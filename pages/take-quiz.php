<?php
/**
 * Page pour passer un quiz
 */

// Start session
session_start();

// Include basic functions
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require student login
requireStudent();

$quiz_id = (int) ($_GET['id'] ?? 0);
$error = '';

// Get quiz information
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name, s.icon as subject_icon, u.name as professor_name 
    FROM quizzes q 
    JOIN subjects s ON q.subject_id = s.id 
    JOIN users u ON q.professor_id = u.id 
    WHERE q.id = ? AND q.is_active = 1
");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    setFlashMessage('error', 'Quiz non trouvé ou non disponible.');
    redirect('student-dashboard.php');
}

// Check if student already completed this quiz
$stmt = $pdo->prepare("
    SELECT * FROM quiz_attempts 
    WHERE student_id = ? AND quiz_id = ? AND is_completed = 1
");
$stmt->execute([getCurrentUserId(), $quiz_id]);
$existing_attempt = $stmt->fetch();

// Get quiz questions
$stmt = $pdo->prepare("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY question_order ASC
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    setFlashMessage('error', 'Ce quiz ne contient aucune question.');
    redirect('student-dashboard.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Create quiz attempt
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (student_id, quiz_id, total_questions, started_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([getCurrentUserId(), $quiz_id, count($questions)]);
        $attempt_id = $pdo->lastInsertId();
        
        // Process answers
        $correct_count = 0;
        foreach ($questions as $question) {
            $selected_answer = $answers[$question['id']] ?? null;
            $is_correct = ($selected_answer === $question['correct_answer']);
            
            if ($is_correct) {
                $correct_count++;
            }
            
            // Save student answer
            $stmt = $pdo->prepare("
                INSERT INTO student_answers (attempt_id, question_id, selected_answer, is_correct) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$attempt_id, $question['id'], $selected_answer, $is_correct]);
        }
        
        // Calculate score and update attempt
        $score = calculatePercentage($correct_count, count($questions));
        $stmt = $pdo->prepare("
            UPDATE quiz_attempts 
            SET score = ?, correct_answers = ?, completed_at = NOW(), is_completed = 1 
            WHERE id = ?
        ");
        $stmt->execute([$score, $correct_count, $attempt_id]);
        
        $pdo->commit();
        
        // Redirect to results
        redirect("quiz_result.php?attempt_id=$attempt_id");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Erreur lors de la soumission du quiz : ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($quiz['title']); ?> - Quiz</title>
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
        
        .timer {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .timer.warning {
            background: rgba(255, 193, 7, 0.8);
            animation: pulse 1s infinite;
        }
        
        .timer.danger {
            background: rgba(220, 53, 69, 0.8);
            animation: pulse 0.5s infinite;
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
        
        .quiz-header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out;
            text-align: center;
        }
        
        .quiz-header h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 2.5em;
            animation: bounce 2s ease-in-out infinite;
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
        
        .progress-bar {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeInUp 1.2s ease-out;
        }
        
        .progress-bar-fill {
            height: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-weight: bold;
        }
        
        .question-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1.4s ease-out;
        }
        
        .question-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .question-number {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .question-text {
            color: #333;
            font-size: 1.3em;
            line-height: 1.5;
        }
        
        .options-container {
            margin-top: 25px;
        }
        
        .option {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            margin-bottom: 15px;
            border-radius: 15px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .option:hover {
            border-color: #667eea;
            transform: translateX(10px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
        }
        
        .option-content {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .option-letter {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .option-text {
            color: #333;
            font-size: 1.1em;
            flex: 1;
        }
        
        .option input[type="radio"]:checked + .option-content {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .option input[type="radio"]:checked + .option-content .option-letter {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .quiz-actions {
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
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
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
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
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
        
        .alert-warning {
            background: linear-gradient(45deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .completed-message {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: fadeInUp 1s ease-out;
        }
        
        .completed-message h2 {
            color: #28a745;
            margin-bottom: 20px;
            font-size: 2em;
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
            .quiz-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .option:hover {
                transform: none;
            }
            
            .nav {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <a href="../index.php" class="logo"> 🎓K&A Quiz Show</a>
            <div class="timer" id="timer">
                ⏱️ <span id="timeLeft"><?php echo formatTime($quiz['time_limit']); ?></span>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($existing_attempt): ?>
                <div class="completed-message">
                    <h2>✅ Quiz Déjà Complété</h2>
                    <p>Vous avez déjà passé ce quiz avec un score de <strong><?php echo calculatePercentage($existing_attempt['correct_answers'], $existing_attempt['total_questions']); ?>%</strong></p>
                    <p style="margin-top: 20px;">
                        <a href="student-dashboard.php" class="btn btn-secondary">
                            ↩️ Retour au tableau de bord
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <!-- Quiz Header -->
                <div class="quiz-header">
                    <h1><?php echo $quiz['subject_icon'] . ' ' . escape($quiz['title']); ?></h1>
                    <p style="color: #666; margin-bottom: 20px;">
                        <?php echo escape($quiz['description']); ?>
                    </p>
                    
                    <div class="quiz-info">
                        <div class="quiz-info-item">
                            <span>📚</span>
                            <span><?php echo escape($quiz['subject_name']); ?></span>
                        </div>
                        <div class="quiz-info-item">
                            <span>👨‍🏫</span>
                            <span><?php echo escape($quiz['professor_name']); ?></span>
                        </div>
                        <div class="quiz-info-item">
                            <span>❓</span>
                            <span><?php echo count($questions); ?> questions</span>
                        </div>
                        <div class="quiz-info-item">
                            <span>⏱️</span>
                            <span><?php echo formatTime($quiz['time_limit']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar">
                    <div class="progress-text">
                        Question <span id="currentQuestion">1</span> sur <?php echo count($questions); ?>
                    </div>
                    <div style="background: #e9ecef; border-radius: 5px;">
                        <div class="progress-bar-fill" id="progressBar" style="width: <?php echo (1/count($questions))*100; ?>%;"></div>
                    </div>
                </div>

                <!-- Quiz Form -->
                <form method="POST" action="" id="quizForm">
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card" id="question_<?php echo $index + 1; ?>" 
                             style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                            <div class="question-header">
                                <div class="question-number"><?php echo $index + 1; ?></div>
                                <div class="question-text">
                                    <?php echo escape($question['question_text']); ?>
                                </div>
                            </div>
                            
                            <div class="options-container">
                                <label class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A" required>
                                    <div class="option-content">
                                        <div class="option-letter">A</div>
                                        <div class="option-text"><?php echo escape($question['option_a']); ?></div>
                                    </div>
                                </label>
                                
                                <label class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B">
                                    <div class="option-content">
                                        <div class="option-letter">B</div>
                                        <div class="option-text"><?php echo escape($question['option_b']); ?></div>
                                    </div>
                                </label>
                                
                                <label class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C">
                                    <div class="option-content">
                                        <div class="option-letter">C</div>
                                        <div class="option-text"><?php echo escape($question['option_c']); ?></div>
                                    </div>
                                </label>
                                
                                <label class="option">
                                    <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D">
                                    <div class="option-content">
                                        <div class="option-letter">D</div>
                                        <div class="option-text"><?php echo escape($question['option_d']); ?></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Navigation Buttons -->
                    <div class="quiz-actions">
                        <button type="button" id="prevBtn" class="btn btn-secondary" onclick="changeQuestion(-1)" style="display: none;">
                            ← Précédent
                        </button>
                        <button type="button" id="nextBtn" class="btn" onclick="changeQuestion(1)">
                            Suivant →
                        </button>
                        <button type="submit" id="submitBtn" class="btn" style="display: none;">
                            ✅ Terminer le Quiz
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        let currentQuestionIndex = 0;
        const totalQuestions = <?php echo count($questions); ?>;
        let timeLeft = <?php echo $quiz['time_limit']; ?>; // in seconds
        let timerInterval;
        
        // Start timer when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startTimer();
        });
        
        function startTimer() {
            timerInterval = setInterval(function() {
                timeLeft--;
                updateTimerDisplay();
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert('Temps écoulé ! Le quiz sera automatiquement soumis.');
                    document.getElementById('quizForm').submit();
                }
            }, 1000);
        }
        
        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const timeString = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            document.getElementById('timeLeft').textContent = timeString;
            
            const timer = document.getElementById('timer');
            if (timeLeft <= 60) {
                timer.className = 'timer danger';
            } else if (timeLeft <= 300) { // 5 minutes
                timer.className = 'timer warning';
            }
        }
        
        function changeQuestion(direction) {
            const currentQuestion = document.getElementById(`question_${currentQuestionIndex + 1}`);
            
            // Validate current question if moving forward
            if (direction > 0) {
                const selectedAnswer = currentQuestion.querySelector('input[type="radio"]:checked');
                if (!selectedAnswer) {
                    alert('Veuillez sélectionner une réponse avant de continuer.');
                    return;
                }
            }
            
            // Hide current question
            currentQuestion.style.display = 'none';
            
            // Update question index
            currentQuestionIndex += direction;
            
            // Show next question
            const nextQuestion = document.getElementById(`question_${currentQuestionIndex + 1}`);
            nextQuestion.style.display = 'block';
            
            // Update progress bar
            const progressPercentage = ((currentQuestionIndex + 1) / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = `${progressPercentage}%`;
            document.getElementById('currentQuestion').textContent = currentQuestionIndex + 1;
            
            // Update navigation buttons
            updateNavigationButtons();
            
            // Scroll to top
            window.scrollTo(0, 0);
        }
        
        function updateNavigationButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            // Show/hide previous button
            if (currentQuestionIndex > 0) {
                prevBtn.style.display = 'inline-block';
            } else {
                prevBtn.style.display = 'none';
            }
            
            // Show/hide next and submit buttons
            if (currentQuestionIndex === totalQuestions - 1) {
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'inline-block';
            } else {
                nextBtn.style.display = 'inline-block';
                submitBtn.style.display = 'none';
            }
        }
        
        // Auto-advance on option selection (optional)
        document.addEventListener('change', function(e) {
            if (e.target.type === 'radio') {
                // Add a small delay before auto-advancing
                setTimeout(function() {
                    if (currentQuestionIndex < totalQuestions - 1) {
                        changeQuestion(1);
                    }
                }, 500);
            }
        });
        
        // Prevent accidental page reload
        window.addEventListener('beforeunload', function(e) {
            if (timeLeft > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // Form submission confirmation
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir terminer le quiz ? Cette action est irréversible.')) {
                e.preventDefault();
                return false;
            }
            
            clearInterval(timerInterval);
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentQuestionIndex > 0) {
                changeQuestion(-1);
            } else if (e.key === 'ArrowRight' && currentQuestionIndex < totalQuestions - 1) {
                const currentQuestion = document.getElementById(`question_${currentQuestionIndex + 1}`);
                const selectedAnswer = currentQuestion.querySelector('input[type="radio"]:checked');
                if (selectedAnswer) {
                    changeQuestion(1);
                }
            } else if (e.key >= '1' && e.key <= '4') {
                // Select option with number keys
                const currentQuestion = document.getElementById(`question_${currentQuestionIndex + 1}`);
                const options = currentQuestion.querySelectorAll('input[type="radio"]');
                const optionIndex = parseInt(e.key) - 1;
                if (options[optionIndex]) {
                    options[optionIndex].checked = true;
                    // Auto-advance after selection
                    setTimeout(function() {
                        if (currentQuestionIndex < totalQuestions - 1) {
                            changeQuestion(1);
                        }
                    }, 300);
                }
            }
        });
    </script>
</body>
</html>