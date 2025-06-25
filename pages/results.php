<?php
/**
 * Page de résultat de quiz
 */

// Start session
session_start();

// Include basic functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

$attempt_id = (int) ($_GET['attempt_id'] ?? 0);

// Get attempt details
$stmt = $pdo->prepare("
    SELECT qa.*, q.title as quiz_title, q.description, s.name as subject_name, s.icon as subject_icon, 
           u.name as student_name, prof.name as professor_name
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    JOIN subjects s ON q.subject_id = s.id 
    JOIN users u ON qa.student_id = u.id
    JOIN users prof ON q.professor_id = prof.id
    WHERE qa.id = ? AND qa.is_completed = 1
");
$stmt->execute([$attempt_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    setFlashMessage('error', 'Tentative de quiz non trouvée.');
    if (isStudent()) {
        redirect('student-dashboard.php');
    } else {
        redirect('prof-dashboard.php');
    }
}

// Check permissions
if (isStudent() && $attempt['student_id'] != getCurrentUserId()) {
    setFlashMessage('error', 'Vous ne pouvez voir que vos propres résultats.');
    redirect('student-dashboard.php');
}

// Get detailed answers
$stmt = $pdo->prepare("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer
    FROM student_answers sa 
    JOIN questions q ON sa.question_id = q.id 
    WHERE sa.attempt_id = ? 
    ORDER BY q.question_order ASC
");
$stmt->execute([$attempt_id]);
$answers = $stmt->fetchAll();

$percentage = calculatePercentage($attempt['correct_answers'], $attempt['total_questions']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat - <?php echo escape($attempt['quiz_title']); ?></title>
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
            max-width: 900px;
            margin: 0 auto;
        }
        
        .result-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out;
            text-align: center;
        }
        
        .result-header h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2.5em;
            animation: bounce 2s ease-in-out infinite;
        }
        
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            color: white;
            position: relative;
            animation: scaleIn 1s ease-out;
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
        
        .quiz-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .quiz-info-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 1.1em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1em;
        }
        
        .answers-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.4s ease-out;
        }
        
        .answers-section h2 {
            color: #333;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .answer-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 5px solid;
            transition: all 0.3s ease;
        }
        
        .answer-card.correct {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }
        
        .answer-card.incorrect {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        }
        
        .answer-card:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .question-number {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .question-text {
            color: #333;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .answer-options {
            margin-top: 15px;
        }
        
        .option-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 10px;
        }
        
        .option-letter {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 0.9em;
        }
        
        .option-letter.selected {
            background: #007bff;
        }
        
        .option-letter.correct {
            background: #28a745;
        }
        
        .option-letter.incorrect {
            background: #dc3545;
        }
        
        .option-letter.default {
            background: #6c757d;
        }
        
        .option-text {
            flex: 1;
            color: #333;
        }
        
        .result-status {
            margin-top: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
        }
        
        .result-status.correct {
            background: rgba(40, 167, 69, 0.2);
            color: #155724;
        }
        
        .result-status.incorrect {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }
        
        .actions-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.6s ease-out;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
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
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
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
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .quiz-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .option-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
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
                <?php if (isStudent()): ?>
                    <a href="student-dashboard.php">Tableau de bord</a>
                    <a href="results.php">Mes Résultats</a>
                <?php else: ?>
                    <a href="prof-dashboard.php">Tableau de bord</a>
                    <a href="results.php">Voir les Résultats</a>
                <?php endif; ?>
                <a href="logout.php">Déconnexion</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Result Header -->
            <div class="result-header">
                <h1><?php echo $attempt['subject_icon']; ?> Résultat du Quiz</h1>
                <h2 style="color: #666; font-size: 1.5em; margin-bottom: 20px;">
                    <?php echo escape($attempt['quiz_title']); ?>
                </h2>
                
                <?php 
                $scoreClass = 'score-poor';
                if ($percentage >= 80) $scoreClass = 'score-excellent';
                elseif ($percentage >= 60) $scoreClass = 'score-good';
                elseif ($percentage >= 40) $scoreClass = 'score-average';
                ?>
                
                <div class="score-circle <?php echo $scoreClass; ?>">
                    <?php echo $percentage; ?>%
                </div>
                
                <div class="quiz-info">
                    <?php if (isProfessor()): ?>
                        <div class="quiz-info-item">
                            <span>👨‍🎓</span>
                            <span><?php echo escape($attempt['student_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="quiz-info-item">
                        <span>📚</span>
                        <span><?php echo escape($attempt['subject_name']); ?></span>
                    </div>
                    <div class="quiz-info-item">
                        <span>📅</span>
                        <span><?php echo date('d/m/Y à H:i', strtotime($attempt['completed_at'])); ?></span>
                    </div>
                    <div class="quiz-info-item">
                        <span>⏱️</span>
                        <span><?php echo formatTime($attempt['time_taken']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $attempt['correct_answers']; ?></div>
                    <div class="stat-label">Bonnes réponses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-number"><?php echo $attempt['total_questions'] - $attempt['correct_answers']; ?></div>
                    <div class="stat-label">Mauvaises réponses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">❓</div>
                    <div class="stat-number"><?php echo $attempt['total_questions']; ?></div>
                    <div class="stat-label">Total questions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo $percentage; ?>%</div>
                    <div class="stat-label">Score final</div>
                </div>
            </div>

            <!-- Detailed Answers -->
            <div class="answers-section">
                <h2>📝 Détail des Réponses</h2>
                
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="answer-card <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                        <div class="question-header">
                            <div class="question-number"><?php echo $index + 1; ?></div>
                            <div class="question-text">
                                <?php echo escape($answer['question_text']); ?>
                            </div>
                        </div>
                        
                        <div class="answer-options">
                            <?php 
                            $options = ['A' => $answer['option_a'], 'B' => $answer['option_b'], 'C' => $answer['option_c'], 'D' => $answer['option_d']];
                            foreach ($options as $letter => $text): 
                                $class = 'default';
                                if ($letter === $answer['selected_answer']) {
                                    $class = $answer['is_correct'] ? 'correct' : 'incorrect';
                                } elseif ($letter === $answer['correct_answer']) {
                                    $class = 'correct';
                                }
                            ?>
                                <div class="option-row">
                                    <div class="option-letter <?php echo $class; ?>"><?php echo $letter; ?></div>
                                    <div class="option-text"><?php echo escape($text); ?></div>
                                    <?php if ($letter === $answer['correct_answer']): ?>
                                        <span style="color: #28a745; font-weight: bold;">✓ Bonne réponse</span>
                                    <?php endif; ?>
                                    <?php if ($letter === $answer['selected_answer'] && !$answer['is_correct']): ?>
                                        <span style="color: #dc3545; font-weight: bold;">✗ Votre réponse</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="result-status <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                            <?php if ($answer['is_correct']): ?>
                                ✅ Correct ! Vous avez choisi la bonne réponse.
                            <?php else: ?>
                                ❌ Incorrect. Vous avez choisi "<?php echo $answer['selected_answer']; ?>" mais la bonne réponse était "<?php echo $answer['correct_answer']; ?>".
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Actions -->
            <div class="actions-section">
                <h2>🎯 Que faire maintenant ?</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    <?php if ($percentage >= 80): ?>
                        Excellent travail ! Vous maîtrisez bien cette matière.
                    <?php elseif ($percentage >= 60): ?>
                        Bon travail ! Il y a encore quelques points à améliorer.
                    <?php elseif ($percentage >= 40): ?>
                        Pas mal, mais vous devriez réviser certains points.
                    <?php else: ?>
                        Il serait bon de réviser cette matière avant de retenter un quiz.
                    <?php endif; ?>
                </p>
                
                <?php if (isStudent()): ?>
                    <a href="student-dashboard.php" class="btn btn-success">
                        🏠 Retour au tableau de bord
                    </a>
                    <a href="results.php" class="btn btn-secondary">
                        📊 Voir tous mes résultats
                    </a>
                <?php else: ?>
                    <a href="prof-dashboard.php" class="btn btn-success">
                        🏠 Retour au tableau de bord
                    </a>
                    <a href="results.php" class="btn btn-secondary">
                        📊 Voir tous les résultats
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>