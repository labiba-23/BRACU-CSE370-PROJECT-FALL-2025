<?php
// register.php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

$errors = [];
$success = "";

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $pass1 = $_POST["password"] ?? "";
    $pass2 = $_POST["confirm_password"] ?? "";

    // ✅ Basic validation (matches your varchar(30) fields)
    if ($name === "" || mb_strlen($name) > 30) {
        $errors[] = "Name is required (max 30 characters).";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 30) {
        $errors[] = "Valid email is required (max 30 characters).";
    }
    if ($phone === "" || mb_strlen($phone) > 30) {
        $errors[] = "Phone is required (max 30 characters).";
    }
    if (strlen($pass1) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($pass1 !== $pass2) {
        $errors[] = "Passwords do not match.";
    }

    // ✅ Check if email already exists
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT ID FROM `user` WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered. Please login.";
        }
    }

    // ✅ Insert into user + visitor + subscription
    if (!$errors) {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            // 1) Insert into user table
            $stmt = $pdo->prepare("
                INSERT INTO `user` (Name, Email, Phone, PasswordHash)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $phone, $hash]);

            // 2) Get the new user ID (int(8) in your DB)
            $newUserId = (int)$pdo->lastInsertId();

            // 3) Insert into visitor table (profile settings)
            // Privacy: 0 = Public, 1 = Private
            $stmt = $pdo->prepare("
                INSERT INTO `visitor` (ID, Reward_points, Privacy)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$newUserId, 0, 0]);

            // 4) Insert into subscription table (watchlist storage)
            // S_ID is int(6), generate a 6-digit number
            $sId = random_int(100000, 999999);

            $stmt = $pdo->prepare("
                INSERT INTO `subscription` (S_ID, Subtitles, Wishlist, Visitor_ID)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$sId, "", "", $newUserId]);

            $pdo->commit();
            $success = "Registration successful! You can login now.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - Theatre</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>Create Account</h1>
      <p class="sub">Movie theatre project — register as a user</p>

      <?php if ($errors): ?>
        <div class="msg error">
          <ul style="margin: 0; padding-left: 18px;">
            <?php foreach ($errors as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="msg ok"><?= h($success) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php" autocomplete="off">
        <label>Full Name</label>
        <input type="text" name="name" maxlength="30" required value="<?= h($_POST["name"] ?? "") ?>"/>

        <label>Email</label>
        <input type="email" name="email" maxlength="30" required value="<?= h($_POST["email"] ?? "") ?>"/>

        <label>Phone</label>
        <input type="text" name="phone" maxlength="30" required value="<?= h($_POST["phone"] ?? "") ?>"/>

        <label>Password</label>
        <input type="password" name="password" required minlength="6"/>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required minlength="6"/>

        <button type="submit">Register</button>
      </form>

      <div class="link">
        Already have an account? <a href="login.php">Login</a>
      </div>
    </div>
  </div>
</body>
</html>

