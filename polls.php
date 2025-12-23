<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}
$userId = (int)$_SESSION["user_id"];

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$msg = "";
$isOk = true;

/* 1) Get active poll */
$stmt = $pdo->query("SELECT poll_id, question FROM polls WHERE is_active = 1 LIMIT 1");
$poll = $stmt->fetch();

$pollId = $poll ? (int)$poll["poll_id"] : 0;

/* 2) If active poll exists, load options */
$options = [];
if ($pollId > 0) {
  $stmt = $pdo->prepare("SELECT option_id, option_text FROM poll_options WHERE poll_id = ? ORDER BY option_id ASC");
  $stmt->execute([$pollId]);
  $options = $stmt->fetchAll();
}

/* 3) Check if user already voted */
$alreadyVoted = false;
$myVoteOptionId = null;

if ($pollId > 0) {
  $stmt = $pdo->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ? LIMIT 1");
  $stmt->execute([$pollId, $userId]);
  $v = $stmt->fetch();
  if ($v) {
    $alreadyVoted = true;
    $myVoteOptionId = (int)$v["option_id"];
  }
}

/* 4) Save vote */
if ($_SERVER["REQUEST_METHOD"] === "POST" && $pollId > 0) {
  if ($alreadyVoted) {
    $msg = "You already voted on this poll.";
    $isOk = false;
  } else {
    $choice = (int)($_POST["option_id"] ?? 0);

    
    $stmt = $pdo->prepare("SELECT 1 FROM poll_options WHERE poll_id = ? AND option_id = ? LIMIT 1");
    $stmt->execute([$pollId, $choice]);
    if (!$stmt->fetch()) {
      $msg = "Invalid option.";
      $isOk = false;
    } else {
      try {
        $stmt = $pdo->prepare("INSERT INTO poll_votes (poll_id, user_id, option_id) VALUES (?, ?, ?)");
        $stmt->execute([$pollId, $userId, $choice]);
        $msg = " Vote submitted!";
        $isOk = true;
        $alreadyVoted = true;
        $myVoteOptionId = $choice;
      } catch (Exception $e) {
        $msg = "Could not save vote (maybe you already voted).";
        $isOk = false;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Polls</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Poll</div>
      <div class="app-sub">Vote on the active poll.</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if (!$poll): ?>
        <p class="app-sub">No poll is active right now.</p>
      <?php else: ?>
        <h3 style="margin:10px 0 12px;"><?= h((string)$poll["question"]) ?></h3>

        <?php if ($alreadyVoted): ?>
          <p class="app-sub">You already voted ✅</p>
        <?php endif; ?>

        <form method="POST">
          <?php foreach ($options as $opt): ?>
            <?php $oid = (int)$opt["option_id"]; ?>
            <label style="display:flex; gap:10px; align-items:center; margin:10px 0;">
              <input type="radio" name="option_id" value="<?= $oid ?>"
                <?= ($alreadyVoted && $myVoteOptionId === $oid) ? "checked" : "" ?>
                <?= $alreadyVoted ? "disabled" : "" ?>
              >
              <span><?= h((string)$opt["option_text"]) ?></span>
            </label>
          <?php endforeach; ?>

          <?php if (!$alreadyVoted): ?>
            <button class="btn" type="submit">Submit Vote</button>
          <?php endif; ?>
        </form>

        <div style="margin-top:14px;">
          <a class="btn btn-ghost" href="dashboard.php">Back</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
