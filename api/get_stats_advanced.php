<?php
// /api/get_stats_advanced.php (CORRIGÉ)

header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';

try {
    $pdo = getDBConnection();
    
    // ... (les autres statistiques restent les mêmes)
    $total = $pdo->query("SELECT COUNT(*) FROM alertes WHERE statut = 'envoyée'")->fetchColumn();
    $stats = ['traitees' => (int)$total];

    $dailyStmt = $pdo->query("
        SELECT DATE(date_alerte) as date, COUNT(*) as count 
        FROM alertes 
        WHERE date_alerte >= CURDATE() - INTERVAL 6 DAY 
        GROUP BY DATE(date_alerte) 
        ORDER BY date ASC
    ");
    $dailyStats = $dailyStmt->fetchAll();

    $incidentTypeStmt = $pdo->query("
        SELECT incident_type as type, COUNT(*) as count 
        FROM alertes 
        WHERE incident_type IS NOT NULL AND incident_type != '' 
        GROUP BY incident_type
    ");
    $incidentTypes = $incidentTypeStmt->fetchAll();


    // --- LA CORRECTION EST ICI ---
    // On groupe par le timestamp exact de l'heure pour ne plus mélanger les jours.
    // On utilise `INTERVAL 24 HOUR` pour être plus précis que `INTERVAL 1 DAY`.
    $hourlyStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_alerte, '%Y-%m-%d %H:00:00') AS hour_timestamp, 
            COUNT(*) as count 
        FROM alertes 
        WHERE date_alerte >= NOW() - INTERVAL 24 HOUR 
        GROUP BY hour_timestamp 
        ORDER BY hour_timestamp ASC
    ");
    $hourlyStats = $hourlyStmt->fetchAll();
    // --- FIN DE LA CORRECTION ---


    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'dailyStats' => $dailyStats,
        'hourlyStats' => $hourlyStats, // On envoie les nouvelles données formatées
        'incidentTypes' => $incidentTypes
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
exit;