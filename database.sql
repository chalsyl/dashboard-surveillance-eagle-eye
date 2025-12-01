-- ==========================================================
-- SCRIPT D'INSTALLATION BASE DE DONNÉES - PROJET EAGLE EYE
-- ==========================================================

CREATE DATABASE IF NOT EXISTS surveillance;
USE surveillance;

-- --------------------------------------------------------
-- 1. Table : USERS (Pour la connexion au site)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- AJOUT DE L'UTILISATEUR ADMIN / ADMIN (En clair)
LOCK TABLES `users` WRITE;
INSERT INTO `users` (username, password) VALUES ('admin', 'admin');
UNLOCK TABLES;

-- --------------------------------------------------------
-- 2. Table : ALERTES (Stockage des détections)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `alertes`;
CREATE TABLE `alertes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_alerte` datetime NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `type_alerte` varchar(50) DEFAULT 'mouvement',
  `statut` enum('non_traitée','envoyée') DEFAULT 'non_traitée',
  `incident_type` varchar(50) DEFAULT NULL,
  `notes` text,
  `treated_at` datetime DEFAULT NULL,
  `treated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. Table : SETTINGS (Réglages du Dashboard C2)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données par défaut pour que le système fonctionne immédiatement
LOCK TABLES `settings` WRITE;
INSERT IGNORE INTO `settings` (setting_key, setting_value) VALUES 
('system_status', '0'),
('sms_enabled', '1'),
('call_enabled', '1');
UNLOCK TABLES;

-- --------------------------------------------------------
-- 4. Table : LOGS SYSTÈME (Optionnel)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_log` datetime DEFAULT CURRENT_TIMESTAMP,
  `message` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ==========================================================
-- 5. CONFIGURATION UTILISATEUR MYSQL (AUTOMATIQUE)
-- ==========================================================

-- Création de l'utilisateur 'python_user' pour le script et le site
CREATE USER IF NOT EXISTS 'python_user'@'localhost' IDENTIFIED BY 'PytHon_pr0j€t!';
CREATE USER IF NOT EXISTS 'python_user'@'%' IDENTIFIED BY 'PytHon_pr0j€t!';

-- Attribution des droits
GRANT ALL PRIVILEGES ON surveillance.* TO 'python_user'@'localhost';
GRANT ALL PRIVILEGES ON surveillance.* TO 'python_user'@'%';

FLUSH PRIVILEGES;
