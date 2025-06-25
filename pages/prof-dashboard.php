 <?php
 
session_start();


require_once '../config/database.php';
require_once '../includes/functions.php';

 
requireProfessor();
 
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name, s.icon as subject_icon,
           COUNT(qa.id) as total_attempts,
           AVG(qa.score) as avg_score
    FROM quizzes q 
    JOIN subjects s ON q.subject_id = s.id 
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.is_completed = 1
    WHERE q.professor_id = ? 
    GROUP BY q.id
    ORDER BY q.created_at DESC
");
$stmt->execute([getCurrentUserId()]);
$quizzes = $stmt->fetchAll();

 
$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, s.name as subject_name, s.icon as subject_icon, u.name as student_name
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    JOIN subjects s ON q.subject_id = s.id 
    JOIN users u ON qa.student_id = u.id
    WHERE q.professor_id = ? AND qa.is_completed = 1
    ORDER BY qa.completed_at DESC 
    LIMIT 10
");
$stmt->execute([getCurrentUserId()]);
$recent_attempts = $stmt->fetchAll();
 
$total_quizzes = count($quizzes);
$total_attempts = array_sum(array_column($quizzes, 'total_attempts'));
$avg_score = $total_attempts > 0 ? array_sum(array_column($quizzes, 'avg_score')) / count(array_filter($quizzes, function($q) { return $q['avg_score'] > 0; })) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Professeur</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            animation: fadeInUp 1.2s ease-out;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .stat-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .actions-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.4s ease-out;
        }
        
        .actions-section h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
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
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .btn-success:hover {
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .quizzes-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.6s ease-out;
        }
        
        .quizzes-section h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .quiz-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 15px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: #667eea;
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
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .quiz-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }
        
        .quiz-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 8px 15px;
            font-size: 0.9em;
            flex: 1;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800);
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .recent-attempts {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.8s ease-out;
        }
        
        .recent-attempts h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .attempt-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .attempt-card:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .attempt-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .attempt-details h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .attempt-meta {
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
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .attempt-card {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .quiz-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
 
    <header class="header">
        <nav class="nav">
            <a href="../index.php" class="logo"> 🎓K&A Quiz Show</a>
            <div class="nav-links">
                <a href="create-quizz.php">Créer un Quiz</a>
                <a href="results.php">Voir les Résultats</a>
                <a href="logout.php">Déconnexion</a>
            </div>
        </nav>
    </header>

  
    <main class="main-content">
        <div class="container">
            
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

             
            <div class="welcome-section">
                <h1>👨‍🏫 Bienvenue, Professeur <?php echo escape(getCurrentUserName()); ?> !</h1>
                <p>Gérez vos quiz et suivez les performances de vos étudiants</p>
            </div>

          
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-number"><?php echo $total_quizzes; ?></div>
                    <div class="stat-label">Quiz créés</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo $total_attempts; ?></div>
                    <div class="stat-label">Tentatives totales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo number_format($avg_score, 1); ?>%</div>
                    <div class="stat-label">Score moyen</div>
                </div>
            </div>

             
            <div class="actions-section">
                <h2>⚡ Actions Rapides</h2>
                <div class="action-buttons">
                    <a href="results.php" class="btn btn-success">
                        ➕ Créer un Nouveau Quiz
                    </a>
                    <a href="quiz_result.php" class="btn">
                        📊 Voir tous les Résultats
                    </a>
                </div>
            </div>

             
            <div class="quizzes-section">
                <h2>📚 Mes Quiz</h2>
                
                <?php if (empty($quizzes)): ?>
                    <div class="no-data">
                        Vous n'avez pas encore créé de quiz. <a href="create-quizz.php">Créer votre premier quiz</a>
                    </div>
                <?php else: ?>
                    <div class="quiz-grid">
                        <?php foreach ($quizzes as $quiz): ?>
                            <div class="quiz-card">
                                <div class="quiz-header">
                                    <span class="subject-icon"><?php echo $quiz['subject_icon']; ?></span>
                                    <div class="quiz-info">
                                        <h3><?php echo escape($quiz['title']); ?></h3>
                                        <div class="quiz-meta">
                                            <?php echo escape($quiz['subject_name']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($quiz['description']): ?>
                                    <div class="quiz-description">
                                        <?php echo escape($quiz['description']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="quiz-stats">
                                    <span>⏱️ <?php echo formatTime($quiz['time_limit']); ?></span>
                                    <span>❓ <?php echo $quiz['total_questions']; ?> questions</span>
                                    <span>👥 <?php echo $quiz['total_attempts']; ?> tentatives</span>
                                </div>
                                
                                <div class="quiz-actions">
                                    <a href="/PROJETTECHNOLOGIQUE/pages/edit_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-small btn-primary">
                                        ✏️ Modifier
                                    </a>
                                    <a href="/PROJETTECHNOLOGIQUE/pages/results.php"<?php echo $quiz['id']; ?>" class="btn btn-small btn-warning">
                                        📊 Résultats
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
 
            <div class="recent-attempts">
                <h2>🕒 Dernières Tentatives</h2>
                
                <?php if (empty($recent_attempts)): ?>
                    <div class="no-data">
                        Aucune tentative récente.
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_attempts as $attempt): ?>
                        <div class="attempt-card">
                            <div class="attempt-info">
                                <span class="subject-icon"><?php echo $attempt['subject_icon']; ?></span>
                                <div class="attempt-details">
                                    <h4><?php echo escape($attempt['student_name']); ?></h4>
                                    <div class="attempt-meta">
                                        <?php echo escape($attempt['quiz_title']); ?> • 
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
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
