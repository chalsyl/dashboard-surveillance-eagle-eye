<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../config.php';
try {
    $pdo = getDBConnection();
    $filter = $_GET['filter'] ?? 'all';
    $sort = $_GET['sort'] ?? 'desc';
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $whereClause = "";
    $params = [];
    if ($filter === 'non_traitée') {
        $whereClause = "WHERE statut = :statut";
        $params[':statut'] = 'non_traitée';
    } elseif ($filter === 'traitée') {
        $whereClause = "WHERE statut = :statut";
        $params[':statut'] = 'envoyée';
    }
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM alertes " . $whereClause);
    $totalStmt->execute($params);
    $totalAlerts = $totalStmt->fetchColumn();
    $sql = "SELECT * FROM alertes " . $whereClause . " ORDER BY date_alerte " . ($sort === 'asc' ? 'ASC' : 'DESC') . " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $alertes = $stmt->fetchAll();
    $formattedAlerts = [];
    foreach ($alertes as $alerte) {
        $formattedAlerts[] = [
            'id' => $alerte['id'],
            'date_alerte' => $alerte['date_alerte'],
            'image_url' => IMAGE_BASE_URL . basename($alerte['image_path']),
            'statut' => $alerte['statut'] === 'envoyée' ? 'traitée' : 'non_traitée',
            'incident_type' => $alerte['incident_type'],
            'notes' => $alerte['notes']
        ];
    }
    echo json_encode([
        'success' => true,
        'alerts' => $formattedAlerts,
        'total' => $totalAlerts,
        'hasMore' => ($offset + count($alertes)) < $totalAlerts
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;