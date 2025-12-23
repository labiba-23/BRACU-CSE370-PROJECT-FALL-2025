<?php
declare(strict_types=1);
require_once __DIR__ . "/config.php";

session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Logged Out - TheatreFlix</title>
  <link rel="stylesheet" href="style.css"/>
  <meta http-equiv="refresh" content="5;url=login.php">
</head>
<body class="auth-body">
  <header class="auth-topbar">
    <div class="auth-brand">THEATRE<span>FLIX</span></div>
    <nav class="auth-nav">
      <a class="auth-cta" href="login.php">Sign in</a>
    </nav>
  </header>

  <main class="auth-layout auth-layout-single">
    <section class="auth-right">
      <div class="auth-card">
        <h2 class="auth-card-title">You’re signed out </h2>
        <p class="auth-card-sub">Redirecting to login…</p>
        <a class="auth-btn auth-btn-link" href="login.php">Go to Login</a>
      </div>
    </section>
  </main>
</body>
</html>


