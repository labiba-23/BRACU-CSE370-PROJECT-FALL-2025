<?php
declare(strict_types=1);

function isPremium(PDO $pdo, int $userId): bool {
   
    $stmt = $pdo->prepare("SELECT * FROM visitor WHERE ID = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) return false;

   
    $flagCols = ["is_premium", "premium", "premium_user", "premium_status"];
    $flagVal = null;

    foreach ($flagCols as $c) {
        if (array_key_exists($c, $row)) {
            $flagVal = (int)$row[$c];
            break;
        }
    }

    $dateCols = ["premium_until", "premium_expiry", "premium_expires_at", "premium_end"];
    $until = null;

    foreach ($dateCols as $c) {
        if (array_key_exists($c, $row)) {
            $until = (string)$row[$c];
            break;
        }
    }

    if ($flagVal === null) return false;


    if ($flagVal !== 1) return false;

    
    if ($until !== null && $until !== "") {
    
        $expiryTime = strtotime($until);
        if ($expiryTime === false) return true; 
        return $expiryTime >= time();
    }

   
    return true;
}

