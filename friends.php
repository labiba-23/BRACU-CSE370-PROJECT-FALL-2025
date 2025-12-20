<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$message = "";
$isOk = true;

/**
 * Two-way friends:
 * If A adds B -> insert (A,B) AND (B,A)
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email.";
        $isOk = false;
    } else {
        // Find friend user ID by email
        $stmt = $pdo->prepare("SELECT ID FROM `user` WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $friend = $stmt->fetch();

        if (!$friend) {
            $message = "User not found.";
            $isOk = false;
        } else {
            $friendId = (int)$friend["ID"];
            if ($friendId === $userId) {
                $message = "You cannot add yourself.";
                $isOk = false;
            } else {
                // Check if already friends (A -> B)
                $stmt = $pdo->prepare("SELECT 1 FROM `can_add` WHERE Visitor1_ID = ? AND Visitor2_ID = ? LIMIT 1");
                $stmt->execute([$userId, $friendId]);

                if ($stmt->fetch()) {
                    $message = "Already friends.";
                    $isOk = false;
                } else {
                    // Insert two-way friendship
                    $pdo->beginTransaction();
                    try {
                        $stmt = $pdo->prepare("INSERT INTO `can_add` (Visitor1_ID, Visitor2_ID) VALUES (?, ?)");
                        $stmt->execute([$userId, $friendId]);
                        $stmt->execute([$friendId, $userId]);

                        $pdo->commit();
                        $message = "Friend added successfully!";
                        $isOk = true;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $message = "Failed to add friend. Try again.";
                        $isOk = false;
                    }
                }
            }
        }
    }
}

// Get friend list for current user (Visitor1_ID = me)
$stmt = $pdo->prepare("
    SELECT u.ID, u.Name, u.Email
    FROM `can_add` c
    JOIN `user` u ON u.ID = c.Visitor2_ID
    WHERE c.Visitor1_ID = ?
    ORDER BY u.Name
");
$stmt->execute([$userId]);
$friends = $stmt->fetchAll();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Friends</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h1>Friends</h1>
    <p class="sub">Add friends using their email</p>

    <?php if ($message): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="friends.php" autocomplete="off">
      <label>Friend Email</label>
      <input type="email" name="email" required placeholder="friend@gmail.com">
      <button type="submit">Add Friend</button>
    </form>

    <h3 style="margin-top:18px;">Your Friends</h3>
    <ul style="margin:0; padding-left:18px;">
      <?php if (!$friends): ?>
        <li>No friends yet.</li>
      <?php else: ?>
        <?php foreach ($friends as $f): ?>
          <li><?= h($f["Name"]) ?> — <?= h($f["Email"]) ?></li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>

    <div class="link" style="margin-top:16px;">
      <a href="dashboard.php">Back to Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>

