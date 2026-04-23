<?php
// Central USDT rate helper - sell_rate_1 as default rate
if (!isset($pdo)) require __DIR__ . '/db.php';

function getUsdtRate($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='usdt_sell_rate_1' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? floatval($row['setting_value']) : 89.80;
    } catch (Exception $e) {
        return 89.80;
    }
}

$usdtRate = getUsdtRate($pdo);
?>
