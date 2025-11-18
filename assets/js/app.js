// ===== VARIABLES GLOBALES =====
let currentFilter = 'all';
let currentSort = 'desc';
let currentOffset = 0;
let isLoading = false;
let hasMore = true;

// Graphiques
let dailyChart, hourlyChart, incidentTypeChart;

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', function() {
    initClock();
    initFilters();
    initLightbox();
    initIncidentModal();
    initLoadMore();
    initCharts();
    startAutoRefresh();
});

// ===== HORLOGE EN TEMPS RÉEL =====
function initClock() {
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        document.getElementById('currentTime').textContent = timeString;
    }
    updateClock();
    setInterval(updateClock, 1000);
}

// ===== AUTO-REFRESH DES STATS (toutes les 10 secondes) =====
function startAutoRefresh() {
    setInterval(updateStats, 10000);
    setInterval(updateCharts, 30000); // Graphiques toutes les 30s
}

function updateStats() {
    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_stats.php';
    
    fetch(apiPath)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                animateCounter('totalAlertes', data.total);
                animateCounter('alertesNonTraitees', data.non_traitees);
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour des stats:', error));
}

function animateCounter(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseInt(element.textContent);
    
    if (currentValue !== newValue) {
        element.style.transform = 'scale(1.2)';
        element.style.transition = 'transform 0.3s ease';
        
        setTimeout(() => {
            element.textContent = newValue;
            element.style.transform = 'scale(1)';
        }, 150);
    }
}

// ===== SYSTÈME DE FILTRES =====
function initFilters() {
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('[data-filter]').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            currentFilter = this.getAttribute('data-filter');
            currentOffset = 0;
            hasMore = true;
            applyFilters(true);
        });
    });
    
    document.querySelectorAll('[data-sort]').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('[data-sort]').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            currentSort = this.getAttribute('data-sort');
            currentOffset = 0;
            hasMore = true;
            applyFilters(true);
        });
    });
}

function applyFilters(reset = false) {
    if (isLoading) return;
    isLoading = true;
    
    if (reset) currentOffset = 0;
    
    const params = new URLSearchParams({
        filter: currentFilter,
        sort: currentSort,
        offset: currentOffset,
        limit: 20
    });
    
    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_alerts.php';
    
    fetch(`${apiPath}?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (reset) {
                    renderAlerts(data.alerts);
                } else {
                    appendAlerts(data.alerts);
                }
                
                hasMore = data.hasMore;
                updateLoadMoreButton();
                
                document.getElementById('alertsCount').textContent = `${data.total} alertes au total`;
            }
            isLoading = false;
        })
        .catch(error => {
            console.error('Erreur lors du filtrage:', error);
            isLoading = false;
        });
}

function renderAlerts(alerts) {
    const container = document.getElementById('alertsContainer');
    
    if (alerts.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-inbox"></i>
                <h3>Aucune alerte correspondante</h3>
                <p>Essayez de modifier vos filtres.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = alerts.map(alert => createAlertCard(alert)).join('');
    
    initLightbox();
    initIncidentModal();
}

function appendAlerts(alerts) {
    const container = document.getElementById('alertsContainer');
    const html = alerts.map(alert => createAlertCard(alert)).join('');
    container.insertAdjacentHTML('beforeend', html);
    
    initLightbox();
    initIncidentModal();
}

function createAlertCard(alert) {
    const isTraitee = alert.statut === 'traitée';
    const badgeClass = isTraitee ? 'treated' : 'untreated';
    const badgeIcon = isTraitee ? 'fa-check-circle' : 'fa-exclamation-circle';
    const badgeText = isTraitee ? 'TRAITÉE' : 'NON TRAITÉE';
    
    const date = new Date(alert.date_alerte);
    const dateFormatted = date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    let actionContent = '';
    if (!isTraitee) {
        actionContent = `
            <button class="btn-mark-treated" data-id="${alert.id}">
                <i class="fas fa-clipboard-check"></i>
                Traiter l'Incident
            </button>
        `;
    } else if (alert.incident_type || alert.notes) {
        actionContent = `
            <div class="incident-info">
                ${alert.incident_type ? `
                    <div class="incident-type">
                        <i class="fas fa-tag"></i>
                        <strong>${alert.incident_type}</strong>
                    </div>
                ` : ''}
                ${alert.notes ? `
                    <div class="incident-notes">
                        <i class="fas fa-comment"></i>
                        ${alert.notes}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    return `
        <div class="alert-card" data-id="${alert.id}" data-status="${alert.statut}">
            <div class="alert-image-wrapper">
                <img src="${alert.image_url}" alt="Capture" class="alert-image" data-large="${alert.image_url}">
                <div class="alert-overlay">
                    <button class="btn-zoom" title="Agrandir">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>
            </div>
            <div class="alert-content">
                <div class="alert-header">
                    <span class="alert-badge alert-badge-${badgeClass}">
                        <i class="fas ${badgeIcon}"></i>
                        ${badgeText}
                    </span>
                </div>
                <div class="alert-info">
                    <div class="alert-date">
                        <i class="far fa-clock"></i>
                        ${dateFormatted}
                    </div>
                    <div class="alert-id">ID: ${alert.id}</div>
                </div>
                ${actionContent}
            </div>
        </div>
    `;
}

// ===== LIGHTBOX =====
function initLightbox() {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const closeBtn = document.querySelector('.lightbox-close');
    
    document.querySelectorAll('.btn-zoom').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const img = this.closest('.alert-image-wrapper').querySelector('.alert-image');
            lightboxImage.src = img.getAttribute('data-large');
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            closeLightbox();
        }
    });
}

// ===== INCIDENT MODAL =====
function initIncidentModal() {
    document.querySelectorAll('.btn-mark-treated').forEach(button => {
        button.addEventListener('click', function() {
            const alertId = this.getAttribute('data-id');
            openIncidentModal(alertId);
        });
    });
}

function openIncidentModal(alertId) {
    document.getElementById('incidentAlertId').value = alertId;
    document.getElementById('incidentForm').reset();
    
    const modal = new bootstrap.Modal(document.getElementById('incidentModal'));
    modal.show();
}

document.getElementById('submitIncidentBtn').addEventListener('click', function() {
    const form = document.getElementById('incidentForm');
    const formData = new FormData(form);
    
    const incidentType = formData.get('incident_type');
    if (!incidentType) {
        showToast('Veuillez sélectionner un type d\'incident', true);
        return;
    }
    
    const alertId = document.getElementById('incidentAlertId').value;
    const notes = document.getElementById('incidentNotes').value;
    
    submitIncident(alertId, incidentType, notes);
});

function submitIncident(alertId, incidentType, notes) {
    const btn = document.getElementById('submitIncidentBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
    
    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/update_status.php';
    
    fetch(apiPath, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            id: alertId,
            incident_type: incidentType,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('incidentModal'));
            modal.hide();
            
            updateCardAfterTreatment(alertId, incidentType, notes);
            updateStats();
            updateCharts();
            showToast('Incident enregistré avec succès !');
        } else {
            showToast(data.error || 'Erreur lors de l\'enregistrement', true);
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer le Rapport';
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('Erreur de connexion', true);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer le Rapport';
    });
}

function updateCardAfterTreatment(alertId, incidentType, notes) {
    const card = document.querySelector(`.alert-card[data-id="${alertId}"]`);
    if (!card) return;
    
    const badge = card.querySelector('.alert-badge');
    badge.className = 'alert-badge alert-badge-treated';
    badge.innerHTML = '<i class="fas fa-check-circle"></i> TRAITÉE';
    
    card.setAttribute('data-status', 'traitée');
    
    const button = card.querySelector('.btn-mark-treated');
    if (button) {
        button.outerHTML = `
            <div class="incident-info">
                <div class="incident-type">
                    <i class="fas fa-tag"></i>
                    <strong>${incidentType}</strong>
                </div>
                ${notes ? `
                    <div class="incident-notes">
                        <i class="fas fa-comment"></i>
                        ${notes}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    card.style.animation = 'fadeIn 0.5s ease';
}

// ===== LOAD MORE =====
function initLoadMore() {
    const btn = document.getElementById('loadMoreBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            if (!isLoading && hasMore) {
                currentOffset += 20;
                applyFilters(false);
            }
        });
    }
}

function updateLoadMoreButton() {
    const container = document.getElementById('loadMoreContainer');
    if (!container) return;
    
    if (hasMore) {
        container.style.display = 'flex';
    } else {
        container.style.display = 'none';
    }
}

// ===== CHARTS =====
function initCharts() {
    updateCharts();
}

function updateCharts() {
    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_stats_advanced.php';
    
    fetch(apiPath)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDailyChart(data.dailyStats);
                updateHourlyChart(data.hourlyStats);
                updateIncidentTypeChart(data.incidentTypes);
                
                // Mettre à jour les KPIs
                if (data.stats) {
                    const traitees = data.stats.traitees || 0;
                    const elem = document.getElementById('alertesTraitees');
                    if (elem) elem.textContent = traitees;
                }
            }
        })
        .catch(error => console.error('Erreur lors de la mise à jour des graphiques:', error));
}

function updateDailyChart(data) {
    const ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    
    const labels = data.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
    });
    
    const values = data.map(d => d.count);
    
    if (dailyChart) {
        dailyChart.data.labels = labels;
        dailyChart.data.datasets[0].data = values;
        dailyChart.update();
    } else {
        dailyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nombre d\'alertes',
                    data: values,
                    backgroundColor: 'rgba(58, 134, 255, 0.7)',
                    borderColor: 'rgba(58, 134, 255, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#778da9' },
                        grid: { color: 'rgba(119, 141, 169, 0.1)' }
                    },
                    x: {
                        ticks: { color: '#778da9' },
                        grid: { color: 'rgba(119, 141, 169, 0.1)' }
                    }
                }
            }
        });
    }
}


function updateHourlyChart(data) { // `data` est maintenant [{hour_timestamp: "...", count: ...}]
    const ctx = document.getElementById('hourlyChart');
    if (!ctx) return;

    // --- 1. Préparer la timeline des 24 dernières heures ---
    const labels = [];
    const values = [];
    const now = new Date();

    for (let i = 23; i >= 0; i--) {
        const date = new Date(now.getTime() - i * 60 * 60 * 1000);
        const hour = date.getHours();
        labels.push(`${hour}h`);
        values.push(0); // On initialise toutes les valeurs à 0
    }

    // --- 2. Créer un dictionnaire pour retrouver les données facilement ---
    const dataMap = new Map();
    data.forEach(d => {
        const hour = new Date(d.hour_timestamp).getHours();
        dataMap.set(hour, d.count);
    });

    // --- 3. Remplir la timeline avec les vraies données ---
    for (let i = 0; i < labels.length; i++) {
        const hourLabel = parseInt(labels[i]); // ex: "12h" -> 12
        if (dataMap.has(hourLabel)) {
            values[i] = dataMap.get(hourLabel);
        }
    }
    
    // --- 4. Mettre à jour le graphique (le code ici ne change pas) ---
    if (hourlyChart) {
        hourlyChart.data.labels = labels;
        hourlyChart.data.datasets[0].data = values;
        hourlyChart.update();
    } else {
        hourlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Alertes par heure',
                    data: values,
                    backgroundColor: 'rgba(58, 134, 255, 0.2)',
                    borderColor: 'rgba(58, 134, 255, 1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(58, 134, 255, 1)',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#778da9', stepSize: 5 },
                        grid: { color: 'rgba(119, 141, 169, 0.1)' }
                    },
                    x: {
                        ticks: { color: '#778da9' },
                        grid: { display: false } // Grille verticale moins visible pour plus de clarté
                    }
                }
            }
        });
    }
}

const incidentColorMap = {
    'Fausse Alerte':       'rgba(46, 204, 113, 0.7)',  // Vert
    'Activité Suspecte':   'rgba(255, 206, 86, 0.7)',  // Jaune
    'Intrusion Confirmée': 'rgba(255, 77, 109, 0.8)',  // Rouge Vif
    'Test Système':        'rgba(58, 134, 255, 0.7)',  // Bleu
    'default':             'rgba(119, 141, 169, 0.7)'   // Gris pour les cas imprévus
};

function updateIncidentTypeChart(data) {
    const ctx = document.getElementById('incidentTypeChart');
    if (!ctx) return;
    
    // 1. On extrait les labels et les valeurs comme avant
    const labels = data.map(d => d.type);
    const values = data.map(d => d.count);
    
    // 2. On génère le tableau de couleurs DANS LE BON ORDRE
    // Pour chaque label reçu, on va chercher sa couleur dans notre carte.
    const backgroundColors = labels.map(label => incidentColorMap[label] || incidentColorMap.default);
    
    if (incidentTypeChart) {
        incidentTypeChart.data.labels = labels;
        incidentTypeChart.data.datasets[0].data = values;
        // On met à jour les couleurs aussi !
        incidentTypeChart.data.datasets[0].backgroundColor = backgroundColors;
        incidentTypeChart.update();
    } else {
        incidentTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    // On utilise notre tableau de couleurs généré
                    backgroundColor: backgroundColors,
                    borderColor: '#1b263b',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { 
                            color: '#e0e1dd', // Texte de la légende en blanc cassé
                            font: { size: 14 }
                        }
                    },
                    tooltip: {
                        bodyFont: { size: 14 },
                        titleFont: { size: 16 }
                    }
                }
            }
        });
    }
}

// ===== NOTIFICATIONS TOAST =====
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    
    if (isError) {
        toast.style.background = 'linear-gradient(135deg, #ff4d6d 0%, #e63946 100%)';
    } else {
        toast.style.background = 'linear-gradient(135deg, #2ecc71 0%, #27ae60 100%)';
    }
    
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}