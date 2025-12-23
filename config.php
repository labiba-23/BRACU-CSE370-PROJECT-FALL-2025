<?php
// config.php
declare(strict_types=1);


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = "localhost";
$dbName = "theatre";
$dbUser = "root";
$dbPass = ""; 
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
   
    die("Database connection failed.");
}
