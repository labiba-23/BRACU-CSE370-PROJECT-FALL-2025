<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$errors = [];
$success = "";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$stmt = $pdo->prepare("
    SELECT u.Name, u.Email, u.Phone, COALESCE(v.Privacy, 0) AS Privacy
    FROM `user` u
    LEFT JOIN `visitor` v ON v.ID = u.ID
    WHERE u.ID = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$current = $stmt->fetch();

if (!$current) die("User not found.");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $privacy = (int)($_POST["privacy"] ?? 0);
    $privacy = ($privacy === 1) ? 1 : 0;

    if ($name === "" || mb_strlen($name) > 30) $errors[] = "Name is required (max 30).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 30) $errors[] = "Valid email required (max 30).";
    if ($phone === "" || mb_strlen($phone) > 30) $errors[] = "Phone is required (max 30).";

    // prevent changing to an email that belongs to another user
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT ID FROM `user` WHERE Email = ? AND ID <> ? LIMIT 1");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) $errors[] = "That email is already used by another account.";
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            // update user table
            $stmt = $pdo->prepare("UPDATE `user` SET Name = ?, Email = ?, Phone = ? WHERE ID = ?");
            $stmt->execute([$name, $email, $phone, $userId]);

            // ensure visitor row exists, then update privacy
            $stmt = $pdo->prepare("SELECT ID FROM `visitor` WHERE ID = ? LIMIT 1");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO `visitor` (ID, Reward_points, Privacy) VALUES (?, ?, ?)");
                $stmt->execute([$userId, 0, $privacy]);
            } else {
                $stmt = $pdo->prepare("UPDATE `visitor` SET Privacy = ? WHERE ID = ?");
                $stmt->execute([$privacy, $userId]);
            }

            $pdo->commit();

            // refresh session values
            $_SESSION["user_name"] = $name;
            $_SESSION["user_email"] = $email;

            $success = "Profile updated successfully.";
            $current = ["Name"=>$name,"Email"=>$email,"Phone"=>$phone,"Privacy"=>$privacy];
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Update failed. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Profile</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>Edit Profile</h1>
      <p class="sub">Update your details & privacy</p>

      <?php if ($errors): ?>
        <div class="msg error">
          <ul style="margin:0; padding-left:18px;">
            <?php foreach ($errors as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="msg ok"><?= h($success) ?></div>
      <?php endif; ?>

      <form method="POST" action="edit_profile.php" autocomplete="off">
        <label>Name</label>
        <input type="text" name="name" maxlength="30" required value="<?= h((string)$current["Name"]) ?>"/>

        <label>Email</label>
        <input type="email" name="email" maxlength="30" required value="<?= h((string)$current["Email"]) ?>"/>

        <label>Phone</label>
        <input type="text" name="phone" maxlength="30" required value="<?= h((string)$current["Phone"]) ?>"/>

        <label>Privacy</label>
        <select name="privacy" class="select">
          <option value="0" <?= ((int)$current["Privacy"] === 0) ? "selected" : "" ?>>Public</option>
          <option value="1" <?= ((int)$current["Privacy"] === 1) ? "selected" : "" ?>>Private</option>
        </select>

        <button type="submit">Save Changes</button>
      </form>

      <div class="link">
        <a href="profile.php">Back to Profile</a>
      </div>
    </div>
  </div>
</body>
</html>
