<?php
session_start();
require_once 'database.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header('Location: control.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            
            header('Location: control.php');
            exit;
        } else {
            $error = "Identifiants invalides. Accès refusé.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eagle Eye - Accès Sécurisé</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 3rem;
            background: rgba(10, 15, 25, 0.7); 
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 0 60px rgba(0, 0, 0, 0.8);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-card::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
            animation: scanLogin 3s linear infinite;
        }

        .brand-icon-lg {
            font-size: 4rem;
            color: var(--neon-blue);
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 20px var(--neon-blue-glow));
            animation: pulse-blue 3s infinite;
        }

        .login-title {
            font-family: var(--font-ui);
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
        }

        .login-subtitle {
            font-family: var(--font-tech);
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-bottom: 2rem;
            text-transform: uppercase;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-right: none;
            color: var(--text-muted);
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid var(--glass-border) !important;
            border-left: none !important;
            color: white !important;
            padding: 0.8rem;
            font-family: var(--font-tech);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1) !important;
            box-shadow: none !important;
            border-color: var(--neon-blue) !important;
        }
        
        .form-control:focus + .input-group-text, 
        .input-group:focus-within .input-group-text {
            border-color: var(--neon-blue) !important;
            color: var(--neon-blue);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            margin-top: 1rem;
            background: var(--neon-blue);
            border: none;
            color: white;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-radius: 8px;
            transition: all 0.3s;
            box-shadow: 0 0 20px var(--neon-blue-glow);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px var(--neon-blue-glow);
            background: #4f8fff; 
        }

        .error-msg {
            color: var(--neon-red);
            font-size: 0.85rem;
            margin-bottom: 1rem;
            font-family: var(--font-tech);
            animation: shake 0.3s;
        }

        @keyframes scanLogin { 0% { top: 0; } 100% { top: 100%; } }
        @keyframes pulse-blue { 50% { opacity: 0.7; transform: scale(0.95); } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-icon-lg">
            <i class="fas fa-fingerprint"></i>
        </div>
        <h2 class="login-title">EAGLE EYE</h2>
        <p class="login-subtitle">Authentification Requise</p>

        <?php if ($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group mb-3">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" name="username" class="form-control" placeholder="IDENTIFIANT" required autocomplete="off">
            </div>

            <div class="input-group mb-4">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="CODE D'ACCÈS" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-unlock-alt me-2"></i> Accéder au C2
            </button>
        </form>
    </div>

</body>
</html>