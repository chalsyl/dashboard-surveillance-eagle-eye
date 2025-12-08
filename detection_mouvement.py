import cv2
import mysql.connector
import datetime
import os
import requests
import json
import time
import glob
import stat
DB_CONFIG = { "host": "localhost", "user": "python_user", "password": "PytHon_pr0j€t!", "database": "surveillance" }
IP_SMS = "192.168.1.14"
PORT_SMS = "8082"
TOKEN_SMS = "6fef598c-1ff6-4300-845a-8d24f587ca06"
NUMERO_SMS = "+221771916379"
ASTERISK_OUTGOING = "/var/spool/asterisk/outgoing/"
EXTENSION_SIP = "100"
LOCK_FILE = "/tmp/alarme_desactivee.lock"
CAMERAS_CONFIG = {
    "USB_CAM": {"source": 0},
    "PHONE_CAM": {"source": "http://192.168.1.14:4747/video"}, 
}
save_dir = os.path.expanduser("~/captures")
os.makedirs(save_dir, exist_ok=True)
try: os.chmod(save_dir, 0o755)
except: pass
caps = {}
fgbgs = {} 
last_alert_times = {}
settings_cache = {"system_status": "0", "sms_enabled": "0", "call_enabled": "0", "last_check": 0}
prev_system_on = None 
last_phone_call_time = 0
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
    try:
        print(message)
    except:
        pass
    try:
        db_log = get_db()
        if db_log:
            cur_log = db_log.cursor()
            cur_log.execute("INSERT INTO system_logs (message) VALUES (%s)", (message,))
            db_log.commit()
            db_log.close()
    except Exception as e:
        pass
def envoyer_sms(msg):
    log(f"📨 SMS: {msg}")
    try:
        requests.post(f"http://{IP_SMS}:{PORT_SMS}/", 
                      data=json.dumps({'to': NUMERO_SMS, 'message': msg}), 
                      headers={'authorization': TOKEN_SMS, 'Content-Type': 'application/json'}, 
                      timeout=1)
    except: pass
def declencher_appel_asterisk(cam_name):
    if os.path.exists(LOCK_FILE):
        log(f"🛑 ALARME SUSPENDUE (Touche 1 active) : Les appels sont bloqués pendant 1 minute.")
        return
    log(f"☎️ Initialisation appel Asterisk pour {cam_name}...")
    ts = datetime.datetime.now().strftime("%Y%m%d%H%M%S%f")
    call_filename = f"alerte_{ts}.call"
    temp_path = os.path.join("/tmp", call_filename)
    final_path = os.path.join(ASTERISK_OUTGOING, call_filename)
    content = f'''
Channel: SIP/{EXTENSION_SIP}
MaxRetries: 1
RetryTime: 60
WaitTime: 30
Context: alerte-systeme
Extension: s
Priority: 1
Set: CAM_SOURCE={cam_name}
'''
    try:
        with open(temp_path, "w") as f:
            f.write(content.strip())
        os.chmod(temp_path, 0o666)
        os.rename(temp_path, final_path)
        log("✅ Appel envoyé au serveur Asterisk !")
    except Exception as e:
        log(f"❌ Erreur Appel: {e}")
for img_file in glob.glob(os.path.join(save_dir, "live_*.jpg")):
    try: os.remove(img_file)
    except: pass
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
try:
    while True:
        update_settings_cache()
        system_on = (settings_cache.get('system_status') == '1')
        sms_on = (settings_cache.get('sms_enabled') == '1')
        call_on = (settings_cache.get('call_enabled') == '1')
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
                    if motion and (time.time() - last_alert_times[name] > 2):
                        if os.path.exists(LOCK_FILE):
                            last_alert_times[name] = time.time() 
                            log(f"🛑 ALARME SUSPENDUE (Touche 1 active) : Mouvement ignoré pendant 1 minute.")
                            continue 
                        last_alert_times[name] = time.time()
                        ts = datetime.datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
                        full_path = os.path.join(save_dir, f"capture_{name}_{ts}.jpg")
                        log(f"🚨 MOUVEMENT {name}")
                        cv2.imwrite(full_path, frame)
                        try:
                            db = get_db()
                            if db:
                                cur = db.cursor()
                                cur.execute("INSERT INTO alertes (date_alerte, image_path, type_alerte, statut) VALUES (NOW(), %s, %s, 'non_traitée')", (full_path, f"Mouvement {name}"))
                                db.commit()
                                aid = cur.lastrowid
                                db.close()
                                if sms_on: envoyer_sms(f"ALERTE {name} ID:{aid}")
                                if call_on: 
                                    if (time.time() - last_phone_call_time) > 60:
                                        declencher_appel_asterisk(name)
                                        last_phone_call_time = time.time()
                                        print("   ☎️ Appel autorisé et lancé.")
                                    else:
                                        print("   ⏳ Appel bloqué (Délai 1min non écoulé)")
                        except: pass
        if not system_on: time.sleep(0.05)
except KeyboardInterrupt: log("STOP")
finally:
    for c in caps.values(): c.release()