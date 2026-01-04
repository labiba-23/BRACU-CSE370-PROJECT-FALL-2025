<?php
require_once __DIR__ . "/config.php";

$rows = [
  ["Popcorn", "SNACK", 200],
  ["Nachos", "SNACK", 250],
  ["Coke", "DRINK", 80],
  ["Water", "DRINK", 30],
  ["T-Shirt", "MERCH", 500],
  ["Mug", "MERCH", 300],
  ["Gift Card 500", "GIFT_CARD", 500],
  ["Gift Card 1000", "GIFT_CARD", 1000],
];

$stmt = $conn->prepare("INSERT INTO products (name, product_type, price, is_active) VALUES (?, ?, ?, 1)");
foreach ($rows as $r) {
    [$name,$type,$price] = $r;
    $stmt->bind_param("ssi", $name, $type, $price);
    $stmt->execute();
}
echo "Inserted products\n";
