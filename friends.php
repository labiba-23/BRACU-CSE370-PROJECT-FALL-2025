<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId = (int)$_SESSION["user_id"];
$msg = "";
$isOk = true;

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["send_request"])) {
  $email = trim($_POST["email"] ?? "");

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = "Enter a valid email.";
    $isOk = false;
  } else {
   
    $stmt = $pdo->prepare("
      SELECT u.ID, v.Privacy
      FROM user u
      JOIN visitor v ON v.ID = u.ID
      WHERE u.Email = ?
      LIMIT 1
    ");
    $stmt->execute([$email]);
    $friend = $stmt->fetch();

    if (!$friend) {
      $msg = "User not found.";
      $isOk = false;
    } else {
      $friendId = (int)$friend["ID"];

      if ($friendId === $userId) {
        $msg = "You cannot add yourself.";
        $isOk = false;
      } else {
        
        $stmt = $pdo->prepare("
          SELECT 1 FROM can_add
          WHERE Visitor1_ID = ? AND Visitor2_ID = ?
          LIMIT 1
        ");
        $stmt->execute([$userId, $friendId]);

        if ($stmt->fetch()) {
          $msg = "Already friends.";
          $isOk = false;
        } else {
          
          if ((int)$friend["Privacy"] === 0) {
            $stmt = $pdo->prepare("
              INSERT IGNORE INTO can_add (Visitor1_ID, Visitor2_ID)
              VALUES (?, ?)
            ");
            $stmt->execute([$userId, $friendId]);
            $stmt->execute([$friendId, $userId]);

            $msg = "Friend added instantly (public profile).";
            $isOk = true;
          }
         
          else {
            $stmt = $pdo->prepare("
              INSERT IGNORE INTO friend_requests (requester_id, receiver_id, status)
              VALUES (?, ?, 'pending')
            ");
            $stmt->execute([$userId, $friendId]);

            $msg = "📩 Friend request sent.";
            $isOk = true;
          }
        }
      }
    }
  }
}


$stmt = $pdo->prepare("
  SELECT u.Name, u.Email
  FROM can_add c
  JOIN user u ON u.ID = c.Visitor2_ID
  WHERE c.Visitor1_ID = ?
  ORDER BY u.Name
");
$stmt->execute([$userId]);
$friends = $stmt->fetchAll();


$stmt = $pdo->prepare("
  SELECT u.Name, u.Email, fr.created_at
  FROM friend_requests fr
  JOIN user u ON u.ID = fr.receiver_id
  WHERE fr.requester_id = ? AND fr.status = 'pending'
  ORDER BY fr.created_at DESC
");
$stmt->execute([$userId]);
$sent = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Friends</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">

      <div class="app-title">Friends</div>
      <div class="app-sub">Add friends by email.</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

   
      <form method="POST">
        <label class="app-label">Friend Email</label>
        <input class="app-input" type="email" name="email" required placeholder="friend@example.com">
        <button class="btn" type="submit" name="send_request">Add / Request</button>
      </form>

      <hr style="margin:18px 0; opacity:.2">

      
      <h3>Your Friends</h3>
      <?php if (!$friends): ?>
        <p class="app-sub">No friends yet.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($friends as $f): ?>
            <li><?= h((string)$f["Name"]) ?> (<?= h((string)$f["Email"]) ?>)</li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Sent Requests</h3>
      <?php if (!$sent): ?>
        <p class="app-sub">No pending sent requests.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($sent as $s): ?>
            <li>
              <?= h((string)$s["Name"]) ?> (<?= h((string)$s["Email"]) ?>)
              — <span class="badge-pending">Pending</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
      </div>

    </div>
  </div>
</body>
</html>



