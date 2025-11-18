<?php

// Fichier de configuration de la base de données

// L'adresse IP de votre machine virtuelle Ubuntu
define('DB_HOST', '192.168.56.102');

// Le nom de la base de données que vous avez créée
define('DB_NAME', 'surveillance');

// L'utilisateur que vous avez créé pour votre script
define('DB_USER', 'python_user');

// Le mot de passe solide que vous avez choisi pour cet utilisateur
define('DB_PASS', 'PytHon_pr0j€t!');

define('DB_CHARSET', 'utf8mb4');

// URL de base pour accéder aux images servies par le serveur Apache de la VM
define('IMAGE_BASE_URL', 'http://192.168.56.102/captures/');
?>
