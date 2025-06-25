 <?php
/**
 * Simple Quiz Site Homepage
 */

// Start session
session_start();

// Include basic functions
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isProfessor()) {
        redirect('pages/prof-dashboard.php');
    } else {
        redirect('pages/student-dashboard.php');
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Site</title>
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
        
        /* Main container */
        .main-content {
            flex: 1;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out;
        }
        
        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            text-align: center;
            padding: 30px 20px;
            animation: slideUp 0.8s ease-out;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: white;
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
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2.5em;
            animation: bounce 2s ease-in-out infinite;
        }
        
        .welcome-text {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            animation: fadeInUp 1.2s ease-out;
        }
        
        .login-section {
            background: linear-gradient(45deg, #f9f9f9, #e9ecef);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            animation: fadeInUp 1.4s ease-out;
            transition: transform 0.3s ease;
        }
        
        .login-section:hover {
            transform: translateY(-5px);
        }
        
        .login-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
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
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #1e7e34);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
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
            color: #495057;
            text-align: center;
        }
        
        .account-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            gap: 20px;
        }
        
        .account-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            width: 45%;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .account-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .features {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 20px;
        }
        
        .feature-box {
            width: 45%;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
            animation: fadeInUp 1.8s ease-out;
        }
        
        .feature-box:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        
        .feature-box h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .feature-list {
            text-align: left;
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 8px 0;
            color: #666;
            transition: color 0.3s ease;
        }
        
        .feature-list li:hover {
            color: #333;
            transform: translateX(5px);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            animation: slideDown 0.5s ease-out;
        }
        
        .alert-success {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .features {
                flex-direction: column;
            }
            
            .feature-box {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .account-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .account-box {
                width: 100%;
            }
            
            .nav {
                flex-direction: column;
                gap: 15px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <a href="#" class="logo">🎓K&A Quiz Show</a>
            <div class="nav-links">
                <a href="pages/login.php">Connexion</a>
                <a href="pages/register.php">Inscription</a>
                <a href="#features">Fonctionnalités</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
        <!-- Messages -->
        <?php
        $success_message = getFlashMessage('success');
        $error_message = getFlashMessage('error');
        
        if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo escape($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo escape($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <h1>🎓 Quiz Site</h1>
        <p class="welcome-text">
            Bienvenue sur notre plateforme de quiz simple !
        </p>

        <!-- Login Section -->
        <div class="login-section">
            <div class="login-buttons">
                <a href="pages/login.php" class="btn btn-primary">Se Connecter</a>
                <a href="pages/register.php" class="btn btn-success">Créer un Compte</a>
            </div>
            
            <div class="demo-accounts">
                <h3>Comptes de test :</h3>
                <div class="account-info">
                    <div class="account-box">
                        <strong>👨‍🏫 Professeur</strong><br>
                        Email: name@prof.com<br>
                        Password: password
                    </div>
                    <div class="account-box">
                        <strong>👨‍🎓 Élève</strong><br>
                        Email: name@student.com<br>
                        Password: password
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="features" id="features">
            <div class="feature-box">
                <h3>👨‍🏫 Pour les Professeurs</h3>
                <ul class="feature-list">
                    <li>✓ Créer des quiz</li>
                    <li>✓ Voir les résultats</li>
                    <li>✓ Gérer les élèves</li>
                    <li>✓ Timer pour les quiz</li>
                </ul>
                <a href="pages/register.php?type=professor" class="btn btn-primary">S'inscrire Prof</a>
            </div>

            <div class="feature-box">
                <h3>👨‍🎓 Pour les Élèves</h3>
                <ul class="feature-list">
                    <li>✓ Passer des quiz</li>
                    <li>✓ Voir ses notes</li>
                    <li>✓ Interface simple</li>
                    <li>✓ Résultats instantanés</li>
                </ul>
                <a href="pages/register.php?type=student" class="btn btn-success">S'inscrire Élève</a>
            </div>
        </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="#about">À propos</a>
                <a href="#contact">Contact</a>
                <a href="#help">Aide</a>
                <a href="#privacy">Confidentialité</a>
            </div>
            <p>&copy; <?php echo date('Y'); ?> Quiz Site - Fait par des étudiants avec ❤️</p>
            <p>Développé avec PHP, CSS et beaucoup de café ☕</p>
        </div>
    </footer>
</body>
</html>