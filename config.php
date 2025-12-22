<?php
// config.php
declare(strict_types=1);

// Start session ONLY if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = "localhost";
$dbName = "theatre";
$dbUser = "root";
$dbPass = ""; // change if needed

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // In production, don't show DB details
    die("Database connection failed.");
}
