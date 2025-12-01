<?php
// api/delete_alerts.php
header('Content-Type: application/json');
require_once __DIR__ . '/../database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $pdo = getDBConnection();

    if ($action === 'delete_one') {
        // Supprimer UNE alerte spécifique
        $id = (int)$input['id'];
        
        // 1. Récupérer le chemin de l'image avant de supprimer la ligne
        $stmt = $pdo->prepare("SELECT image_path FROM alertes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            // Supprimer le fichier image
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
            // Supprimer la ligne BDD
            $del = $pdo->prepare("DELETE FROM alertes WHERE id = :id");
            $del->execute(['id' => $id]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete_all_treated') {
        // Supprimer TOUTES les alertes traitées ('envoyée')
        
        // 1. Récupérer toutes les images concernées
        $stmt = $pdo->query("SELECT image_path FROM alertes WHERE statut = 'envoyée'"); // 'envoyée' = Traitée dans notre logique
        $rows = $stmt->fetchAll();

        // Supprimer les fichiers
        foreach ($rows as $row) {
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
        }

        // 2. Vider la table pour ces statuts
        $pdo->query("DELETE FROM alertes WHERE statut = 'envoyée'");
        
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
