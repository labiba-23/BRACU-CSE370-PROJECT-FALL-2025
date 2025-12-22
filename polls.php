<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION["user_id"];
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

/* Get the current poll from admin table (latest admin row) */
$row = $pdo->query("SELECT `Add polls` AS poll_text FROM `admin` ORDER BY ID DESC LIMIT 1")->fetch();
$pollText = trim((string)($row["poll_text"] ?? ""));

$question = "";
$options = [];

if ($pollText !== "") {
  $parts = array_values(array_filter(array_map("trim", explode("|", $pollText))));
  if (count($parts) >= 3) {
    $question = array_shift($parts);
    $options = $parts;
  }
}

if ($question === "" || !$options) {
  $pollId = "";
} else {
  $pollId = sha1($question . "|" . implode("|", $options)); // unique id for this poll
}

/* Store vote per user (session + cookie) */
$cookieKey = "poll_vote_" . $pollId;
$sessionKey = "poll_vote_" . $pollId;

$hasVoted = false;
$myVote = null;

if ($pollId !== "") {
  if (isset($_SESSION[$sessionKey])) {
    $hasVoted = true;
    $myVote = (int)$_SESSION[$sessionKey];
  } elseif (isset($_COOKIE[$cookieKey])) {
    $hasVoted = true;
    $myVote = (int)$_COOKIE[$cookieKey];
    $_SESSION[$sessionKey] = $myVote; // sync back to session
  }
}

$msg = "";
$isOk = true;

if ($_SERVER["REQUEST_METHOD"] === "POST" && $pollId !== "") {
  if ($hasVoted) {
    $msg = "You already voted ✅";
    $isOk = false;
  } else {
    $choice = (int)($_POST["choice"] ?? -1);
    if ($choice < 0 || $choice >= count($options)) {
      $msg = "Invalid choice.";
      $isOk = false;
    } else {
      $_SESSION[$sessionKey] = $choice;
      setcookie($cookieKey, (string)$choice, time() + 60*60*24*30, "/"); // 30 days

      $hasVoted = true;
      $myVote = $choice;

      $msg = "✅ Vote submitted!";
      $isOk = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Polls</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
<div class="container">
  <div class="card" style="max-width:820px;">
    <h1>🗳️ Poll</h1>
    <p class="sub">Vote once (saved in your session/cookie)</p>

    <?php if ($msg): ?>
      <div class="msg <?= $isOk ? "ok" : "error" ?>"><?= h($msg) ?></div>
    <?php endif; ?>

    <?php if ($pollId === ""): ?>
      <p class="sub">No active poll set by admin.</p>
    <?php else: ?>

      <h3 style="margin-top:10px;"><?= h($question) ?></h3>

      <?php if (!$hasVoted): ?>
        <form method="POST" action="polls.php" autocomplete="off">
          <label>Select an option</label>
          <select name="choice" class="select" required>
            <option value="">-- Choose --</option>
            <?php foreach ($options as $i => $opt): ?>
              <option value="<?= (int)$i ?>"><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Submit Vote</button>
        </form>
      <?php else: ?>
        <div class="msg ok">
          You voted for: <b><?= h($options[(int)$myVote] ?? "Unknown") ?></b>
        </div>
        <p class="sub">
          Note: Global results need shared storage (DB table). With only PHP/HTML/CSS, we can only store your vote per browser.
        </p>
      <?php endif; ?>

    <?php endif; ?>

    <div class="link" style="margin-top:16px;">
      <a href="dashboard.php">Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>
