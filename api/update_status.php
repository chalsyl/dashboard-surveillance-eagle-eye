<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID d\'alerte invalide'
        ], JSON_THROW_ON_ERROR);
        exit;
    }
    if (!isset($data['incident_type']) || empty($data['incident_type'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Type d\'incident requis'
        ], JSON_THROW_ON_ERROR);
        exit;
    }
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        UPDATE alertes 
        SET statut = 'envoyée',
            incident_type = :incident_type,
            notes = :notes,
            treated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => (int)$data['id'],
        'incident_type' => $data['incident_type'],
        'notes' => isset($data['notes']) ? $data['notes'] : null
    ]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Incident enregistré avec succès'
        ], JSON_THROW_ON_ERROR);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Alerte non trouvée'
        ], JSON_THROW_ON_ERROR);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la mise à jour',
        'details' => $e->getMessage()
    ], JSON_THROW_ON_ERROR);
}
exit;