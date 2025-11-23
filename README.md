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

nano /var/www/html/detection_mouvement.py

Modifiez save_dir si besoin.
Mettez l'IP de VOTRE téléphone dans la section SMS.
Mettez VOTRE numéro de téléphone destinataire.
Étape 6 : Lancer !
Lancer le script de surveillance :

code
Bash

python3 /var/www/html/detection_mouvement.py

Voir le site web :
Ouvrez le navigateur de la VM et allez sur http://localhost.



