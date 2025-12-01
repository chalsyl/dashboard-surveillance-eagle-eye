// ==========================================
// EAGLE EYE - MOTEUR JS PRINCIPAL (V3.1 FINAL)
// ==========================================

// ===== VARIABLES GLOBALES =====
let currentFilter = 'all';
let currentSort = 'desc';
let currentOffset = 0;
let isLoading = false;
let hasMore = true;

// Graphiques
let dailyChart, hourlyChart, incidentTypeChart;

const incidentColorMap = {
    'Fausse Alerte':       'rgba(16, 185, 129, 0.8)', // Vert
    'Activité Suspecte':   'rgba(245, 158, 11, 0.8)', // Orange
    'Intrusion Confirmée': 'rgba(239, 68, 68, 0.8)',  // Rouge
    'Test Système':        'rgba(59, 130, 246, 0.8)', // Bleu
    'default':             'rgba(148, 163, 184, 0.5)' // Gris
};

// ===== INITIALISATION =====
document.addEventListener('DOMContentLoaded', function() {
    initClock();
    initFilters();
    initLightbox();
    
    // C'était la fonction manquante !
    initIncidentModal(); 
    
    initLoadMore();
    initCharts();
    initSpotlight();
    
    // Lancement des tâches de fond
    startAutoRefresh();
});

// ===== 1. LOGIQUE DE RAFRAÎCHISSEMENT (AUTO-REFRESH) =====
function startAutoRefresh() {
    setInterval(updateStats, 5000);
    setInterval(updateCharts, 30000);
    setInterval(checkNewAlerts, 4000);
}

function checkNewAlerts() {
    if (isLoading || currentSort !== 'desc' || currentFilter === 'traitée') return;

    let maxId = 0;
    document.querySelectorAll('.alert-card').forEach(card => {
        const id = parseInt(card.getAttribute('data-id'));
        if (id > maxId) maxId = id;
    });

    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_alerts.php';
    const params = new URLSearchParams({ filter: currentFilter, sort: 'desc', limit: 5 });

    fetch(`${apiPath}?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.alerts.length > 0) {
                const newItems = data.alerts.filter(a => parseInt(a.id) > maxId);
                if (newItems.length > 0) {
                    newItems.reverse().forEach(alert => injectNewAlert(alert));
                    showToast(`${newItems.length} nouvelle(s) activité(s) !`);
                }
            }
        })
        .catch(e => console.error("Auto-refresh error:", e));
}

function injectNewAlert(alert) {
    const container = document.getElementById('alertsContainer');
    const html = createAlertCard(alert);
    
    // Injection au début de la grille
    container.insertAdjacentHTML('afterbegin', html);
    const newCard = container.firstElementChild;
    
    // --- CORRECTION ICI ---
    // 1. On force l'animation avec 'forwards' pour qu'elle reste visible à la fin
    newCard.style.animation = 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards';
    
    // 2. Lueur bleue pour attirer l'attention
    newCard.style.boxShadow = '0 0 30px var(--neon-blue-glow)';
    
    // 3. Nettoyage après l'animation (1 seconde plus tard)
    setTimeout(() => { 
        newCard.style.boxShadow = ''; 
        // On force l'opacité à 1 manuellement pour être sûr à 100% qu'elle ne redisparaisse jamais
        newCard.style.opacity = '1';
    }, 1000);

    // Réattacher les événements sur ce nouvel élément
    const zoomBtn = newCard.querySelector('.btn-zoom');
    if(zoomBtn) {
        zoomBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const img = this.closest('.alert-image-wrapper').querySelector('.alert-image');
            openLightbox(img.getAttribute('data-large'));
        });
    }

    const treatBtn = newCard.querySelector('.btn-mark-treated');
    if(treatBtn) {
        treatBtn.addEventListener('click', function() {
            openIncidentModal(this.getAttribute('data-id'));
        });
    }
    
    updateAlertCountText(1);
    
    // Réappliquer l'effet Spotlight sur la nouvelle carte
    if (typeof initSpotlight === 'function') initSpotlight();
}

// ===== 2. GESTION DES INCIDENTS (La Partie Corrigée) =====

// CETTE FONCTION MANQUAIT : Elle attache les clics aux boutons existants
function initIncidentModal() {
    document.querySelectorAll('.btn-mark-treated').forEach(button => {
        // On clone pour éviter les doublons d'événements si appelé plusieurs fois
        const newBtn = button.cloneNode(true);
        button.parentNode.replaceChild(newBtn, button);
        
        newBtn.addEventListener('click', function() {
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

// Listener global pour le bouton "Sauvegarder" de la modale
const submitBtn = document.getElementById('submitIncidentBtn');
if (submitBtn) {
    // On supprime les anciens listeners pour éviter les multiples soumissions
    const newSubmitBtn = submitBtn.cloneNode(true);
    submitBtn.parentNode.replaceChild(newSubmitBtn, submitBtn);
    
    newSubmitBtn.addEventListener('click', function() {
        const form = document.getElementById('incidentForm');
        const formData = new FormData(form);
        const incidentType = formData.get('incident_type');
        const notes = document.getElementById('incidentNotes').value;
        const alertId = document.getElementById('incidentAlertId').value;

        if (!incidentType) {
            showToast('Veuillez sélectionner un type d\'incident', true);
            return;
        }
        submitIncident(alertId, incidentType, notes);
    });
}

function submitIncident(alertId, incidentType, notes) {
    const btn = document.getElementById('submitIncidentBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';

    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/update_status.php';

    fetch(apiPath, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: alertId, incident_type: incidentType, notes: notes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modalEl = document.getElementById('incidentModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            
            handleCardUpdate(alertId, incidentType, notes);
            updateStats();
            updateCharts();
            showToast('Incident traité avec succès.');
        } else {
            showToast(data.error, true);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
    });
}

function handleCardUpdate(alertId, incidentType, notes) {
    const card = document.querySelector(`.alert-card[data-id="${alertId}"]`);
    if (!card) return;

    if (currentFilter === 'non_traitée') {
        card.style.height = card.offsetHeight + 'px';
        card.style.overflow = 'hidden';
        card.style.transition = 'all 0.5s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            card.style.height = '0px';
            card.style.padding = '0';
            card.style.margin = '0';
            card.style.border = 'none';
        }, 400);

        setTimeout(() => {
            card.remove();
            updateAlertCountText(-1);
        }, 900);
    } else {
        const badge = card.querySelector('.alert-badge');
        badge.className = 'alert-badge alert-badge-treated';
        badge.innerHTML = '<i class="fas fa-check-circle"></i> TRAITÉE';
        card.setAttribute('data-status', 'traitée');

        const btnContainer = card.querySelector('.btn-mark-treated');
        if (btnContainer) {
            const infoHtml = `
                <div class="incident-info" style="animation: fadeIn 0.5s">
                    <div class="incident-type"><i class="fas fa-tag"></i> ${incidentType}</div>
                    ${notes ? `<div class="incident-notes"><i class="fas fa-comment"></i> ${notes}</div>` : ''}
                </div>`;
            btnContainer.outerHTML = infoHtml;
        }
    }
}

// ===== 3. FONCTIONS UTILITAIRES =====

function updateAlertCountText(change) {
    const countEl = document.getElementById('alertsCount');
    if(countEl) {
        let current = parseInt(countEl.textContent.replace(/\D/g,''));
        current = Math.max(0, current + change);
        countEl.textContent = `${current} alertes affichées`;
    }
}

function createAlertCard(alert) {
    const isTraitee = alert.statut === 'traitée' || alert.statut === 'envoyée';
    const badgeClass = isTraitee ? 'treated' : 'untreated';
    const badgeText = isTraitee ? 'TRAITÉE' : 'NON TRAITÉE';
    const dateFormatted = new Date(alert.date_alerte).toLocaleString('fr-FR');

    let footerContent = '';
    
    if (!isTraitee) {
        // Bouton TRAITER (Bleu)
        footerContent = `<button class="btn-mark-treated" data-id="${alert.id}"><i class="fas fa-clipboard-check"></i> TRAITER</button>`;
    } else {
        // INFOS + Bouton SUPPRIMER (Rouge)
        footerContent = `
            <div class="incident-info">
                <div class="incident-type"><i class="fas fa-tag"></i> ${alert.incident_type || 'Incident classé'}</div>
                ${alert.notes ? `<div class="incident-notes"><i class="fas fa-comment"></i> ${alert.notes}</div>` : ''}
            </div>
            <button class="btn-delete-alert" data-id="${alert.id}" style="
                width: 100%; margin-top: 10px; background: rgba(239, 68, 68, 0.1); 
                border: 1px solid var(--neon-red); color: var(--neon-red); padding: 5px; 
                border-radius: 6px; cursor: pointer; font-family: var(--font-tech);">
                <i class="fas fa-trash"></i> SUPPRIMER
            </button>
        `;
    }

    return `
        <div class="alert-card" data-id="${alert.id}">
            <div class="alert-image-wrapper">
                <img src="${alert.image_url}" class="alert-image" data-large="${alert.image_url}">
                <div class="alert-overlay">
                    <button class="btn-zoom"><i class="fas fa-search-plus"></i></button>
                </div>
            </div>
            <div class="alert-content">
                <div class="alert-header">
                    <span class="alert-badge alert-badge-${badgeClass}">${badgeText}</span>
                </div>
                <div class="alert-info">
                    <i class="far fa-clock"></i> ${dateFormatted}
                    <span style="float:right; opacity:0.5">#${alert.id}</span>
                </div>
                ${footerContent}
            </div>
        </div>
    `;
}

// ===== 4. CHARGEMENT & FILTRES =====

function initFilters() {
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            applyFilters(true);
        });
    });
    
    document.querySelectorAll('[data-sort]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-sort]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentSort = this.getAttribute('data-sort');
            applyFilters(true);
        });
    });
}

function applyFilters(reset = false) {
    if (isLoading) return;
    isLoading = true;
    if (reset) {
        currentOffset = 0;
        document.getElementById('alertsContainer').innerHTML = '';
    }

    const params = new URLSearchParams({
        filter: currentFilter,
        sort: currentSort,
        offset: currentOffset,
        limit: 20
    });

    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_alerts.php';

    fetch(`${apiPath}?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (reset) document.getElementById('alertsContainer').innerHTML = '';
                
                if (data.alerts.length === 0 && currentOffset === 0) {
                    document.getElementById('alertsContainer').innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>Aucune donnée</p></div>';
                } else {
                    data.alerts.forEach(a => {
                        if(!document.querySelector(`.alert-card[data-id="${a.id}"]`)) {
                            document.getElementById('alertsContainer').insertAdjacentHTML('beforeend', createAlertCard(a));
                        }
                    });
                    initLightbox(); 
                    initIncidentModal();
                    initSpotlight();
                }
                
                hasMore = data.total > (currentOffset + data.alerts.length); // Fix hasMore logic
                const btnLoad = document.getElementById('loadMoreContainer');
                if(btnLoad) btnLoad.style.display = hasMore ? 'flex' : 'none';
                
                document.getElementById('alertsCount').textContent = `${data.total} alertes au total`;
            }
            isLoading = false;
        });
}

function initLoadMore() {
    const btn = document.getElementById('loadMoreBtn');
    if(btn) btn.addEventListener('click', () => {
        if(!isLoading && hasMore) {
            currentOffset += 20;
            applyFilters(false);
        }
    });
}

// ===== 5. LIGHTBOX & SPOTLIGHT =====

function initLightbox() {
    document.querySelectorAll('.btn-zoom').forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        newBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const img = this.closest('.alert-image-wrapper').querySelector('.alert-image');
            openLightbox(img.getAttribute('data-large'));
        });
    });
}

function openLightbox(src) {
    const lb = document.getElementById('lightbox');
    document.getElementById('lightboxImage').src = src;
    lb.classList.add('active');
}

const lbClose = document.querySelector('.lightbox-close');
if(lbClose) lbClose.addEventListener('click', () => document.getElementById('lightbox').classList.remove('active'));

const lb = document.getElementById('lightbox');
if(lb) lb.addEventListener('click', (e) => { if(e.target.id === 'lightbox') lb.classList.remove('active'); });

function initSpotlight() {
    const cards = document.querySelectorAll('.alert-card, .kpi-card, .chart-card');
    document.addEventListener('mousemove', (e) => {
        cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
}

// ===== 6. STATS & CHARTS =====

function updateStats() {
    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_stats.php';
    fetch(apiPath).then(r => r.json()).then(data => {
        if (data.success) {
            animateCounter('totalAlertes', data.total);
            animateCounter('alertesNonTraitees', data.non_traitees);
        }
    });
}

function animateCounter(id, end) {
    const el = document.getElementById(id);
    if (!el) return;
    const start = parseInt(el.textContent.replace(/\D/g, '')) || 0;
    if (start === end) return;
    
    // Simple interpolation
    el.textContent = end;
    el.style.textShadow = "0 0 15px var(--neon-blue)";
    setTimeout(() => { el.style.textShadow = "none"; }, 500);
}

function initClock() {
    setInterval(() => {
        const timeEl = document.getElementById('currentTime');
        if(timeEl) timeEl.textContent = new Date().toLocaleString('fr-FR');
    }, 1000);
}

function initCharts() {
    // On lance immédiatement la mise à jour pour créer les graphiques
    updateCharts();
}

function updateCharts() {
    const apiPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + '/api/get_stats_advanced.php';
    fetch(apiPath).then(r => r.json()).then(d => {
        if(d.success) {
            updateDailyChart(d.dailyStats);
            updateHourlyChart(d.hourlyStats);
            updateIncidentTypeChart(d.incidentTypes);
        }
    });
}

function updateDailyChart(data) {
    const ctx = document.getElementById('dailyChart');
    if (!ctx) return;
    const labels = data.map(d => new Date(d.date).toLocaleDateString('fr-FR', {day:'2-digit', month:'short'}));
    const values = data.map(d => d.count);
    
    if (dailyChart) { dailyChart.data.labels = labels; dailyChart.data.datasets[0].data = values; dailyChart.update(); }
    else {
        dailyChart = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: 'Alertes', data: values, backgroundColor: '#3b82f6' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: {display:false} }, scales: { y: { beginAtZero: true, grid: {color:'rgba(255,255,255,0.1)'} }, x: { grid: {display:false} } } }
        });
    }
}

function updateHourlyChart(data) {
    const ctx = document.getElementById('hourlyChart');
    if (!ctx) return;
    const labels = []; const values = []; const now = new Date();
    for (let i = 23; i >= 0; i--) {
        const d = new Date(now.getTime() - i*3600000);
        labels.push(d.getHours()+'h'); values.push(0);
    }
    const map = new Map();
    data.forEach(d => map.set(new Date(d.hour_timestamp).getHours(), d.count));
    for(let i=0; i<labels.length; i++) {
        let h = parseInt(labels[i]); if(map.has(h)) values[i] = map.get(h);
    }
    
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.5)'); 
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

    if (hourlyChart) { hourlyChart.data.labels = labels; hourlyChart.data.datasets[0].data = values; hourlyChart.update(); }
    else {
        hourlyChart = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: [{ label: 'Activité', data: values, backgroundColor: gradient, borderColor: '#3b82f6', fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: {display:false} }, scales: { x: {grid:{display:false}}, y: { beginAtZero: true, grid: {color:'rgba(255,255,255,0.1)'} } } }
        });
    }
}

function updateIncidentTypeChart(data) {
    const ctx = document.getElementById('incidentTypeChart');
    if (!ctx) return;
    const labels = data.map(d => d.type);
    const values = data.map(d => d.count);
    const bgColors = labels.map(l => incidentColorMap[l] || incidentColorMap.default);
    
    if (incidentTypeChart) { incidentTypeChart.data.labels = labels; incidentTypeChart.data.datasets[0].data = values; incidentTypeChart.data.datasets[0].backgroundColor = bgColors; incidentTypeChart.update(); }
    else {
        incidentTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: bgColors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: {position:'bottom', labels:{color:'#fff', font:{size:14}}} } }
        });
    }
}

function showToast(msg, err=false) {
    const t = document.getElementById('toast');
    if(!t) return;
    document.getElementById('toastMessage').textContent = msg;
    t.style.borderColor = err ? 'var(--neon-red)' : 'var(--neon-green)';
    t.classList.add('show');
    setTimeout(()=>t.classList.remove('show'), 3000);
}
