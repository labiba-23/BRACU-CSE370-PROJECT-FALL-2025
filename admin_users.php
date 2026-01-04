<?php
declare(strict_types=1);

require_once __DIR__ . "/config.php"; 


if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

try {
  
    $stmt = $pdo->prepare("SELECT `ID`, `Name`, `Email`, `Phone` FROM `user` ORDER BY `ID` ASC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin | View Users</title>
  <link rel="stylesheet" href="style.css?v=4"/>
</head>

<body class="admin-page">
  <h2>👥 Registered Users</h2>

  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($users)): ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= h((string)$u["ID"]) ?></td>
            <td><?= h((string)$u["Name"]) ?></td>
            <td><?= h((string)$u["Email"]) ?></td>
            <td><?= h((string)$u["Phone"]) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="4">No users found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <a href="admin_dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
</body>
</html>
<?php


