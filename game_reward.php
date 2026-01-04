<?php
declare(strict_types=1);

header("Content-Type: application/json");

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
  http_response_code(401);
  echo json_encode(["ok" => false, "msg" => "Not logged in"]);
  exit;
}

$userId = (int)$_SESSION["user_id"];

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "msg" => "Invalid JSON"]);
  exit;
}

$game = strtoupper(trim((string)($data["game"] ?? "")));
$pointsToAdd = (int)($data["points"] ?? 0);


$allowedGames = ["TRIVIA", "DAILY_SPIN", "LUCKY_CLICK", "POKE_FLIP"];

if (!in_array($game, $allowedGames, true)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "msg" => "Invalid game"]);
  exit;
}


if ($pointsToAdd < 0 || $pointsToAdd > 50) {
  http_response_code(400);
  echo json_encode(["ok" => false, "msg" => "Invalid points"]);
  exit;
}


$isDaily = true;
$today = (new DateTime("now"))->format("Y-m-d");

try {
  $pdo->beginTransaction();

 
  $stmt = $pdo->prepare("SELECT Reward_points FROM visitor WHERE ID=? FOR UPDATE");
  $stmt->execute([$userId]);
  $current = $stmt->fetchColumn();

  if ($current === false) {
    throw new Exception("Visitor not found");
  }

  if ($isDaily) {
   
    $stmt = $pdo->prepare("
      SELECT 1
      FROM game_plays
      WHERE visitor_id=? AND game_code=? AND play_date=?
      LIMIT 1
    ");
    $stmt->execute([$userId, $game, $today]);

    if ($stmt->fetchColumn()) {
      $pdo->rollBack();
      echo json_encode(["ok" => false, "msg" => "Already played today"]);
      exit;
    }

  
    $stmt = $pdo->prepare("
      INSERT INTO game_plays (visitor_id, game_code, play_date)
      VALUES (?,?,?)
    ");
    $stmt->execute([$userId, $game, $today]);
  }


  $stmt = $pdo->prepare("UPDATE visitor SET Reward_points = Reward_points + ? WHERE ID=?");
  $stmt->execute([$pointsToAdd, $userId]);

  
  $stmt = $pdo->prepare("SELECT Reward_points FROM visitor WHERE ID=?");
  $stmt->execute([$userId]);
  $newPoints = (int)$stmt->fetchColumn();

  $pdo->commit();

  echo json_encode([
    "ok" => true,
    "msg" => "Points added",
    "new_points" => $newPoints,
    "added" => $pointsToAdd,
    "game" => $game
  ]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(["ok" => false, "msg" => "Server error: " . $e->getMessage()]);
}


