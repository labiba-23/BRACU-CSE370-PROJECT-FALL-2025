<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

$userId  = (int)$_SESSION["user_id"];
$partyId = (int)($_GET["party_id"] ?? 0);

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

if ($partyId <= 0) die("Invalid party.");


$movieCol = null;
$stmt = $pdo->query("DESCRIBE watch_parties");
$fields = array_map(fn($r) => $r["Field"], $stmt->fetchAll(PDO::FETCH_ASSOC));
foreach (["movie_title", "movie_name", "movie_id"] as $c) {
  if (in_array($c, $fields, true)) { $movieCol = $c; break; }
}
if ($movieCol === null) {
  die("watch_parties table has no movie column (movie_title/movie_name/movie_id).");
}

$sql = "
  SELECT wp.party_id, wp.group_id, wp.host_id, wp.`{$movieCol}` AS movie_value,
         wp.cost, wp.status, wp.created_at,
         g.group_name
  FROM watch_parties wp
  JOIN `groups` g ON g.group_id = wp.group_id
  WHERE wp.party_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$partyId]);
$party = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$party) die("Party not found.");


$stmt = $pdo->prepare("
  SELECT 1
  FROM party_invites
  WHERE party_id=? AND visitor_id=?
  UNION
  SELECT 1 WHERE ? = ?
  LIMIT 1
");
$stmt->execute([$partyId, $userId, $userId, (int)$party["host_id"]]);
if (!$stmt->fetch()) die("You are not allowed to view this party.");


$stmt = $pdo->prepare("
  SELECT u.ID AS user_id, u.Name, u.Email, pi.status, pi.responded_at
  FROM party_invites pi
  JOIN user u ON u.ID = pi.visitor_id
  WHERE pi.party_id = ?
   AND pi.visitor_id <> ?
  ORDER BY (pi.status='pending') DESC, u.Name
");
$stmt->execute([$partyId,$userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$movieDisplay = (string)($party["movie_value"] ?? "");


$counts = ["pending"=>0, "accepted"=>0, "rejected"=>0];
foreach ($rows as $r) {
  $st = (string)$r["status"];
  if (isset($counts[$st])) $counts[$st]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Party</title>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .badge {
      display:inline-block; padding:4px 10px; border-radius:999px;
      font-weight:700; font-size:12px; text-transform:uppercase;
      border:1px solid rgba(255,255,255,.18);
    }
    .b-pending { background: rgba(255, 200, 0, .18); }
    .b-accepted { background: rgba(0, 255, 120, .18); }
    .b-rejected { background: rgba(255, 80, 80, .18); }
    .muted { opacity:.8; }
  </style>
</head>
<body class="app-body">
  <div class="app-page">
    <div class="app-card">
      <div class="app-title"><?= h((string)$party["group_name"]) ?> — Watch Party</div>
      <div class="app-sub">
        Movie: <b><?= h($movieDisplay) ?></b> · Cost: <b><?= (int)$party["cost"] ?></b>
      </div>

      <div class="app-sub muted" style="margin-top:10px;">
        Pending: <b><?= (int)$counts["pending"] ?></b> ·
        Accepted: <b><?= (int)$counts["accepted"] ?></b> ·
        Rejected: <b><?= (int)$counts["rejected"] ?></b>
      </div>

      <hr style="margin:18px 0; opacity:.2">

      <h3>Invites</h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Member</th>
              <th>Email</th>
              <th>Status</th>
              <th>Responded</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                $st = (string)$r["status"];
                $badgeClass = $st === "accepted" ? "b-accepted" : ($st === "rejected" ? "b-rejected" : "b-pending");
                $responded = $r["responded_at"] ? (string)$r["responded_at"] : "-";
                $name = (string)$r["Name"];
                if ((int)$r["user_id"] === $userId) $name .= " (You)";
              ?>
              <tr>
                <td><?= h($name) ?></td>
                <td><?= h((string)$r["Email"]) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= h($st) ?></span></td>
                <td><?= h($responded) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:14px;">
        <a class="btn btn-ghost" href="balance.php">Go to Balance</a>
        <a class="btn btn-ghost" href="friends.php">Back to Friends</a>
      </div>
    </div>
  </div>
</body>
</html>

