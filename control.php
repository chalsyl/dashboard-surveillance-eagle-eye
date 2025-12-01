<?php 
session_start();
// Si pas connecté, ouste ! Direction le login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'database.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eagle Eye - Command Center</title>
    
    <!-- CDNs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* --- Styles Spécifiques pour le Contrôleur --- */
        
        /* Zone du Réacteur (Bouton Power) */
        .reactor-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            background: radial-gradient(circle at center, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
        }

        .power-btn-wrapper {
            position: relative;
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        /* Le bouton lui-même */
        .power-btn {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(10, 10, 15, 0.9);
            border: 2px solid var(--glass-border);
            color: var(--text-muted);
            font-size: 3rem;
            cursor: pointer;
            position: relative;
            z-index: 2;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 0 30px rgba(0,0,0,0.5), inset 0 0 20px rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Anneaux animés autour du bouton */
        .power-ring {
            position: absolute;
            top: -10px; left: -10px; right: -10px; bottom: -10px;
            border-radius: 50%;
            border: 2px dashed var(--glass-border);
            animation: spin 10s linear infinite;
            z-index: 1;
            opacity: 0.5;
        }

        .power-ring::before {
            content: ''; position: absolute; top: -10px; left: -10px; right: -10px; bottom: -10px;
            border-radius: 50%; border: 1px solid var(--glass-border); opacity: 0.3;
        }

        /* État ACTIF (Vert/Bleu Néon) */
        .power-btn.active {
            color: var(--neon-green);
            border-color: var(--neon-green);
            box-shadow: 0 0 50px var(--neon-green-glow), inset 0 0 30px var(--neon-green-glow);
            text-shadow: 0 0 20px var(--neon-green);
        }
        
        .power-btn.active ~ .power-ring {
            border-color: var(--neon-green);
            animation: spin 4s linear infinite; /* Tourne plus vite */
            box-shadow: 0 0 30px var(--neon-green-glow);
        }

        /* Style Console Hacker */
.console-box {
    background-color: #000;
    color: #00ff00; /* Vert Matrix */
    font-family: 'Courier New', Courier, monospace;
    padding: 15px;
    border-radius: 5px;
    height: 400px;
    overflow-y: auto; /* Barre de défilement */
    border: 1px solid #333;
    box-shadow: inset 0 0 10px #000;
    font-size: 0.9rem;
}
.console-line {
    margin-bottom: 5px;
    border-bottom: 1px solid #111;
    padding-bottom: 2px;
}
.console-date {
    color: #666;
    font-size: 0.8rem;
    margin-right: 10px;
} 
        /* État LOADING */
        .power-btn.loading i { display: none; }
        .power-btn.loading::after {
            content: '';
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: var(--neon-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Modules de configuration */
        .config-module {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .config-module:hover {
            background: rgba(255, 255, 255, 0.03);
            border-color: var(--neon-blue);
        }
        
        /* Switch Custom */
        .switch-custom {
            position: relative; display: inline-block; width: 50px; height: 26px;
        }
        .switch-custom input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #1a1a1a; border: 1px solid var(--glass-border); transition: .4s; border-radius: 34px;
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: var(--text-muted); transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: rgba(16, 185, 129, 0.2); border-color: var(--neon-green); }
        input:checked + .slider:before { transform: translateX(24px); background-color: var(--neon-green); box-shadow: 0 0 10px var(--neon-green-glow); }
        input:disabled + .slider { opacity: 0.5; cursor: not-allowed; }

        /* Caméras */
        .live-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }.cam-layers {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 0;
}

/* Les images elles-mêmes */
.cam-layer {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    opacity: 0; /* Caché par défaut */
    transition: opacity 0.2s ease-in-out; /* Transition fluide */
}

/* Classe pour afficher l'image active */
.cam-layer.visible {
    opacity: 1;
    z-index: 2;
}

        .cam-feed {
            background: #000;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            overflow: hidden;
            position: relative;
            aspect-ratio: 16/9;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
        }
        .cam-feed img {
            width: 100%; height: 100%; object-fit: cover;
            opacity: 0.8; transition: opacity 0.3s;
        }
        .cam-feed.active img { opacity: 1; }
        
        /* Overlay "No Signal" */
        .no-signal {
            position: absolute; inset: 0;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: repeating-linear-gradient(
                45deg, #050505, #050505 10px, #0a0a0a 10px, #0a0a0a 20px
            );
            color: var(--text-muted);
            z-index: 1;
        }
        .cam-ui {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none; z-index: 2; padding: 15px;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .cam-header { display: flex; justify-content: space-between; align-items: center; }
        .rec-badge {
            background: rgba(239, 68, 68, 0.2); color: var(--neon-red);
            padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem;
            border: 1px solid var(--neon-red); display: flex; align-items: center; gap: 6px;
            opacity: 0; transition: opacity 0.3s;
        }
        .active .rec-badge { opacity: 1; }
        .rec-dot { width: 8px; height: 8px; background: var(--neon-red); border-radius: 50%; animation: blink 1s infinite; }
        
        .cam-name {
            font-family: var(--font-tech); color: var(--neon-blue);
            background: rgba(0,0,0,0.7); padding: 2px 8px; border-radius: 4px; font-size: 0.9rem;
            border-left: 3px solid var(--neon-blue);
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes blink { 50% { opacity: 0; } }

    </style>
</head>
<body>

    <header class="dashboard-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="brand">
                <i class="fas fa-shield-alt brand-icon"></i>
                <h1 class="brand-title">EAGLE EYE <span class="brand-subtitle">COMMAND</span></h1>
            </div>
            <a href="index.php" class="btn-filter"><i class="fas fa-arrow-left"></i> DASHBOARD</a>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <div class="row g-4">
                <!-- Bouton Power -->
                <div class="col-lg-5">
                    <div class="kpi-card reactor-container h-100">
                        <div class="power-btn-wrapper">
                            <button id="systemPowerBtn" class="power-btn"><i class="fas fa-power-off"></i></button>
                            <div class="power-ring"></div>
                        </div>
                        <div class="text-center">
                            <h2 class="kpi-value mb-2" id="systemStatusText" style="font-size: 2rem;">INITIALISATION...</h2>
                            <p class="neon-blue font-monospace" id="systemSubtext">Synchronisation...</p>
                        </div>
                    </div>
                </div>

                <!-- Config -->
                <div class="col-lg-7">
                    <div class="h-100 d-flex flex-column justify-content-center gap-3">
                        <div class="config-module">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon"><i class="fas fa-comment-alt"></i></div>
                                <div><h5 class="m-0 fw-bold">Protocole SMS</h5></div>
                            </div>
                            <label class="switch-custom"><input type="checkbox" id="smsToggle"><span class="slider"></span></label>
                        </div>
                        <div class="config-module">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon"><i class="fas fa-phone-volume"></i></div>
                                <div><h5 class="m-0 fw-bold">Ligne d'Urgence</h5></div>
                            </div>
                            <label class="switch-custom"><input type="checkbox" id="callToggle"><span class="slider"></span></label>
                        </div>
                        <div class="incident-info mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="incident-type"><i class="fas fa-server"></i> SERVEUR DÉTECTION</span>
                                <span class="neon-blue font-monospace">192.168.56.102</span>
                            </div>
                            <div class="incident-notes mt-2" id="serverMsg">
                                <i class="fas fa-circle-notch fa-spin"></i> Vérification de la liaison...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
    <div class="col-12">
        <div class="card bg-dark text-light">
            <div class="card-header border-secondary">
                <i class="bi bi-terminal-fill me-2"></i> Journal Système en Direct
            </div>
            <div class="card-body p-0">
                <div id="live-console" class="console-box">
                    <!-- Les logs s'afficheront ici -->
                    <div class="text-center text-muted mt-5">Chargement des logs...</div>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Caméras -->
            <div class="section-header mt-5">
                <h2 class="section-title"><i class="fas fa-video"></i> FLUX TACTIQUES</h2>
            </div>
            <div class="live-grid" id="cameraGrid">
                <!-- Le JS va injecter les caméras ici -->
            </div>
        </div>
    </main>

    <script>
        // ===== CONFIGURATION =====
        const API_URL = 'api/control_system.php';
        // REMPLACEZ PAR VOTRE IP :
        const IMG_BASE_URL = 'http://192.168.56.102/captures/'; 
        const CAMERAS = ['USB_CAM', 'PHONE_CAM']; 

        // État global
        let isProcessing = false;
        let ignoreUpdates = false;
        const activeLayerMap = {}; // Pour le double buffering

        // ===== 1. INITIALISATION DES CAMÉRAS (CRÉATION HTML) =====
        function initCameras() {
            const container = document.getElementById('cameraGrid');
            if(!container) return;
            container.innerHTML = '';
            
            CAMERAS.forEach(camName => {
                activeLayerMap[camName] = 0; // On commence par le calque 0
                
                const camHtml = `
                    <div class="cam-feed" id="feed-${camName}">
                        <!-- Overlay NO SIGNAL -->
                        <div class="no-signal" id="nosig-${camName}">
                            <i class="fas fa-video-slash fa-3x mb-3"></i>
                            <div style="font-family: var(--font-tech); letter-spacing: 2px;">OFFLINE</div>
                        </div>
                        
                        <!-- IMAGES SUPERPOSÉES (Double Buffering) -->
                        <div class="cam-layers">
                            <img class="cam-layer visible" id="layer0-${camName}" src="" alt="">
                            <img class="cam-layer" id="layer1-${camName}" src="" alt="">
                        </div>

                        <!-- UI Overlay -->
                        <div class="cam-ui">
                            <div class="cam-header">
                                <div class="cam-name">${camName}</div>
                                <div class="rec-badge"><div class="rec-dot"></div> REC</div>
                            </div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', camHtml);
            });
        }

        // ===== 2. MISE À JOUR VIDÉO (FLUIDITÉ TOTALE) =====
        function updateVideoFeeds(isOn) {
            const ts = new Date().getTime();
            
            CAMERAS.forEach(camName => {
                // Sélecteurs précis basés sur l'ID créé dans initCameras
                const feedDiv = document.getElementById(`feed-${camName}`);
                const noSignal = document.getElementById(`nosig-${camName}`);
                
                // Sécurité si le DOM n'est pas prêt
                if (!feedDiv || !noSignal) return;

                if (!isOn) {
                    feedDiv.classList.remove('active');
                    noSignal.style.opacity = 1;
                    return;
                }

                feedDiv.classList.add('active');

                // Logique Ping-Pong des calques
                const currentIdx = activeLayerMap[camName] || 0;
                const nextIdx = (currentIdx === 0) ? 1 : 0;
                
                const nextImg = document.getElementById(`layer${nextIdx}-${camName}`);
                const currentImg = document.getElementById(`layer${currentIdx}-${camName}`);

                if(!nextImg || !currentImg) return;

                // Préchargement en mémoire
                const tester = new Image();
                
                tester.onload = function() {
                    // SUCCÈS : L'image est prête, on l'affiche
                    nextImg.src = this.src;
                    nextImg.classList.add('visible'); // Fade In
                    currentImg.classList.remove('visible'); // Fade Out
                    noSignal.style.opacity = 0;
                    
                    // On note quel est le calque actif pour la prochaine fois
                    activeLayerMap[camName] = nextIdx;
                };

                tester.onerror = function() {
                    // ERREUR : Fichier supprimé par Python -> Caméra HS
                    noSignal.style.opacity = 1;
                };

                // Déclenchement du chargement
                tester.src = `${IMG_BASE_URL}live_${camName}.jpg?t=${ts}`;
            });
        }

        // ===== 3. LOGIQUE BOUTONS & API =====
        const powerBtn = document.getElementById('systemPowerBtn');
        const statusText = document.getElementById('systemStatusText');
        const statusSub = document.getElementById('systemSubtext');
        const smsToggle = document.getElementById('smsToggle');
        const callToggle = document.getElementById('callToggle');
        const serverMsg = document.getElementById('serverMsg');

        async function refreshStatus(force = false) {
            if(ignoreUpdates && !force) return;

            try {
                const res = await fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'get_status' }) });
                const data = await res.json();
                
                if(data.success) {
                    const s = data.settings;
                    const isOn = s.system_status === '1';

                    // UI Power
                    if (isOn) {
                        powerBtn.classList.add('active');
                        statusText.textContent = "SYSTÈME ENGAGÉ";
                        statusText.style.color = "var(--neon-green)";
                        statusSub.textContent = "Surveillance active • Enregistrement";
                        serverMsg.innerHTML = '<i class="fas fa-check-circle" style="color:var(--neon-green)"></i> Service opérationnel';
                    } else {
                        powerBtn.classList.remove('active');
                        statusText.textContent = "SYSTÈME EN VEILLE";
                        statusText.style.color = "var(--neon-red)";
                        statusSub.textContent = "Capteurs désactivés • Attente d'ordres";
                        serverMsg.innerHTML = '<i class="fas fa-stop-circle" style="color:var(--neon-red)"></i> Service en pause';
                    }

                    // UI Toggles (si pas désactivés par l'utilisateur)
                    if (!smsToggle.disabled) smsToggle.checked = s.sms_enabled === '1';
                    if (!callToggle.disabled) callToggle.checked = s.call_enabled === '1';
                }
            } catch (e) {
                serverMsg.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i> Connexion perdue...';
            }
        }

        function fetchLogs() {
        fetch('api/get_logs.php')
            .then(response => response.json())
            .then(data => {
                const consoleBox = document.getElementById('live-console');
                consoleBox.innerHTML = ''; // On vide pour rafraîchir

                // On parcourt les logs (data est trié par DESC, on veut afficher le plus récent en haut)
                data.forEach(log => {
                    const line = document.createElement('div');
                    line.className = 'console-line';
                    
                    // Formatage de la date
                    const date = new Date(log.date_log);
                    const timeStr = date.toLocaleTimeString();

                    line.innerHTML = `<span class="console-date">[${timeStr}]</span> ${log.message}`;
                    consoleBox.appendChild(line);
                });
            })
            .catch(err => console.error('Erreur logs:', err));
    }

    // Rafraîchir les logs toutes les 2 secondes
    setInterval(fetchLogs, 2000);
    
    // Premier chargement
    fetchLogs();
    
        // Action générique pour les toggles
        async function toggleSetting(key, val, el) {
            ignoreUpdates = true; el.disabled = true;
            try {
                await fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'toggle_setting', key: key, value: val }) });
                // Petit délai pour laisser le temps à la BDD de s'écrire
                setTimeout(() => { ignoreUpdates = false; refreshStatus(true); }, 1000);
            } catch(e) { 
                el.checked = !val; // Annuler en cas d'erreur
                ignoreUpdates = false; 
            } finally { el.disabled = false; }
        }

        // Listeners
        powerBtn.addEventListener('click', async () => {
            if(powerBtn.classList.contains('loading')) return;
            
            ignoreUpdates = true; 
            powerBtn.classList.add('loading');
            statusText.textContent = "TRAITEMENT...";
            
            try {
                const target = powerBtn.classList.contains('active') ? '0' : '1';
                await fetch(API_URL, { method: 'POST', body: JSON.stringify({ action: 'set_status', value: target }) });
                
                // Attente forcée pour la stabilité (Python doit lire la BDD)
                setTimeout(async () => {
                    await refreshStatus(true);
                    powerBtn.classList.remove('loading');
                    ignoreUpdates = false;
                }, 1500);
            } catch(e) {
                console.error(e);
                powerBtn.classList.remove('loading');
                ignoreUpdates = false;
            }
        });

        smsToggle.addEventListener('change', (e) => toggleSetting('sms_enabled', e.target.checked, smsToggle));
        callToggle.addEventListener('change', (e) => toggleSetting('call_enabled', e.target.checked, callToggle));

        // ===== 4. BOUCLES INFINIES =====
        
        // Démarrage
        initCameras();
        refreshStatus(true);

        // Loop Statut (Lent)
        setInterval(refreshStatus, 2000);
        
        // Loop Vidéo (Rapide)
        setInterval(() => {
            // On ne lance le rafraichissement vidéo que si le bouton est ACTIF
            if(powerBtn.classList.contains('active')) {
                updateVideoFeeds(true);
            } else {
                updateVideoFeeds(false);
            }
        }, 150); // ~7 images/sec

    </script>
</body>
</html>

 
