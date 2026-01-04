<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION["user_id"];

$movie = trim((string)($_POST["movie_title"] ?? ""));
if ($movie === "") {
  header("Location: up_next.php?err=Choose+a+movie");
  exit;
}

$stmt = $pdo->prepare("
  INSERT INTO movie_votes (visitor_id, movie_title)
  VALUES (?, ?)
  ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
");
$stmt->execute([$userId, $movie]);

header("Location: up_next.php?msg=Saved");
exit;
