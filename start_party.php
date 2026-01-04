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


$movieCol = null;
try {
  $cols = [];
  $stmt = $pdo->query("DESCRIBE watch_parties");
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $cols[] = $row["Field"];
  }

  foreach (["movie_name", "movie_title", "movie_id"] as $c) {
    if (in_array($c, $cols, true)) {
      $movieCol = $c;
      break;
    }
  }
} catch (Exception $e) {
  $movieCol = null;
}

if ($movieCol === null) {
  $msg = "Your table watch_parties has no movie column. Add one: movie_name (VARCHAR).";
  $isOk = false;
}


$stmt = $pdo->prepare("
  SELECT group_id, group_name
  FROM `groups`
  WHERE creator_id = ?
  ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$myGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);


$selectedGroupId = (int)($_GET["group_id"] ?? 0);


$stmt = $pdo->query("
  SELECT TRIM(on_movie) AS title
  FROM movie_catalogue
  WHERE on_movie IS NOT NULL AND TRIM(on_movie) <> ''
  UNION
  SELECT TRIM(up_movie) AS title
  FROM movie_catalogue
  WHERE up_movie IS NOT NULL AND TRIM(up_movie) <> ''
  ORDER BY title
");
$movies = $stmt->fetchAll(PDO::FETCH_COLUMN);


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_party"]) && $movieCol !== null) {

  $groupId   = (int)($_POST["group_id"] ?? 0);
  $movieName = trim((string)($_POST["movie_name"] ?? ""));
  $cost      = (int)($_POST["cost"] ?? 0);

  if ($groupId <= 0) {
    $msg = "Choose a group.";
    $isOk = false;
  } elseif ($movieName === "") {
    $msg = "Choose a movie.";
    $isOk = false;
  } elseif ($cost < 0) {
    $msg = "Cost must be 0 or more.";
    $isOk = false;
  } else {
    try {
      $pdo->beginTransaction();

     
      $stmt = $pdo->prepare("SELECT 1 FROM `groups` WHERE group_id=? AND creator_id=?");
      $stmt->execute([$groupId, $userId]);
      if (!$stmt->fetch()) {
        throw new Exception("You can only host using groups you created.");
      }

      $sql = "INSERT INTO watch_parties (group_id, host_id, {$movieCol}, cost, status)
              VALUES (?, ?, ?, ?, 'pending')";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$groupId, $userId, $movieName, $cost]);
      $partyId = (int)$pdo->lastInsertId();

      $stmt = $pdo->prepare("SELECT visitor_id FROM group_members WHERE group_id = ?");
      $stmt->execute([$groupId]);
      $members = $stmt->fetchAll(PDO::FETCH_COLUMN);

      $ins = $pdo->prepare("
        INSERT IGNORE INTO party_invites (party_id, visitor_id, status)
        VALUES (?, ?, 'pending')
      ");

      foreach ($members as $vid) {
        $vid = (int)$vid;
        if ($vid === $userId) continue; 
        $ins->execute([$partyId, $vid]);
      }

      $pdo->commit();
      header("Location: party.php?party_id=" . $partyId);
      exit;

    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $msg = $e->getMessage();
      $isOk = false;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Start Watch Party</title>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .btn.small { padding: 8px 10px; font-size: 13px; border-radius: 12px; }
  </style>
</head>
<body class="app-body">
  <div class="app-page">
    <div class="app-card">

      <div class="app-title">Start Watch Party</div>
      <div class="app-sub">Invite your group to watch a movie.</div>

      <?php if ($msg): ?>
        <div class="notice <?= $isOk ? "ok" : "err" ?>"><?= h($msg) ?></div>
      <?php endif; ?>

      <?php if (!$myGroups): ?>
        <p class="app-sub">Create a group first, then you can host a watch party.</p>
        <a class="btn btn-ghost" href="friends.php">Go to Groups</a>

      <?php elseif (!$movies): ?>
        <p class="app-sub">No movies found in movie_catalogue (on_movie / up_movie are empty).</p>
        <a class="btn btn-ghost" href="movie_catalogue.php">Go to Movie Catalogue</a>

      <?php else: ?>
        <form method="POST">
          <label class="app-label">Choose Group</label>
          <select class="app-input" name="group_id" required>
            <option value="">-- Select --</option>
            <?php foreach ($myGroups as $g): ?>
              <?php $gid = (int)$g["group_id"]; ?>
              <option value="<?= $gid ?>" <?= ($selectedGroupId === $gid ? "selected" : "") ?>>
                <?= h((string)$g["group_name"]) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label class="app-label">Movie</label>
          <select class="app-input" name="movie_name" required>
            <option value="">-- Select Movie --</option>
            <?php foreach ($movies as $t): ?>
              <option value="<?= h((string)$t) ?>"><?= h((string)$t) ?></option>
            <?php endforeach; ?>
          </select>

          <label class="app-label">Cost (deducted on Accept)</label>
          <input class="app-input" type="number" name="cost" required value="10" min="0">

          <button class="btn" type="submit" name="create_party">Send Invites</button>
        </form>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
      </div>

    </div>
  </div>
</body>
</html>


