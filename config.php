<?php
// config.php
declare(strict_types=1);

session_start();

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
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed.");
}
