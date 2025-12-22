<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass  = (string)($_POST["password"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email.";
    if ($pass === "") $errors[] = "Password is required.";

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT ID, Name, Email, PasswordHash FROM `user` WHERE Email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, (string)$user["PasswordHash"])) {
            $errors[] = "Invalid email or password.";
        } else {
            $_SESSION["user_id"]    = (int)$user["ID"];
            $_SESSION["user_name"]  = (string)$user["Name"];
            $_SESSION["user_email"] = (string)$user["Email"];

            $stmt = $pdo->prepare("SELECT ID FROM `admin` WHERE ID = ? LIMIT 1");
            $stmt->execute([$_SESSION["user_id"]]);

            if ($stmt->fetch()) {
                $_SESSION["role"] = "admin";
                header("Location: admin_dashboard.php");
                exit;
            }

            $_SESSION["role"] = "visitor";
            header("Location: dashboard.php");
            exit;
        }
    }
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - TheatreFlix</title>
  <link rel="stylesheet" href="style.css?v=4"/>

</head>

<body class="auth-body">
  <div class="auth-page">
    <header class="auth-topbar">
      <div class="auth-brand">THEATRE<span>FLIX</span></div>
      <nav class="auth-nav">
        <a  href="movie_catalogue.php">Catalogue</a>
        <a class="auth-cta" href="register.php">Create account</a>
      </nav>
    </header>

    <main class="auth-layout">
      <section class="auth-left">
        <div class="auth-left-inner">
          <div class="auth-pill">WELCOME TO CINEHAUL!</div>
          <h1 class="auth-title">Pick what to watch next.</h1>
          <p class="auth-desc">
            Save movies & series to your watchlist, explore what’s playing, and build your UPNEXT list in seconds.
          </p>
        </div>


    
        <div class="auth-glow"></div>
      </section>

      <section class="auth-right">
        <div class="auth-card">
          <h2 class="auth-card-title">Sign in</h2>
          <p class="auth-card-sub">Welcome back — login to continue</p>

          <?php if ($errors): ?>
            <div class="auth-alert">
              <ul>
                <?php foreach ($errors as $e): ?>
                  <li><?= h((string)$e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="POST" action="login.php" class="auth-form" autocomplete="off">
            <label class="auth-label">Email</label>
            <input class="auth-input" type="email" name="email" required maxlength="30"
                   placeholder="you@example.com"
                   value="<?= h((string)($_POST["email"] ?? "")) ?>"/>

            <label class="auth-label">Password</label>
            <input class="auth-input" type="password" name="password" required placeholder="••••••••"/>

            <button class="auth-btn" type="submit">Login</button>
          </form>

          <div class="auth-divider"><span>OR</span></div>

          <div class="auth-footer">
            <span>New user?</span> <a href="register.php">Create account</a>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

