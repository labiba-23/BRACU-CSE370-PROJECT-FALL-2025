<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
  header("Location: login.php");
  exit;
}

$adminId = (int)$_SESSION["user_id"];
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$msg = "";
$isOk = true;


if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create") {
  $question = trim((string)($_POST["question"] ?? ""));
  $raw = trim((string)($_POST["options"] ?? ""));

  $opts = array_values(array_filter(array_map("trim", explode(",", $raw))));

  if ($question === "" || count($opts) < 2) {
    $msg = "Enter a question and at least 2 options (comma separated).";
    $isOk = false;
  } else {
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("INSERT INTO polls (question, is_active, created_by_admin_id) VALUES (?, 0, ?)");
      $stmt->execute([$question, $adminId]);
      $pollId = (int)$pdo->lastInsertId();

      $ins = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
      foreach ($opts as $o) $ins->execute([$pollId, $o]);

      $pdo->commit();
      $msg = "✅ Poll created (not active yet).";
      $isOk = true;
    } catch (Exception $e) {
      $pdo->rollBack();
      $msg = "Failed to create poll.";
      $isOk = false;
    }
  }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "activate") {
  $pollId = (int)($_POST["poll_id"] ?? 0);
  if ($pollId > 0) {
    $pdo->beginTransaction();
    try {
      $pdo->exec("UPDATE polls SET is_active = 0");
      $stmt = $pdo->prepare("UPDATE polls SET is_active = 1 WHERE poll_id = ?");
      $stmt->execute([$pollId]);
      $pdo->commit();
      $msg = "✅ Poll activated!";
      $isOk = true;
    } catch (Exception $e) {
      $pdo->rollBack();
      $msg = "Failed to activate poll.";
      $isOk = false;
    }
  }
}


$polls = $pdo->query("SELECT poll_id, question, is_active, created_at FROM polls ORDER BY created_at DESC")->fetchAll();


$active = null;
foreach ($polls as $p) {
  if ((int)$p["is_active"] === 1) { $active = $p; break; }
}


$results = [];
$voters = [];
if ($active) {
  $pid = (int)$active["poll_id"];

  $stmt = $pdo->prepare("
    SELECT o.option_id, o.option_text, COUNT(v.option_id) AS votes
    FROM poll_options o
    LEFT JOIN poll_votes v ON v.option_id = o.option_id
    WHERE o.poll_id = ?
    GROUP BY o.option_id
    ORDER BY votes DESC, o.option_id ASC
  ");
  $stmt->execute([$pid]);
  $results = $stmt->fetchAll();

  $stmt = $pdo->prepare("
    SELECT u.Name, u.Email, o.option_text, v.voted_at
    FROM poll_votes v
    JOIN user u ON u.ID = v.user_id
    JOIN poll_options o ON o.option_id = v.option_id
    WHERE v.poll_id = ?
    ORDER BY v.voted_at DESC
  ");
  $stmt->execute([$pid]);
  $voters = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Polls</title>
  <link rel="stylesheet" href="style.css?v=999">
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title">Admin Polls</div>
      <div class="app-sub">Create polls, activate one, and see who voted what.</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <h3>Create Poll</h3>
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <label class="app-label">Question</label>
        <input class="app-input" name="question" placeholder="e.g. Which genre should we feature?" required>

        <label class="app-label">Options (comma separated)</label>
        <input class="app-input" name="options" placeholder="Action, Comedy, Horror, Romance" required>

        <button class="btn" type="submit">Create</button>
      </form>

      <hr style="margin:18px 0; opacity:.2">

      <h3>All Polls</h3>
      <?php if (!$polls): ?>
        <p class="app-sub">No polls created yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($polls as $p): ?>
              <tr>
                <td><?= (int)$p["poll_id"] ?></td>
                <td><?= h((string)$p["question"]) ?></td>
                <td><?= ((int)$p["is_active"] === 1) ? "ACTIVE" : "inactive" ?></td>
                <td>
                  <?php if ((int)$p["is_active"] !== 1): ?>
                    <form method="POST" style="margin:0;">
                      <input type="hidden" name="action" value="activate">
                      <input type="hidden" name="poll_id" value="<?= (int)$p["poll_id"] ?>">
                      <button class="btn btn-ghost" type="submit">Activate</button>
                    </form>
                  <?php else: ?>
                    <span class="app-chip">Active</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Active Poll Results</h3>
      <?php if (!$active): ?>
        <p class="app-sub">No active poll.</p>
      <?php else: ?>
        <p><b><?= h((string)$active["question"]) ?></b></p>

        <h4 style="margin:12px 0 8px;">Vote counts</h4>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Option</th><th>Votes</th></tr>
            </thead>
            <tbody>
              <?php foreach ($results as $r): ?>
                <tr>
                  <td><?= h((string)$r["option_text"]) ?></td>
                  <td><?= (int)$r["votes"] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <h4 style="margin:14px 0 8px;">Who voted what</h4>
        <?php if (!$voters): ?>
          <p class="app-sub">No votes yet.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>Name</th><th>Email</th><th>Choice</th><th>Time</th></tr>
              </thead>
              <tbody>
                <?php foreach ($voters as $v): ?>
                  <tr>
                    <td><?= h((string)$v["Name"]) ?></td>
                    <td><?= h((string)$v["Email"]) ?></td>
                    <td><?= h((string)$v["option_text"]) ?></td>
                    <td><?= h((string)$v["voted_at"]) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="admin_dashboard.php">Back</a>
      </div>
    </div>
  </div>
</body>
</html>
