<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'database.php';
$pdo = getDBConnection();

$totalAlertes = $pdo->query("SELECT COUNT(*) FROM alertes")->fetchColumn();
$alertesNonTraitees = $pdo->query("SELECT COUNT(*) FROM alertes WHERE statut = 'non_traitée'")->fetchColumn();

$stmt = $pdo->query("SELECT * FROM alertes ORDER BY date_alerte DESC LIMIT 20");
$alertes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eagle Eye - Command Center</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="dashboard-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
            <div class="brand">
                    <i class="fas fa-eye brand-icon"></i>
                    <div>
                        <h1 class="brand-title">EAGLE EYE</h1>
                        <div class="brand-subtitle">SURVEILLANCE SYSTEM</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-4">
                    
                    <div class="header-info d-none d-md-flex">
                        <span class="status-indicator">
                            <span class="pulse-dot"></span>
                            <span>ONLINE</span>
                        </span>
                        <span class="current-time" id="currentTime"></span>
                    </div>

                    <a href="control.php" class="btn-command">
                        <i class="fas fa-gamepad"></i>
                        <span>COMMANDER</span>
                    </a>
                    
                    <a href="logout.php" class="btn-filter" style="border-color: var(--neon-red); color: var(--neon-red);" title="Déconnexion">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="container">
            
            <section class="kpi-section">
                <div class="row g-4">
                    <div class="col-md-6">
                <div class="kpi-card">
                            <div class="kpi-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="kpi-content">
                                <h3 class="kpi-label">Total des Alertes</h3>
                                <div class="kpi-value" id="totalAlertes"><?= $totalAlertes ?></div>
                            </div>
                            <div class="kpi-decoration"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="kpi-card kpi-card-alert">
                             <div class="kpi-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="kpi-content">
                                <h3 class="kpi-label">Alertes Non Traitées</h3>
                                <div class="kpi-value kpi-value-alert" id="alertesNonTraitees"><?= $alertesNonTraitees ?></div> </div>
                            <div class="kpi-decoration kpi-decoration-alert"></div>
                    </div>
                    </div>
                </div>
            </section>

            <section class="filters-section">
                <div class="filters-card">
                    <div class="filters-header">
                        <i class="fas fa-filter"></i>
                        <span>Filtres & Tri</span>
                    </div>
                    <div class="filters-controls">
                        <div class="filter-group">
                            <label>Statut :</label>
                            <div class="btn-group-filter" role="group">
                                <button type="button" class="btn-filter active" data-filter="all">
                                    <i class="fas fa-list"></i> Toutes
                                </button>
                                <button type="button" class="btn-filter" data-filter="non_traitée">
                                    <i class="fas fa-bell"></i> Non Traitées
                                </button>
                                <button type="button" class="btn-filter" data-filter="traitée">
                                    <i class="fas fa-check-circle"></i> Traitées
                                </button>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Tri par date :</label>
                            <div class="btn-group-filter" role="group">
                                <button type="button" class="btn-filter active" data-sort="desc">
                                    <i class="fas fa-sort-amount-down"></i> Plus récent
                                </button>
                                <button type="button" class="btn-filter" data-sort="asc">
                                    <i class="fas fa-sort-amount-up"></i> Plus ancien
                                </button>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label>Actions :</label>
                            <div class="btn-group-filter" role="group">
                                <button type="button" class="btn-filter" id="btnPurgeAll" style="border-color: var(--neon-red); color: var(--neon-red);">
                                <i class="fas fa-trash-alt"></i> Purger Traitées
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="alerts-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-stream"></i>
                        Flux d'Événements
                    </h2>
                    <span class="alerts-count" id="alertsCount"><?= count($alertes) ?> alertes affichées</span>
                </div>
                
                <div id="alertsContainer" class="alerts-grid">
                    <?php foreach ($alertes as $alerte): ?>
                        <?php
                        $imageName = basename($alerte['image_path']);
                        $imageUrl = IMAGE_BASE_URL . $imageName;
                        
                        $date = new DateTime($alerte['date_alerte']);
                        $dateFormatted = $date->format('d/m/Y à H:i:s');
                        
                        $isTraitee = $alerte['statut'] === 'envoyée';
                        ?>
                        <div class="alert-card" data-id="<?= $alerte['id'] ?>" data-status="<?= $alerte['statut'] ?>">
                            <div class="alert-image-wrapper">
                                <img src="<?= $imageUrl ?>" alt="Capture" class="alert-image" data-large="<?= $imageUrl ?>">
                                <div class="alert-overlay">
                                <button class="btn-zoom" title="Agrandir">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="alert-content">
                                <div class="alert-header">
                                    <span class="alert-badge alert-badge-<?= $isTraitee ? 'treated' : 'untreated' ?>">
                                        <i class="fas <?= $isTraitee ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                                        <?= $isTraitee ? 'TRAITÉE' : 'NON TRAITÉE' ?>
                                    </span>
                                </div>
                                <div class="alert-info">
                                    <div class="alert-date">
                                        <i class="far fa-clock"></i>
                                        <?= $dateFormatted ?>
                                    </div>
                                    <div class="alert-id">ID: <?= $alerte['id'] ?></div>
                                </div>
                                <?php if (!$isTraitee): ?>
                                <button class="btn-mark-treated" data-id="<?= $alerte['id'] ?>">
                                    <i class="fas fa-clipboard-check"></i>
                                    Traiter l'Incident
                                </button>
                            <?php else: ?>
                                <?php if (!empty($alerte['incident_type']) || !empty($alerte['notes'])): ?>
                                    <div class="incident-info">
                                        <?php if (!empty($alerte['incident_type'])): ?>
                                            <div class="incident-type">
                                                <i class="fas fa-tag"></i>
                                                <strong><?= htmlspecialchars($alerte['incident_type']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($alerte['notes'])): ?>
                                            <div class="incident-notes">
                                                <i class="fas fa-comment"></i>
                                                <?= nl2br(htmlspecialchars($alerte['notes'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="loadMoreContainer" class="d-flex justify-content-center mt-4">
                    <button id="loadMoreBtn" class="btn-load-more">
                        <i class="fas fa-plus"></i> Afficher plus d'alertes
                    </button>
                </div>

                <?php if (empty($alertes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucune alerte enregistrée</h3>
                        <p>Le système est en attente de détection de mouvement.</p>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </main>

<section class="stats-section">
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            Analyse des Données
        </h2>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="chart-card">
                <h3>Alertes par Heure (Dernières 24h)</h3>
                <div class="chart-container">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <h3>Types d'Incidents</h3>
                <div class="chart-container">
                    <canvas id="incidentTypeChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="chart-card">
                <h3>Activité sur les 7 derniers jours</h3>
                <div class="chart-container">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

    <div id="lightbox" class="lightbox">
        <button class="lightbox-close">
            <i class="fas fa-times"></i>
        </button>
        <div class="lightbox-content">
            <img id="lightboxImage" src="" alt="Image agrandie">
        </div>
    </div>

<div class="modal fade" id="incidentModal" tabindex="-1" aria-labelledby="incidentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-white" style="background: var(--bg-card); border: 1px solid var(--border-color);">
            <div class="modal-header" style="border-bottom-color: var(--border-color);">
                <h5 class="modal-title" id="incidentModalLabel">
                    <i class="fas fa-clipboard-check"></i> Traiter l'Incident
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="incidentForm">
                    <input type="hidden" id="incidentAlertId" name="alert_id">
                    <div class="mb-3">
                        <label for="incidentType" class="form-label">Type d'incident *</label>
                        <select class="form-select" id="incidentType" name="incident_type" required style="background-color: var(--bg-dark); color: var(--text-primary); border-color: var(--border-color);">
                            <option value="" selected disabled>-- Choisir une qualification --</option>
                            <option value="Fausse Alerte">Fausse Alerte (animal, météo...)</option>
                            <option value="Activité Suspecte">Activité Suspecte</option>
                            <option value="Intrusion Confirmée">Intrusion Confirmée</option>
                            <option value="Test Système">Test Système</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="incidentNotes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" id="incidentNotes" name="notes" rows="3" placeholder="Ex: Véhicule suspect aperçu..." style="background-color: var(--bg-dark); color: var(--text-primary); border-color: var(--border-color);"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top-color: var(--border-color);">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="submitIncidentBtn">
                    <i class="fas fa-save"></i> Enregistrer le Rapport
                </button>
            </div>
        </div>
    </div>
</div>

    <div id="toast" class="toast-notification">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
