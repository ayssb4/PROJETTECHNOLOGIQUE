 <?php
/**
 * Page de connexion
 */


session_start();

// Include basic functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isProfessor()) {
        redirect('prof-dashboard.php');
    } else {
        redirect('student-dashboard.php');
    }
}

$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Tous les champs sont obligatoires.';
    } else {
        // Get user from database
        $user = getUserByEmail($pdo, $email);
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Login successful
            loginUser($user);
            
            // Redirect based on role
            if ($user['role'] === 'professor') {
                redirect('prof-dashboard.php');
            } else {
                redirect('student-dashboard.php');
            }
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Quiz Site</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.8s ease-out;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
            animation: pulse 2s infinite;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 450px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            animation: bounce 2s ease-in-out infinite;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover:before {
            width: 300px;
            height: 300px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            animation: slideDown 0.5s ease-out;
        }
        
        .alert-danger {
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-center a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        
        .text-center a:hover {
            color: #764ba2;
        }
        
        .demo-accounts {
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            animation: fadeInUp 1.6s ease-out;
        }
        
        .demo-accounts h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #495057;
            text-align: center;
            font-size: 1.1em;
        }
        
        .account-info {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .account-box {
            background: white;
            padding: 12px;
            border-radius: 8px;
            width: 48%;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-size: 0.9em;
        }
        
        .account-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .account-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .account-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <a href="../index.php" class="logo">🎓K&A Quiz Show</a>
            <div class="nav-links">
                <a href="../index.php">Accueil</a>
                <a href="register.php">Inscription</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <h1>🔐 Connexion</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Adresse email :</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo escape($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Se connecter</button>
            </form>
            
            <div class="demo-accounts">
                <h3>Comptes de démonstration :</h3>
                <div class="account-info">
                    <div class="account-box">
                        <strong>Professeur</strong><br>
                        Email: prof@prof.com<br>
                        Password: password
                    </div>
                    <div class="account-box">
                        <strong>👨‍🎓 Élève</strong><br>
                        Email: eleve@student.com<br>
                        Password: password
                    </div>
                </div>
            </div>
            
            <p class="text-center" style="margin-top: 20px;">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </p>
        </div>
    </main>
</body>
</html>
