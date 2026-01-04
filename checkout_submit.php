<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/premium_helper.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


$payment_method = trim((string)($_POST["payment_method"] ?? ""));
$use_points  = isset($_POST["use_points"]);
$use_balance = isset($_POST["use_balance"]);

$qtyMap = $_POST["qty"] ?? [];
if (!is_array($qtyMap)) {
    http_response_code(400);
    die("Invalid items.");
}


$items = [];
foreach ($qtyMap as $pid => $qty) {
    $pid = (int)$pid;
    $qty = (int)$qty;
    if ($pid > 0 && $qty > 0) {
        $items[] = ["product_id" => $pid, "qty" => $qty];
    }
}

if (!$items) {
    http_response_code(400);
    die("No items selected.");
}

if ($payment_method === "") {
    http_response_code(400);
    die("Payment method missing.");
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT Reward_points, balance FROM visitor WHERE ID=? FOR UPDATE");
    $stmt->execute([$userId]);
    $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$visitor) throw new Exception("Visitor not found");

    $points  = (int)$visitor["Reward_points"];
    $balance = (int)$visitor["balance"];

   
    $isPremium = isPremium($pdo, $userId);

  
    $ids = array_values(array_unique(array_column($items, "product_id")));
    $ph  = implode(",", array_fill(0, count($ids), "?"));

    $stmt = $pdo->prepare("
        SELECT product_id, name, product_type, price
        FROM products
        WHERE is_active=1 AND product_id IN ($ph)
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products = [];
    foreach ($rows as $r) $products[(int)$r["product_id"]] = $r;

    $subtotal = 0;
    $Sn_flag = 0; $Fr_flag = 0; $GC_flag = 0; $Tick_flag = 0;

    $foodNames = []; $drinkNames = [];
    $foodQty = 0; $drinkQty = 0;

    foreach ($items as $it) {
        $pid = (int)$it["product_id"];
        $qty = (int)$it["qty"];

        if (!isset($products[$pid])) {
            throw new Exception("Invalid product ID $pid");
        }

        $p = $products[$pid];
        $price = (int)$p["price"];
        $type  = (string)$p["product_type"];
        $name  = (string)$p["name"];

        $line = $price * $qty;
        $subtotal += $line;

        if ($type === "SNACK") {
            $Sn_flag = 1;
            $foodNames[] = "{$name} x{$qty}";
            $foodQty += $qty;
        } elseif ($type === "DRINK") {
            $Fr_flag = 1;
            $drinkNames[] = "{$name} x{$qty}";
            $drinkQty += $qty;
        } elseif ($type === "GIFT_CARD") {
            $GC_flag = 1;
        }
       
    }

    $discount_amount = 0;
    $discount_reason = null;

    if ($isPremium && $subtotal > 0) {
        $discount_amount = (int) round($subtotal * 0.10);
        if ($discount_amount < 0) $discount_amount = 0;
        if ($discount_amount > $subtotal) $discount_amount = $subtotal;
        $discount_reason = "PREMIUM_10_PERCENT";
    }

    $total_after_discount = $subtotal - $discount_amount;

    $points_used = 0;
    $wallet_used = 0;

    if ($use_points) {
        $points_used = min($points, $total_after_discount);
        $points -= $points_used;
    }

    $remaining = $total_after_discount - $points_used;

    if ($use_balance && $remaining > 0) {
        $wallet_used = min($balance, $remaining);
        $balance -= $wallet_used;
    }

    $paid_amount = $total_after_discount - $points_used - $wallet_used;


    $stmt = $pdo->query("SELECT COALESCE(MAX(P_ID),0)+1 FROM purchase");
    $P_ID = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO purchase
        (P_ID, P_date, Pay_later, Amount, PreBook, Visitor_ID,
         Sn_flag, Fr_flag, Tick_flag, GC_flag,
         Food_name, Drinks_name, Food_quantity, Drinks_quantity, Ticket_num,
         points_used, wallet_used, paid_amount,
         discount_amount, discount_reason)
        VALUES
        (?, CURDATE(), 0, ?, 0, ?,
         ?, ?, ?, ?,
         ?, ?, ?, ?, 0,
         ?, ?, ?,
         ?, ?)
    ");

    $stmt->execute([
        $P_ID, $subtotal, $userId,
        $Sn_flag, $Fr_flag, $Tick_flag, $GC_flag,
        implode(", ", $foodNames),
        implode(", ", $drinkNames),
        $foodQty, $drinkQty,
        $points_used, $wallet_used, $paid_amount,
        $discount_amount, $discount_reason
    ]);

   
    $stmt = $pdo->prepare("
        INSERT INTO purchase_line_items
        (P_ID, product_id, quantity, unit_price, line_total)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $it) {
        $pid = (int)$it["product_id"];
        $qty = (int)$it["qty"];
        $price = (int)$products[$pid]["price"];
        $stmt->execute([$P_ID, $pid, $qty, $price, $price * $qty]);
    }

   
    $stmt = $pdo->prepare("UPDATE visitor SET Reward_points=?, balance=? WHERE ID=?");
    $stmt->execute([$points, $balance, $userId]);

 
    if ($wallet_used > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO balance_ledger (visitor_id, party_id, change_amount, reason)
            VALUES (?, NULL, ?, ?)
        ");
        $stmt->execute([$userId, -$wallet_used, "Purchase wallet deduction P_ID=$P_ID"]);
    }

    $stmt = $pdo->prepare("INSERT INTO purchase_method (P_ID, Payment_method) VALUES (?, ?)");
    $stmt->execute([$P_ID, $payment_method]);

    foreach ($items as $it) {
        $pid = (int)$it["product_id"];
        $qty = (int)$it["qty"];

        if (((string)$products[$pid]["product_type"]) !== "GIFT_CARD") continue;

        $value = (int)$products[$pid]["price"];
        for ($i = 0; $i < $qty; $i++) {
            $code = "GC-" . random_int(100000, 999999) . time() . "-$i";
            $stmt = $pdo->prepare("
                INSERT INTO gift_cards (card_code, initial_value, remaining_value, issued_to_visitor_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$code, $value, $value, $userId]);
        }
    }

    $pdo->commit();

    if ($paid_amount > 0) {
        header("Location: payment_demo.php?pid=$P_ID");
    } else {
        header("Location: purchase_success.php?pid=$P_ID");
    }
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo "Checkout failed: " . h($e->getMessage());
}




