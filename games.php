<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION["user_id"];

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("SELECT Reward_points FROM visitor WHERE ID=?");
$stmt->execute([$userId]);
$points = (int)$stmt->fetchColumn();

$msg = (string)($_GET["msg"] ?? "");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Games</title>
  <link rel="stylesheet" href="style.css?v=999">
  <style>
    .games-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-top:16px;}
    .game-card{display:block;padding:18px;border-radius:18px;text-decoration:none;color:#111;
      background:linear-gradient(135deg,#ff77b7,#ff4fa0);
      box-shadow:0 12px 30px rgba(0,0,0,.25);
      transition:transform .2s ease, box-shadow .2s ease;
    }
    .game-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(0,0,0,.35);}
    .game-title{font-size:20px;font-weight:900;margin:0 0 6px;}
    .game-sub{opacity:.9;margin:0;}
  </style>
</head>
<body class="app-body">
<div class="app-page">
  <div class="app-card">
    <div class="app-title">Games</div>
    <div class="app-sub">Play and earn reward points</div>

    <?php if ($msg): ?>
      <div class="notice ok"><?= h($msg) ?></div>
    <?php endif; ?>

    <p><b>Your Reward Points:</b> <?= $points ?></p>

    <div class="games-grid">
      <a class="game-card" href="trivia.php">
        <div class="game-title">Trivia Quiz</div>
        <p class="game-sub">Answer 1 question (once per day).</p>
      </a>

      <a class="game-card" href="daily_spin.php?game=SPIN">

        <div class="game-title"> Daily Spin</div>
        <p class="game-sub">Spin once per day to win points.</p>
      </a>

      <a class="game-card" href="lucky_click.php?game=LUCKY">

        <div class="game-title">Lucky Click</div>
        <p class="game-sub">Click to win points (limited per day).</p>
      </a>
      <a class="game-card" href="pokemon_flip.php">
  <div class="game-title"> PokéFlip</div>
  <p class="game-sub">Flip a Pokémon card and win points (daily).</p>
</a>

    </div>

    <div style="margin-top:14px;">
      <a class="btn btn-ghost" href="dashboard.php">Back to Dashboard</a>
    </div>
  </div>
</div>
</body>
</html>
