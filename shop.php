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


$stmt = $pdo->prepare("SELECT Reward_points, balance FROM visitor WHERE ID = ?");
$stmt->execute([$userId]);
$visitor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$visitor) die("Visitor not found.");


$isPremium = isPremium($pdo, $userId);


$stmt = $pdo->query("
    SELECT product_id, name, product_type, price
    FROM products
    WHERE is_active = 1
    ORDER BY product_type, name
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


$snacksOnly = [];
$drinksOnly = [];
$merch  = [];
$gcards = [];

foreach ($products as $p) {
    $type = (string)$p["product_type"];
    if ($type === "SNACK") $snacksOnly[] = $p;
    elseif ($type === "DRINK") $drinksOnly[] = $p;
    elseif ($type === "MERCH") $merch[] = $p;
    elseif ($type === "GIFT_CARD") $gcards[] = $p;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop</title>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .tabs { display:flex; gap:10px; margin:14px 0; flex-wrap:wrap; }
    .tab-btn { cursor:pointer; border:0; padding:12px 16px; border-radius:999px; font-weight:800; }
    .tab-btn.active { outline:2px solid rgba(255,255,255,.25); }
    .tab-panel { display:none; margin-top:10px; }
    .tab-panel.active { display:block; }

    .grid { display:grid; grid-template-columns: 1fr 140px; gap:10px; }
    .row { padding:10px 0; border-bottom:1px solid rgba(255,255,255,.15); }
    .type { font-size:12px; opacity:.75; margin-left:8px; }
    .qty { width:100%; padding:10px; border-radius:10px; border:1px solid rgba(255,255,255,.25); }
    .empty { opacity:.75; padding:10px 0; }
    .section-title { font-weight:900; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.25); }

    .summary-box { padding:12px; border:1px solid rgba(255,255,255,.2); border-radius:14px; }
    .sum-row { display:flex; justify-content:space-between; padding:6px 0; }
    .sum-total { display:flex; justify-content:space-between; padding:8px 0; font-weight:900; border-top:1px solid rgba(255,255,255,.15); }
    .note { margin-top:8px; font-size:12px; opacity:.8; }
    .badge { display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:900; margin-left:10px; }
  </style>
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">

      <div class="app-title">Shop</div>
      <div class="app-sub">Buy snacks, drinks, merchandise, and gift cards</div>

      <div style="margin-top:10px; font-weight:800;">
        Balance: <?= (int)$visitor["balance"]; ?>
        &nbsp;&nbsp;&nbsp;
        Reward Points: <?= (int)$visitor["Reward_points"]; ?>

        <?php if ($isPremium): ?>
          <span class="badge" style="background:rgba(0,255,120,.15); border:1px solid rgba(0,255,120,.35);">
            Premium ✅ (10% off)
          </span>
        <?php else: ?>
          <span class="badge" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.20);">
            Standard
          </span>
        <?php endif; ?>
      </div>

      <div class="tabs">
        <button type="button" class="btn tab-btn active" data-tab="snacks">Snacks & Drinks</button>
        <button type="button" class="btn tab-btn" data-tab="merch">Merchandise</button>
        <button type="button" class="btn tab-btn" data-tab="gifts">Gift Cards</button>
      </div>

      <form method="POST" action="checkout_submit.php">
        <input type="hidden" name="visitor_id" value="<?= (int)$userId; ?>">

     
        <div class="tab-panel active" id="tab-snacks">
          <div class="table-wrap">
            <div class="grid">
              <div><b>Product</b></div>
              <div><b>Qty</b></div>

              <?php if (!$snacksOnly && !$drinksOnly): ?>
                <div class="empty">No snacks or drinks available.</div><div></div>
              <?php else: ?>

                <?php if ($snacksOnly): ?>
                  <div class="section-title">Snacks</div><div class="section-title"></div>
                  <?php foreach ($snacksOnly as $p): ?>
                    <div class="row">
                      <?= h((string)$p["name"]); ?>
                      <span class="type">(SNACK) - <?= (int)$p["price"]; ?></span>
                    </div>
                    <div class="row">
                      <input class="qty" type="number" min="0" step="1" value="0"
                             name="qty[<?= (int)$p["product_id"]; ?>]">
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($drinksOnly): ?>
                  <div class="section-title">Drinks</div><div class="section-title"></div>
                  <?php foreach ($drinksOnly as $p): ?>
                    <div class="row">
                      <?= h((string)$p["name"]); ?>
                      <span class="type">(DRINK) - <?= (int)$p["price"]; ?></span>
                    </div>
                    <div class="row">
                      <input class="qty" type="number" min="0" step="1" value="0"
                             name="qty[<?= (int)$p["product_id"]; ?>]">
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>

              <?php endif; ?>
            </div>
          </div>
        </div>

       
        <div class="tab-panel" id="tab-merch">
          <div class="table-wrap">
            <div class="grid">
              <div><b>Product</b></div>
              <div><b>Qty</b></div>

              <?php if (!$merch): ?>
                <div class="empty">No merchandise products available.</div><div></div>
              <?php else: ?>
                <?php foreach ($merch as $p): ?>
                  <div class="row">
                    <?= h((string)$p["name"]); ?>
                    <span class="type">(MERCH) - <?= (int)$p["price"]; ?></span>
                  </div>
                  <div class="row">
                    <input class="qty" type="number" min="0" step="1" value="0"
                           name="qty[<?= (int)$p["product_id"]; ?>]">
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

     
        <div class="tab-panel" id="tab-gifts">
          <div class="table-wrap">
            <div class="grid">
              <div><b>Gift Card</b></div>
              <div><b>Qty</b></div>

              <?php if (!$gcards): ?>
                <div class="empty">No gift card products available.</div><div></div>
              <?php else: ?>
                <?php foreach ($gcards as $p): ?>
                  <div class="row">
                    <?= h((string)$p["name"]); ?>
                    <span class="type">(GIFT_CARD) - <?= (int)$p["price"]; ?></span>
                  </div>
                  <div class="row">
                    <input class="qty" type="number" min="0" step="1" value="0"
                           name="qty[<?= (int)$p["product_id"]; ?>]">
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <hr style="margin:18px 0; opacity:.2">

        <h3>Checkout Options</h3>

        <label>
          <input type="checkbox" name="use_points" value="1" checked>
          Use reward points (1 point = 1 currency unit)
        </label><br>

        <label>
          <input type="checkbox" name="use_balance" value="1" checked>
          Use balance
        </label><br><br>

        <label>Payment Method:</label>
        <select name="payment_method" required>
          <option value="Cash">Cash</option>
          <option value="Card">Card</option>
          <option value="bKash">bKash</option>
          <option value="Nagad">Nagad</option>
        </select>

        <hr style="margin:18px 0; opacity:.2">

       
        <div class="summary-box">
          <div style="font-weight:900; margin-bottom:8px;">Order Summary</div>

          <div class="sum-row">
            <span>Subtotal</span>
            <span id="subtotalText">0</span>
          </div>

          <?php if ($isPremium): ?>
            <div class="sum-row" style="opacity:.9;">
              <span>Premium Discount (10%)</span>
              <span id="discountText">-0</span>
            </div>
            <div class="sum-total">
              <span>Total After Discount</span>
              <span id="totalAfterText">0</span>
            </div>
            <div class="note">Premium actived!  Discount will be applied at checkout.</div>
          <?php else: ?>
            <div class="note">Not premium — no discount. Buy Premium to get 10% off.</div>
          <?php endif; ?>
        </div>

      
        <input type="hidden" name="client_subtotal" id="clientSubtotal" value="0">
        <input type="hidden" name="client_discount" id="clientDiscount" value="0">
        <input type="hidden" name="client_total_after" id="clientTotalAfter" value="0">

        <br>
        <button class="btn" type="submit">Place Order</button>
      </form>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="purchase_history.php">Purchase History</a>
        <a class="btn btn-ghost" href="balance.php">Wallet</a>
        <a class="btn btn-ghost" href="gift_cards.php">My Gift Cards (Send to Friends)</a>
        <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
      </div>

    </div>
  </div>

  <script>
    const btns = document.querySelectorAll("[data-tab]");
    const panels = {
      snacks: document.getElementById("tab-snacks"),
      merch: document.getElementById("tab-merch"),
      gifts: document.getElementById("tab-gifts"),
    };

    btns.forEach(b => {
      b.addEventListener("click", () => {
        btns.forEach(x => x.classList.remove("active"));
        b.classList.add("active");

        Object.values(panels).forEach(p => p.classList.remove("active"));
        panels[b.dataset.tab].classList.add("active");
      });
    });
  </script>

 
  <script>
    const PRICE_MAP = <?php
      $priceMap = [];
      foreach ($products as $p) {
        $priceMap[(int)$p["product_id"]] = (int)$p["price"];
      }
      echo json_encode($priceMap, JSON_UNESCAPED_SLASHES);
    ?>;

    const IS_PREMIUM = <?= $isPremium ? "true" : "false" ?>;

    const subtotalText = document.getElementById("subtotalText");
    const discountText = document.getElementById("discountText");
    const totalAfterText = document.getElementById("totalAfterText");

    const clientSubtotal = document.getElementById("clientSubtotal");
    const clientDiscount = document.getElementById("clientDiscount");
    const clientTotalAfter = document.getElementById("clientTotalAfter");

    function money(n){ return String(Math.max(0, Math.round(n))); }

    function calcTotals() {
      let subtotal = 0;

      document.querySelectorAll('input[name^="qty["]').forEach(inp => {
        const qty = parseInt(inp.value || "0", 10);
        if (!qty || qty < 0) return;

        const match = inp.name.match(/^qty\[(\d+)\]$/);
        if (!match) return;

        const pid = parseInt(match[1], 10);
        const price = PRICE_MAP[pid] ?? 0;
        subtotal += qty * price;
      });

      let discount = 0;
      let totalAfter = subtotal;

      if (IS_PREMIUM) {
        discount = Math.round(subtotal * 0.10);
        totalAfter = subtotal - discount;
      }

      subtotalText.textContent = money(subtotal);
      if (discountText) discountText.textContent = "-" + money(discount);
      if (totalAfterText) totalAfterText.textContent = money(totalAfter);

      clientSubtotal.value = money(subtotal);
      clientDiscount.value = money(discount);
      clientTotalAfter.value = money(totalAfter);
    }

    document.querySelectorAll('input[name^="qty["]').forEach(inp => {
      inp.addEventListener("input", calcTotals);
      inp.addEventListener("change", calcTotals);
    });

    calcTotals();
  </script>
</body>
</html>

