<?php
// Central USDT rate helper - settings table se rate fetch karo
if (!isset($pdo)) require __DIR__ . '/db.php';

function getUsdtRate($pdo) {
    try {
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_group='rates' AND setting_key='usdt_inr_rate' LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        return $row ? floatval($row['setting_value']) : 89.80;
    } catch (Exception $e) {
        return 89.80;
    }
}

$usdtRate = getUsdtRate($pdo);
?>
