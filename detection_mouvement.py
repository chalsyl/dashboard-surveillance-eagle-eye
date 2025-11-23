# ==============================================================================
#                 EAGLE EYE - SCRIPT C2 + ASTERISK (FINAL)
# ==============================================================================
import cv2
import mysql.connector
import datetime
import os
import requests
import json
import time
import glob
import stat

# --- CONFIGURATION ---
DB_CONFIG = { "host": "localhost", "user": "python_user", "password": "PytHon_pr0j€t!", "database": "surveillance" }

# SMS (Traccar)
IP_SMS = "192.168.1.14"
PORT_SMS = "8082"
TOKEN_SMS = "6fef598c-1ff6-4300-845a-8d24f587ca06"
NUMERO_SMS = "+221771916379"

# ASTERISK (Appels)
ASTERISK_OUTGOING = "/var/spool/asterisk/outgoing/"
EXTENSION_SIP = "100" # Le numéro à appeler (votre softphone)
LOCK_FILE = "/tmp/alarme_desactivee.lock" # Fichier créé si on appuie sur 1

CAMERAS_CONFIG = {
    "USB_CAM": {"source": 0},
    # "PHONE_CAM": {"source": "http://192.168.1.14:4747/video"}, 
}

# --- GLOBALES ---
save_dir = os.path.expanduser("~/captures")
os.makedirs(save_dir, exist_ok=True)
try: os.chmod(save_dir, 0o755)
except: pass

caps = {}
fgbgs = {} 
last_alert_times = {}
settings_cache = {"system_status": "0", "sms_enabled": "0", "call_enabled": "0", "last_check": 0}
prev_system_on = None 

def get_db():
    try: return mysql.connector.connect(**DB_CONFIG)
    except: return None

def update_settings_cache():
    if time.time() - settings_cache['last_check'] < 1: return
    try:
        db = get_db()
        if db:
            cursor = db.cursor(dictionary=True)
            cursor.execute("SELECT * FROM settings")
            rows = cursor.fetchall()
            db.close()
            for row in rows: settings_cache[row['setting_key']] = row['setting_value']
            settings_cache['last_check'] = time.time()
    except: pass

def log(message):
    """Affiche dans le terminal ET enregistre dans la BDD pour le site web"""
    # 1. Affichage Terminal (avec encodage pour éviter les erreurs d'émojis)
    try:
        print(message)
    except:
        pass # On ignore les erreurs d'affichage terminal

    # 2. Enregistrement BDD
    try:
        db_log = get_db()
        if db_log:
            cur_log = db_log.cursor()
            # On échappe les caractères spéciaux pour éviter les bugs SQL
            cur_log.execute("INSERT INTO system_logs (message) VALUES (%s)", (message,))
            db_log.commit()
            db_log.close()
    except Exception as e:
        pass # On ne veut pas que le log fasse planter le script

def envoyer_sms(msg):
    log(f"📨 SMS: {msg}")
    try:
        requests.post(f"http://{IP_SMS}:{PORT_SMS}/", 
                      data=json.dumps({'to': NUMERO_SMS, 'message': msg}), 
                      headers={'authorization': TOKEN_SMS, 'Content-Type': 'application/json'}, 
                      timeout=1)
    except: pass

def declencher_appel_asterisk(cam_name):
    # 1. Vérifier si l'alarme est en pause (fichier lock)
    if os.path.exists(LOCK_FILE):
        log(f"🛑 ALARME SUSPENDUE (Touche 1 active) : Les appels sont bloqués pendant 1 minute.")
        return

    log(f"☎️ Initialisation appel Asterisk pour {cam_name}...")

    # Nom unique pour le fichier
    ts = datetime.datetime.now().strftime("%Y%m%d%H%M%S%f")
    call_filename = f"alerte_{ts}.call"
    temp_path = os.path.join("/tmp", call_filename)
    final_path = os.path.join(ASTERISK_OUTGOING, call_filename)

    # Contenu du fichier .call
    content = f"""
Channel: SIP/{EXTENSION_SIP}
MaxRetries: 1
RetryTime: 60
WaitTime: 30
Context: alerte-systeme
Extension: s
Priority: 1
Set: CAM_SOURCE={cam_name}
"""
    try:
        # On écrit dans /tmp d'abord
        with open(temp_path, "w") as f:
            f.write(content.strip())

        # On donne les droits
        os.chmod(temp_path, 0o666)

        # On déplace vers le dossier Asterisk (C'est atomique, donc sûr)
        os.rename(temp_path, final_path)
        log("✅ Appel envoyé au serveur Asterisk !")

    except Exception as e:
        log(f"❌ Erreur Appel: {e}")

# --- NETTOYAGE INITIAL ---
for img_file in glob.glob(os.path.join(save_dir, "live_*.jpg")):
    try: os.remove(img_file)
    except: pass

# --- INIT CAMERAS ---
log("🚀 DÉMARRAGE...")
for name, conf in CAMERAS_CONFIG.items():
    cap = cv2.VideoCapture(conf['source'])
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 360)
    if cap.isOpened():
        caps[name] = cap
        fgbgs[name] = cv2.createBackgroundSubtractorMOG2(history=500, varThreshold=25, detectShadows=False)
        last_alert_times[name] = 0
        log(f"✅ {name} OK")

# --- BOUCLE ---
try:
    while True:
        update_settings_cache()
        system_on = (settings_cache.get('system_status') == '1')
        sms_on = (settings_cache.get('sms_enabled') == '1')
        call_on = (settings_cache.get('call_enabled') == '1') # Nouveau setting

        if system_on != prev_system_on:
           log("\n🟢 ENGAGÉ" if system_on else "\n💤 VEILLE")
           prev_system_on = system_on

        for name in CAMERAS_CONFIG.keys():
            live_path = os.path.join(save_dir, f"live_{name}.jpg")

            if name not in caps or not caps[name].isOpened():
                if os.path.exists(live_path):
                    try: os.remove(live_path)
                    except: pass
                continue

            cap = caps[name]
            ret, frame = cap.read()

            if not ret:
                if os.path.exists(live_path):
                    try: os.remove(live_path)
                    except: pass
                continue

            try: cv2.imwrite(live_path, frame, [int(cv2.IMWRITE_JPEG_QUALITY), 80])
            except: pass

            if system_on:
                fgmask = fgbgs[name].apply(frame)
                _, fgmask = cv2.threshold(fgmask, 250, 255, cv2.THRESH_BINARY)
                
                if cv2.countNonZero(fgmask) > 0:
                    contours, _ = cv2.findContours(fgmask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
                    motion = any(cv2.contourArea(c) > 2000 for c in contours)

                    # Si mouvement détecté et cooldown terminé
                    if motion and (time.time() - last_alert_times[name] > 2):

                        # 1. VÉRIFICATION DU FICHIER LOCK (ALARME SUSPENDUE)
                        if os.path.exists(LOCK_FILE):
                            # On met à jour le temps pour éviter de spammer le message
                            last_alert_times[name] = time.time() 
                            log(f"🛑 ALARME SUSPENDUE (Touche 1 active) : Mouvement ignoré pendant 1 minute.")
                            continue 

                        # 2. SI ON ARRIVE ICI, C'EST QUE L'ALARME EST ACTIVE
                        last_alert_times[name] = time.time()
                        ts = datetime.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
                        full_path = os.path.join(save_dir, f"capture_{name}_{ts}.jpg")

                        log(f"🚨 MOUVEMENT {name}")
                        cv2.imwrite(full_path, frame)

                        # 3. ACTIONS (BDD, SMS, APPEL)
                        try:
                            db = get_db()
                            if db:
                                cur = db.cursor()
                                cur.execute("INSERT INTO alertes (date_alerte, image_path, type_alerte, statut) VALUES (NOW(), %s, %s, 'non_traitée')", (full_path, f"Mouvement {name}"))
                                db.commit()
                                aid = cur.lastrowid
                                db.close()

                                # SMS
                                if sms_on: envoyer_sms(f"ALERTE {name} ID:{aid}")

                                # APPEL ASTERISK
                                if call_on: declencher_appel_asterisk(name)

                        except: pass

        if not system_on: time.sleep(0.05)

except KeyboardInterrupt: log("STOP")
finally:
    for c in caps.values(): c.release()
