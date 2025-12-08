<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
try {
    $pdo = getDBConnection();
    $total = $pdo->query("SELECT COUNT(*) FROM alertes")->fetchColumn();
    $nonTraitees = $pdo->query("SELECT COUNT(*) FROM alertes WHERE statut = 'non_traitée'")->fetchColumn();
    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'non_traitees' => (int)$nonTraitees
    ], JSON_THROW_ON_ERROR);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statistiques',
        'details' => $e->getMessage()
    ], JSON_THROW_ON_ERROR);
}
exit;