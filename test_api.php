<?php
require_once 'database.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test des Statuts</title>
    <style>
        body { font-family: monospace; background: #0d1b2a; color: #e0e1dd; padding: 20px; }
        .test { background: #1b263b; border: 1px solid #3a86ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #0a1320; padding: 10px; overflow-x: auto; }
        .success { color: #2ecc71; }
        .error { color: #ff4d6d; }
    </style>
</head>
<body>
    <h1>🔍 Test des Statuts dans la BDD</h1>
    
    <?php
    try {
        $pdo = getDBConnection();
        
        echo "<div class='test'>";
        echo "<h2>1. Valeurs ENUM de la colonne 'statut'</h2>";
        $stmt = $pdo->query("SHOW COLUMNS FROM alertes LIKE 'statut'");
        $column = $stmt->fetch();
        echo "<pre>";
        print_r($column);
        echo "</pre>";
        echo "</div>";
        
        echo "<div class='test'>";
        echo "<h2>2. Répartition des statuts</h2>";
        $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM alertes GROUP BY statut");
        $stats = $stmt->fetchAll();
        echo "<pre>";
        foreach ($stats as $stat) {
            echo "Statut '{$stat['statut']}': {$stat['count']} alertes\n";
        }
        echo "</pre>";
        echo "</div>";
        
        echo "<div class='test'>";
        echo "<h2>3. Dernières alertes et leurs statuts</h2>";
        $stmt = $pdo->query("SELECT id, date_alerte, statut FROM alertes ORDER BY date_alerte DESC LIMIT 10");
        $alertes = $stmt->fetchAll();
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; color: #e0e1dd;'>";
        echo "<tr><th>ID</th><th>Date</th><th>Statut (BDD)</th></tr>";
        foreach ($alertes as $alerte) {
            echo "<tr>";
            echo "<td>{$alerte['id']}</td>";
            echo "<td>{$alerte['date_alerte']}</td>";
            echo "<td><strong>{$alerte['statut']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        echo "<div class='test'>";
        echo "<h2>4. Test de mise à jour</h2>";
        echo "<p>Choisissez une alerte non traitée pour tester :</p>";
        $stmt = $pdo->query("SELECT id FROM alertes WHERE statut = 'non_traitée' LIMIT 5");
        $ids = $stmt->fetchAll();
        echo "<ul>";
        foreach ($ids as $row) {
            $testUrl = "api/update_status.php";
            echo "<li>ID: {$row['id']} 
                  <button onclick=\"testUpdate({$row['id']})\">Tester la mise à jour</button>
                  <span id='result-{$row['id']}'></span>
                  </li>";
        }
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='test error'>";
        echo "❌ Erreur: " . $e->getMessage();
        echo "</div>";
    }
    ?>
    
    <script>
    function testUpdate(id) {
        const resultSpan = document.getElementById('result-' + id);
        resultSpan.innerHTML = ' ⏳ Test en cours...';
        
        fetch('api/update_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                resultSpan.innerHTML = ' <span class="success">✅ ' + data.message + '</span>';
            } else {
                resultSpan.innerHTML = ' <span class="error">❌ ' + data.error + '</span>';
            }
        })
        .catch(e => {
            resultSpan.innerHTML = ' <span class="error">❌ Erreur: ' + e.message + '</span>';
        });
    }
    </script>
    
    <p><a href="index.php" style="color: #3a86ff;">← Retour au Dashboard</a></p>
</body>
</html>