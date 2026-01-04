<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION["user_id"];

$giftCardId = (int)($_POST["gift_card_id"] ?? 0);
$toVisitorId = (int)($_POST["to_visitor_id"] ?? 0);

if ($giftCardId <= 0 || $toVisitorId <= 0) {
  header("Location: gift_cards.php?err=" . urlencode("Invalid request."));
  exit;
}
if ($toVisitorId === $userId) {
  header("Location: gift_cards.php?err=" . urlencode("You can't gift to yourself."));
  exit;
}

try {
  $pdo->beginTransaction();

  
  $stmt = $pdo->prepare("SELECT 1 FROM can_add WHERE Visitor1_ID=? AND Visitor2_ID=? LIMIT 1 FOR UPDATE");
  $stmt->execute([$userId, $toVisitorId]);
  if (!$stmt->fetchColumn()) {
    throw new Exception("You can only gift to friends.");
  }


  $stmt = $pdo->prepare("
    SELECT gift_card_id, issued_to_visitor_id, remaining_value
    FROM gift_cards
    WHERE gift_card_id = ?
    FOR UPDATE
  ");
  $stmt->execute([$giftCardId]);
  $card = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$card) throw new Exception("Gift card not found.");
  if ((int)$card["issued_to_visitor_id"] !== $userId) throw new Exception("This card is not yours.");
  if ((int)$card["remaining_value"] <= 0) throw new Exception("This card has no remaining value.");

  $stmt = $pdo->prepare("UPDATE gift_cards SET issued_to_visitor_id=? WHERE gift_card_id=?");
  $stmt->execute([$toVisitorId, $giftCardId]);

  $stmt = $pdo->prepare("
    INSERT INTO gift_card_transfers (gift_card_id, from_visitor_id, to_visitor_id)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$giftCardId, $userId, $toVisitorId]);

  $pdo->commit();
  header("Location: gift_cards.php?msg=" . urlencode("Gift card sent successfully!"));
  exit;

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header("Location: gift_cards.php?err=" . urlencode($e->getMessage()));
  exit;
}
