<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION["user_id"];

$pid = (int)($_POST["pid"] ?? 0);
$otp = trim((string)($_POST["otp"] ?? ""));

if ($pid <= 0) die("Invalid purchase.");

if ($otp !== "123456") {
  header("Location: payment_demo.php?pid={$pid}&err=Invalid OTP (use 123456)");
  exit;
}


$stmt = $pdo->prepare("
  SELECT paid_amount
  FROM purchase
  WHERE P_ID = ? AND Visitor_ID = ?
  LIMIT 1
");
$stmt->execute([$pid, $userId]);
$paid_amount = $stmt->fetchColumn();

if ($paid_amount === false) {
  die("Purchase not found.");
}

if ((int)$paid_amount > 0) {
 
  $stmt = $pdo->prepare("UPDATE purchase SET paid_amount = 0 WHERE P_ID = ? AND Visitor_ID = ?");
  $stmt->execute([$pid, $userId]);
}

header("Location: purchase_success.php?pid=" . $pid);
exit;

