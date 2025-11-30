🦅 Guide d'Installation - Projet Eagle Eye
Voici comment installer le projet sur votre propre machine virtuelle (VM) Ubuntu.
Prérequis
Une VM Ubuntu 20.04 ou plus récente.
Une Webcam (USB ou DroidCam) connectée à la VM.
Un téléphone Android avec "SMS Gateway API" (si vous voulez tester les SMS).
Étape 1 : Préparer le Système
Ouvrez un terminal sur votre VM et lancez ces commandes :

code
Bash

# 1. Mettre à jour et installer les logiciels de base
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php libapache2-mod-php php-mysql mysql-server python3-pip git -y

# 2. Installer les librairies Python
pip install opencv-python mysql-connector-python requests

Étape 2 : Récupérer le Projet
Nous allons télécharger le code directement dans le dossier du serveur web.

code
Bash

# Aller dans le dossier web
cd /var/www/html

# Supprimer le fichier par défaut
sudo rm index.html

# Cloner le projet (remplacez par le lien du dépôt si nécessaire)

sudo apt install git -y

sudo git clone https://github.com/chalsyl/dashboard-surveillance-eagle-eye.git .

# Donner les permissions à Apache

sudo chown -R www-data:www-data /var/www/html

sudo chmod -R 755 /var/www/html

Étape 3 : Configurer la Base de Données
Nous allons importer la structure vierge du projet et créer votre utilisateur.
Importer la structure et les réglages par défaut :

code
Bash

sudo mysql < database.sql

Créer votre utilisateur et donner les droits :
Connectez-vous à MySQL :

code
Bash

sudo mysql

Puis copiez-collez ces commandes pour créer l'utilisateur standard du projet :

code
SQL

-- Création de l'utilisateur (Si vous changez le mot de passe ici, changez-le aussi dans les fichiers de config !)

CREATE USER IF NOT EXISTS 'python_user'@'localhost' IDENTIFIED BY 'PytHon_pr0j€t!';

CREATE USER IF NOT EXISTS 'python_user'@'%' IDENTIFIED BY 'PytHon_pr0j€t!';

-- Donner les droits sur la base 'surveillance'

GRANT ALL PRIVILEGES ON surveillance.* TO 'python_user'@'localhost';

GRANT ALL PRIVILEGES ON surveillance.* TO 'python_user'@'%';

FLUSH PRIVILEGES;

EXIT;

Étape 4 : Configurer le stockage des images
Le script enregistre les images dans ~/captures. Il faut créer ce dossier et le lier au site web.

code
Bash

# 1. Créer le dossier de captures dans VOTRE dossier personnel
mkdir -p ~/captures

# 2. Donner les droits (remplacez 'votre_nom' par votre nom d'utilisateur Linux, ex: osboxes)

chmod 755 ~

chmod 755 ~/captures

# 3. Créer le lien symbolique pour le site web
# IMPORTANT : Remplacez /home/chariosxvii par VOTRE chemin (ex: /home/osboxes)

sudo ln -s /home/$USER/captures /var/www/html/captures

Étape 5 : Configuration Finale
Site Web : Éditez le fichier de config pour mettre localhost.

code
Bash

sudo nano /var/www/html/config.php

Vérifiez que la ligne est : define('DB_HOST', 'localhost');
Script Python : Éditez le script pour mettre vos infos personnelles.

code
Bash

sudo nano /var/www/html/detection_mouvement.py

Modifiez save_dir si besoin.
Mettez l'IP de VOTRE téléphone dans la section SMS.
Mettez VOTRE numéro de téléphone destinataire.

📱 Partie Supplémentaire : Récupérer les infos Android (Traccar & DroidCam)


A. Pour les SMS (Traccar SMS Gateway)
Ouvrez l'application Traccar SMS Gateway.
Paramètre > Gateway configuration.
Regardez la section "LOCAL SERVICE" (Service Local) :
L'Adresse IP et le Port sont sous "Endpoints" (ex: http://192.168.1.14:8082).
Le Token est affiché juste au-dessus (une suite de chiffres/lettres).
B. Pour la Caméra IP (DroidCam)
Connectez votre téléphone au Wi-Fi.
Ouvrez l'application DroidCam (ou DroidCamX).
L'application affiche un écran avec "WiFi IP" et "DroidCam Port".
L'adresse complète à utiliser est : http:// + WiFi IP + : + Port + /video.
Exemple : Si IP est 192.168.1.14 et Port est 4747, l'adresse est : http://192.168.1.14:4747/video

✏️ Partie Supplémentaire : Où modifier le script Python ?

Commande pour éditer :

code
Bash

nano /var/www/html/detection_mouvement.py

Voici les lignes exactes à modifier :
1. Pour les SMS (Lignes ~20-25)
Cherchez ce bloc au début du fichier et remplacez les valeurs :

code
Python

# SMS (Traccar)
# Mettez l'IP que vous avez vue dans l'app Traccar (ex: 192.168.1.XX)
IP_SMS = "192.168.1.14" 

# Le port (généralement 8082)
PORT_SMS = "8082"

# Mettez le Token vu dans l'app Traccar
TOKEN_SMS = "votre_token_ici..."

# Mettez le numéro de téléphone qui doit recevoir l'alerte
NUMERO_SMS = "+33612345678"

2. Pour la Caméra IP (Lignes ~35)
    mettez l'adresse vue dans DroidCam :
   
code
Python

CAMERAS_CONFIG = {
    "USB_CAM": {"source": 0},
    # mettez votre IP DroidCam
    "PHONE_CAM": {"source": "http://192.168.1.14:4747/video"}, 
}


🦅 Guide Complémentaire : Audio, Téléphonie et Mobile
1. Installation du Message Vocal (Audio)
Asterisk est très strict sur les formats audio. Il ne lit pas les MP3 directement. Il faut convertir votre fichier menu_message.mp3 en format WAV spécifique (Mono, 8000Hz) et le placer au bon endroit.
Commandes à exécuter sur la VM :
Installer le convertisseur :

code
Bash

sudo apt update && sudo apt install ffmpeg -y

Convertir et installer le fichier :
(Assurez-vous que votre fichier menu_message.mp3 est dans votre dossier actuel)

code
Bash

# 1. Créer les dossiers nécessaires

sudo mkdir -p /var/lib/asterisk/sounds/custom

# 2. Convertir le MP3 en WAV compatible Asterisk

ffmpeg -i menu_message.mp3 -ac 1 -ar 8000 -acodec pcm_s16le alerte.wav

# 3. Copier le fichier dans les deux dossiers standards (ceinture et bretelles)

sudo cp alerte.wav /var/lib/asterisk/sounds/custom/alerte.wav

sudo mv alerte.wav /var/lib/asterisk/sounds/alerte.wav

# 4. Donner les permissions à Asterisk (CRUCIAL)

sudo chown asterisk:asterisk /var/lib/asterisk/sounds/alerte.wav

sudo chown asterisk:asterisk /var/lib/asterisk/sounds/custom/alerte.wav

sudo chmod 644 /var/lib/asterisk/sounds/alerte.wav

sudo chmod 644 /var/lib/asterisk/sounds/custom/alerte.wav

2. Configuration d'Asterisk (Fichiers de Config)
Pour que le système d'appel fonctionne, vous devez configurer deux fichiers.
A. Fichier sip.conf (Les Comptes)
Ouvrez le fichier :

code
Bash

sudo nano /etc/asterisk/sip.conf

Allez tout en bas du fichier (ou effacez tout) et ajoutez cette configuration propre :

code
Ini

[general]
context=internal
allowguest=no
udpbindaddr=0.0.0.0
tcpenable=no
transport=udp

; Configuration du compte pour votre Softphone (PC/Mobile)
[100]
type=friend
context=internal
host=dynamic
secret=1234        
disallow=all
allow=ulaw
allow=alaw
allow=gsm
dtmfmode=rfc2833   ; INDISPENSABLE pour que la touche "1" fonctionne
B. Fichier extensions.conf (Le Plan d'Appel)
Ouvrez le fichier :

code
Bash

sudo nano /etc/asterisk/extensions.conf

Ajoutez ceci à la fin :

code
Ini

[general]
static=yes
writeprotect=no

[internal]
; Extension pour appeler le softphone 100

exten => 100,1,Dial(SIP/100,20)
same => n,Hangup()

[alerte-systeme]
; Le script Python appelle ici

exten => s,1,Answer()
same => n,Wait(1)
; Joue le son (écoute les touches en même temps)

same => n,Background(/var/lib/asterisk/sounds/custom/alerte)
same => n,WaitExten(5)

; Si on appuie sur 1 : Désactive l'alarme

exten => 1,1,System(/usr/local/bin/desactiver_alarme.sh)
same => n,Playback(beep) ; Bip de confirmation
same => n,Hangup()

; Si timeout ou erreur

exten => t,1,Playback(vm-goodbye)
same => n,Hangup()
exten => i,1,Playback(pbx-invalid)
same => n,Goto(s,3)
N'oubliez pas de redémarrer Asterisk après modification :

code
Bash

sudo systemctl restart asterisk




Étape 6 : Lancer !
Lancer le script de surveillance :

code
Bash

python3 /var/www/html/detection_mouvement.py

Voir le site web :
Ouvrez le navigateur de la VM et allez sur http://localhost.
