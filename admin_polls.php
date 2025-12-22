<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION["user_id"];
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("SELECT `Add polls` AS poll_text FROM `admin` WHERE ID = ? LIMIT 1");
$stmt->execute([$userId]);
$adminRow = $stmt->fetch();

if (!$adminRow) { header("Location: dashboard.php"); exit; }

$currentPoll = (string)($adminRow["poll_text"] ?? "");
$msg = "";
$isOk = true;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $poll = trim((string)($_POST["poll_text"] ?? ""));
  $parts = array_values(array_filter(array_map("trim", explode("|", $poll))));

  if (count($parts) < 3) {
    $msg = "Format: Question | Option1 | Option2 (at least 2 options).";
    $isOk = false;
  } else {
    $pdo->prepare("UPDATE `admin` SET `Add polls`=? WHERE ID=?")->execute([$poll, $userId]);
    $currentPoll = $poll;
    $msg = "✅ Poll updated!";
    $isOk = true;

    // If poll changes, users should be able to vote again (cookie/session key changes automatically)
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Poll</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="container">
  <div class="card" style="max-width:820px;">
    <h1>🗳️ Admin Poll</h1>
    <p class="sub">Set the poll shown to users</p>

    <?php if ($msg): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin_polls.php" autocomplete="off">
      <label>Poll Text</label>
      <textarea name="poll_text" rows="4" maxlength="300" required
        placeholder="Example: Best snack? | Popcorn | Nachos | Coke"><?= h($currentPoll) ?></textarea>
      <button type="submit">Save Poll</button>
    </form>

    <div class="link" style="margin-top:16px;">
      <a href="polls.php">User Poll Page</a> • <a href="admin_dashboard.php">Back</a>
    </div>
  </div>
</div>
</body>
</html>
