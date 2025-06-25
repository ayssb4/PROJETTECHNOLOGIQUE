 <?php
/**
 * Tableau de bord étudiant
 */

// Start session
session_start();

// Include basic functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require student login
requireStudent();

// Get available quizzes
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name, s.icon as subject_icon, u.name as professor_name 
    FROM quizzes q 
    JOIN subjects s ON q.subject_id = s.id 
    JOIN users u ON q.professor_id = u.id 
    WHERE q.is_active = 1 
    ORDER BY q.created_at DESC
");
$stmt->execute();
$quizzes = $stmt->fetchAll();

// Get student's recent attempts
$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, s.name as subject_name, s.icon as subject_icon
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    JOIN subjects s ON q.subject_id = s.id 
    WHERE qa.student_id = ? AND qa.is_completed = 1
    ORDER BY qa.completed_at DESC 
    LIMIT 5
");
$stmt->execute([getCurrentUserId()]);
$recent_attempts = $stmt->fetchAll();

// Get subjects for filtering
$subjects = getSubjects($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Étudiant</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out;
            text-align: center;
        }
        
        .welcome-section h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
            animation: bounce 2s ease-in-out infinite;
        }
        
        .subjects-filter {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .subject-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid #ddd;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            text-decoration: none;
            color: #333;
        }
        
        .subject-btn:hover,
        .subject-btn.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
            transform: translateY(-3px);
        }
        
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .quiz-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 1.2s ease-out;
        }
        
        .quiz-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .quiz-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .subject-icon {
            font-size: 2em;
        }
        
        .quiz-info h3 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .quiz-meta {
            color: #666;
            font-size: 0.9em;
        }
        
        .quiz-description {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .quiz-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #666;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: center;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .results-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.4s ease-out;
        }
        
        .results-section h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .result-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .result-card:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .result-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .result-details h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .result-meta {
            color: #666;
            font-size: 0.9em;
        }
        
        .score {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            color: white;
            text-align: center;
            min-width: 80px;
        }
        
        .score-excellent {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .score-good {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        
        .score-average {
            background: linear-gradient(45deg, #fd7e14, #dc3545);
        }
        
        .score-poor {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .no-data {
            text-align: center;
            color: #666;
            padding: 40px;
            font-style: italic;
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
            .quiz-grid {
                grid-template-columns: 1fr;
            }
            
            .subjects-filter {
                flex-direction: column;
                align-items: center;
            }
            
            .result-card {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <a href="../index.php" class="logo"> 🎓K&A Quiz Show</a>
            <div class="nav-links">
                <a href="results.php">Mes Résultats</a>
                <a href="logout.php">Déconnexion</a>
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

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>👨‍🎓 Bienvenue, <?php echo escape(getCurrentUserName()); ?> !</h1>
                <p>Choisissez un quiz pour tester vos connaissances</p>
                
                <!-- Subject Filter -->
                <div class="subjects-filter">
                    <a href="#" class="subject-btn active" onclick="filterQuizzes('all')">Toutes les matières</a>
                    <?php foreach ($subjects as $subject): ?>
                        <a href="#" class="subject-btn" onclick="filterQuizzes('<?php echo $subject['id']; ?>')">
                            <?php echo $subject['icon'] . ' ' . escape($subject['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quizzes Section -->
            <div class="quiz-grid" id="quizGrid">
                <?php if (empty($quizzes)): ?>
                    <div class="no-data">
                        Aucun quiz disponible pour le moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-card" data-subject="<?php echo $quiz['subject_id']; ?>">
                            <div class="quiz-header">
                                <span class="subject-icon"><?php echo $quiz['subject_icon']; ?></span>
                                <div class="quiz-info">
                                    <h3><?php echo escape($quiz['title']); ?></h3>
                                    <div class="quiz-meta">
                                        <?php echo escape($quiz['subject_name']); ?> • Par <?php echo escape($quiz['professor_name']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($quiz['description']): ?>
                                <div class="quiz-description">
                                    <?php echo escape($quiz['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="quiz-details">
                                <span>⏱️ <?php echo formatTime($quiz['time_limit']); ?></span>
                                <span>❓ <?php echo $quiz['total_questions']; ?> questions</span>
                            </div>
                            
                            <a href="take-quiz.php?id=<?php echo $quiz['id']; ?>" class="btn">
                                Commencer le Quiz
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Results -->
            <div class="results-section">
                <h2>📊 Mes Derniers Résultats</h2>
                
                <?php if (empty($recent_attempts)): ?>
                    <div class="no-data">
                        Vous n'avez pas encore passé de quiz.
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_attempts as $attempt): ?>
                        <div class="result-card">
                            <div class="result-info">
                                <span class="subject-icon"><?php echo $attempt['subject_icon']; ?></span>
                                <div class="result-details">
                                    <h4><?php echo escape($attempt['quiz_title']); ?></h4>
                                    <div class="result-meta">
                                        <?php echo escape($attempt['subject_name']); ?> • 
                                        <?php echo date('d/m/Y à H:i', strtotime($attempt['completed_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php 
                            $percentage = calculatePercentage($attempt['correct_answers'], $attempt['total_questions']);
                            $scoreClass = 'score-poor';
                            if ($percentage >= 80) $scoreClass = 'score-excellent';
                            elseif ($percentage >= 60) $scoreClass = 'score-good';
                            elseif ($percentage >= 40) $scoreClass = 'score-average';
                            ?>
                            
                            <div class="score <?php echo $scoreClass; ?>">
                                <?php echo $percentage; ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="results.php" class="btn" style="width: auto; padding: 10px 30px;">
                            Voir tous mes résultats
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function filterQuizzes(subjectId) {
            // Update active button
            document.querySelectorAll('.subject-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filter quiz cards
            const quizCards = document.querySelectorAll('.quiz-card');
            quizCards.forEach(card => {
                if (subjectId === 'all' || card.dataset.subject === subjectId) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>