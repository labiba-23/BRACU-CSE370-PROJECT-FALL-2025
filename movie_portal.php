<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }


$total = (int)$pdo->query("SELECT COUNT(*) FROM movie_votes")->fetchColumn();


$stmt = $pdo->query("
  SELECT movie_title, COUNT(*) AS cnt
  FROM movie_votes
  GROUP BY movie_title
  ORDER BY cnt DESC, movie_title ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topMovie = $rows[0]["movie_title"] ?? "";
$topCount = (int)($rows[0]["cnt"] ?? 0);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Movie Majority Portal</title>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .bar-wrap { background: rgba(255,255,255,.12); border-radius: 999px; overflow:hidden; height: 16px; }
    .bar { height: 16px; background: rgba(255, 105, 180, .85); width:0; }
    .row { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,.12); }
    .muted { opacity:.8; }
    .pill { display:inline-block; padding:4px 10px; border-radius:999px; background: rgba(255,255,255,.12); }
  </style>
</head>
<body class="app-body">
<div class="app-page">
  <div class="app-card">
    <div class="app-title">Movie Majority Portal</div>
    <div class="app-sub muted">Shows what most users want (poll %)</div>

    <hr style="margin:18px 0; opacity:.2">

    <?php if ($total <= 0): ?>
      <div class="notice err">No votes yet. Ask users to select “Up Next”.</div>
    <?php else: ?>
      <div class="notice ok">
        Total votes: <b><?= $total ?></b> · Top choice: <b><?= h($topMovie) ?></b> (<?= $topCount ?> votes)
      </div>

      <div style="margin-top:14px;">
        <?php foreach ($rows as $r):
          $title = (string)$r["movie_title"];
          $cnt = (int)$r["cnt"];
          $pct = $total > 0 ? round(($cnt * 100) / $total, 1) : 0;
        ?>
          <div class="row">
            <div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
              <div>
                <b><?= h($title) ?></b>
                <span class="pill"><?= $cnt ?> votes</span>
              </div>
              <div><b><?= $pct ?>%</b></div>
            </div>

            <div class="bar-wrap" style="margin-top:10px;">
              <div class="bar" style="width: <?= $pct ?>%;"></div>
            </div>

       
            <div style="margin-top:10px;">
              <a class="btn btn-ghost" href="ticket_buy.php?movie=<?= urlencode($title) ?>">Buy Ticket</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div style="margin-top:14px;">
      <a class="btn btn-ghost" href="up_next.php">Back</a>
      <a class="btn btn-ghost" href="dashboard.php">Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>
