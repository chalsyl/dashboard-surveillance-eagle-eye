<?php
require_once '../database.php';
header('Content-Type: application/json');
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 50");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($logs);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>