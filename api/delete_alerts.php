<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $pdo = getDBConnection();
    if ($action === 'delete_one') {
        $id = (int)$input['id'];
        $stmt = $pdo->prepare("SELECT image_path FROM alertes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
            $del = $pdo->prepare("DELETE FROM alertes WHERE id = :id");
            $del->execute(['id' => $id]);
        }
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_all_treated') {
        $stmt = $pdo->query("SELECT image_path FROM alertes WHERE statut = 'envoyée'");
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
        }
        $pdo->query("DELETE FROM alertes WHERE statut = 'envoyée'");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>