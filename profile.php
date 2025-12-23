<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT 
        u.ID, u.Name, u.Email, u.Phone,
        v.Reward_points, v.Privacy
    FROM `user` u
    LEFT JOIN `visitor` v ON v.ID = u.ID
    WHERE u.ID = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$row = $stmt->fetch();

if (!$row) {
    die("User not found.");
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$privacyText = ((int)($row["Privacy"] ?? 0) === 1) ? "Private" : "Public";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Profile</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>My Profile</h1>
      <p class="sub">View your account details & settings</p>

      <div class="profile-grid">
        <div><b>User ID:</b></div><div><?= (int)$row["ID"] ?></div>
        <div><b>Name:</b></div><div><?= h((string)$row["Name"]) ?></div>
        <div><b>Email:</b></div><div><?= h((string)$row["Email"]) ?></div>
        <div><b>Phone:</b></div><div><?= h((string)$row["Phone"]) ?></div>

        <div><b>Reward Points:</b></div><div><?= (int)($row["Reward_points"] ?? 0) ?></div>
        <div><b>Privacy:</b></div><div><?= h($privacyText) ?></div>
      </div>

      <div class="row">
        <a class="btn" href="edit_profile.php">Edit Profile</a>
        <a class="btn" href="dashboard.php">Back to Dashboard</a>
      </div>

      <div class="link" style="margin-top:12px;">
        <a href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</body>
</html>
