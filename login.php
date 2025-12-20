<?php
// login.php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass  = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email.";
    if ($pass === "") $errors[] = "Password is required.";

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT ID, Name, Email, PasswordHash FROM `user` WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user["PasswordHash"])) {
            $errors[] = "Invalid email or password.";
        } else {
            // logged in
            $_SESSION["user_id"] = (int)$user["ID"];   // int(8) in DB
            $_SESSION["user_name"] = $user["Name"];
            $_SESSION["user_email"] = $user["Email"];

            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Theatre</title>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>Login</h1>
      <p class="sub">Welcome back — login to continue</p>

      <?php if ($errors): ?>
        <div class="msg error">
          <ul style="margin: 0; padding-left: 18px;">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" autocomplete="off">
        <label>Email</label>
        <input type="email" name="email" maxlength="30" required value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"/>

        <label>Password</label>
        <input type="password" name="password" required/>

        <button type="submit">Login</button>
      </form>

      <div class="link">
        New user? <a href="register.php">Create account</a>
      </div>
    </div>
  </div>
</body>
</html>
