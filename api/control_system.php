<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? '';
    
    $pdo = getDBConnection();

    if ($action === 'get_status') {
        $stmt = $pdo->query("SELECT * FROM settings");
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach($raw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        echo json_encode(['success' => true, 'settings' => $settings]);

    } elseif ($action === 'toggle_setting') {
        $key = $data['key'];
        $val = $data['value'] ? '1' : '0';
        
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$val, $key]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'set_status') {
        $val = $data['value'];
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'system_status'");
        $stmt->execute([$val]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>