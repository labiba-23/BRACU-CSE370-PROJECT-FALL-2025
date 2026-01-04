<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId  = (int)$_SESSION["user_id"];
$groupId = (int)($_GET["group_id"] ?? 0);

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

if ($groupId <= 0) {
  die("Invalid group.");
}


$movieCol = null;
$stmt = $pdo->query("DESCRIBE watch_parties");
$fields = array_map(fn($r) => $r["Field"], $stmt->fetchAll(PDO::FETCH_ASSOC));
foreach (["movie_title", "movie_name", "movie_id"] as $c) {
  if (in_array($c, $fields, true)) { $movieCol = $c; break; }
}
if ($movieCol === null) {
  die("watch_parties table has no movie column (movie_title/movie_name/movie_id).");
}


$stmt = $pdo->prepare("SELECT group_name FROM `groups` WHERE group_id = ?");
$stmt->execute([$groupId]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
  die("Group not found.");
}

$stmt = $pdo->prepare("
  SELECT 1
  FROM group_members
  WHERE group_id = ? AND visitor_id = ?
  LIMIT 1
");
$stmt->execute([$groupId, $userId]);

if (!$stmt->fetchColumn()) {
  die("You are not a member of this group.");
}


$stmt = $pdo->prepare("
  SELECT u.Name, u.Email
  FROM group_members gm
  JOIN user u ON u.ID = gm.visitor_id
  WHERE gm.group_id = ?
  ORDER BY u.Name
");
$stmt->execute([$groupId]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("
  SELECT
    wp.party_id,
    wp.group_id,
    wp.host_id,
    wp.`{$movieCol}` AS movie_value,
    wp.cost,
    wp.status,
    wp.created_at,
    host.Name AS host_name,
    COALESCE(SUM(CASE WHEN pi.status='pending'  THEN 1 ELSE 0 END),0) AS pending_count,
    COALESCE(SUM(CASE WHEN pi.status='accepted' THEN 1 ELSE 0 END),0) AS accepted_count,
    COALESCE(SUM(CASE WHEN pi.status='rejected' THEN 1 ELSE 0 END),0) AS rejected_count
  FROM watch_parties wp
  LEFT JOIN party_invites pi ON pi.party_id = wp.party_id
  LEFT JOIN user host ON host.ID = wp.host_id
  WHERE wp.group_id = ?
  GROUP BY wp.party_id
  ORDER BY wp.created_at DESC
");
$stmt->execute([$groupId]);
$parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= h((string)$group["group_name"]) ?></title>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .section-title{margin-top:18px;}
    .pill{
      display:inline-block;
      padding:6px 10px;
      border-radius:999px;
      font-weight:900;
      border:1px solid rgba(255,255,255,0.14);
      background:rgba(255,255,255,0.08);
      color:#fff;
      font-size:12px;
    }
    .pill.pending{ color:#ffd86b; }
    .pill.accepted{ color:#7CFFB5; }
    .pill.rejected{ color:#ff7b7b; }
  </style>
</head>

<body class="app-body">
  <div class="app-page">
    <div class="app-card">

      <div class="app-title pink"><?= h((string)$group["group_name"]) ?></div>
      <div class="app-sub">Group members & watch parties</div>

      <h3 class="section-title">Watch Parties</h3>

    <div style="margin:10px 0 14px;">
 
</div>


      <?php if (!$parties): ?>
        <p class="app-sub">No watch parties yet for this group.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Movie</th>
                <th>Cost</th>
                <th>Host</th>
                <th>Status</th>
                <th>Invites</th>
                <th>Created</th>
                <th>View</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($parties as $p): ?>
                <tr>
                  <td><?= h((string)$p["movie_value"]) ?></td>
                  <td><?= (int)$p["cost"] ?></td>
                  <td>
                    <?= h((string)($p["host_name"] ?? "Unknown")) ?>
                    <?php if ((int)$p["host_id"] === $userId): ?> (You)<?php endif; ?>
                  </td>
                  <td><?= h((string)$p["status"]) ?></td>
                  <td>
                    <span class="pill pending">Pending: <?= (int)$p["pending_count"] ?></span>
                    <span class="pill accepted">Accepted: <?= (int)$p["accepted_count"] ?></span>
                    <span class="pill rejected">Rejected: <?= (int)$p["rejected_count"] ?></span>
                  </td>
                  <td><?= h((string)$p["created_at"]) ?></td>
                  <td>
                    
                    <a class="btn btn-ghost small" href="party.php?party_id=<?= (int)$p["party_id"] ?>">View</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <hr style="margin:18px 0; opacity:.2">

      <h3 class="section-title">Group Members</h3>
      <?php if (!$members): ?>
        <p class="app-sub">No members found.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($members as $m): ?>
            <li><?= h((string)$m["Name"]) ?> (<?= h((string)$m["Email"]) ?>)</li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="friends.php">← Back to Friends</a>
        <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
      </div>

    </div>
  </div>
</body>

</html>

