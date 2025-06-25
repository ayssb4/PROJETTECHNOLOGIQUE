<?php

 
session_start();
 
require_once '../config/database.php';
require_once '../includes/functions.php';

 
requireProfessor();

$quiz_id = (int) ($_GET['id'] ?? 0);
$error = '';
$success = '';
 
$stmt = $pdo->prepare("
    SELECT q.*, s.name as subject_name 
    FROM quizzes q 
    JOIN subjects s ON q.subject_id = s.id 
    WHERE q.id = ? AND q.professor_id = ?
");
$stmt->execute([$quiz_id, getCurrentUserId()]);
$quiz = $stmt->fetch();

if (!$quiz) {
    setFlashMessage('error', 'Quiz non trouvé ou vous n\'avez pas les permissions pour le modifier.');
    redirect('prof-dashboard.php');
}


$stmt = $pdo->prepare("
    SELECT * FROM questions 
    WHERE quiz_id = ? 
    ORDER BY question_order ASC
");
$stmt->execute([$quiz_id]);
$existing_questions = $stmt->fetchAll();

 
$subjects = getSubjects($pdo);

 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $subject_id = (int) ($_POST['subject_id'] ?? 0);
    $time_limit = (int) ($_POST['time_limit'] ?? 600);
    $questions = $_POST['questions'] ?? [];
    
   
    if (empty($title) || $subject_id <= 0) {
        $error = 'Le titre et la matière sont obligatoires.';
    } elseif (empty($questions) || count($questions) < 1) {
        $error = 'Vous devez avoir au moins une question.';
    } else {
       
        $validQuestions = 0;
        foreach ($questions as $q) {
            if (!empty($q['question']) && !empty($q['option_a']) && !empty($q['option_b']) && 
                !empty($q['option_c']) && !empty($q['option_d']) && !empty($q['correct'])) {
                $validQuestions++;
            }
        }
        
        if ($validQuestions === 0) {
            $error = 'Toutes les questions doivent être complètement remplies.';
        } else {
            try {
                $pdo->beginTransaction();
                
                
                $stmt = $pdo->prepare("
                    UPDATE quizzes 
                    SET title = ?, description = ?, subject_id = ?, time_limit = ?, total_questions = ? 
                    WHERE id = ? AND professor_id = ?
                ");
                $stmt->execute([$title, $description, $subject_id, $time_limit, $validQuestions, $quiz_id, getCurrentUserId()]);
                
                
                $stmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
                $stmt->execute([$quiz_id]);
                
                 
                $stmt = $pdo->prepare("
                    INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, question_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $order = 1;
                foreach ($questions as $q) {
                    if (!empty($q['question']) && !empty($q['option_a']) && !empty($q['option_b']) && 
                        !empty($q['option_c']) && !empty($q['option_d']) && !empty($q['correct'])) {
                        $stmt->execute([
                            $quiz_id,
                            $q['question'],
                            $q['option_a'],
                            $q['option_b'],
                            $q['option_c'],
                            $q['option_d'],
                            strtoupper($q['correct']),
                            $order++
                        ]);
                    }
                }
                
                $pdo->commit();
                setFlashMessage('success', 'Quiz modifié avec succès !');
                redirect('prof-dashboard.php');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Erreur lors de la modification du quiz : ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Quiz - <?php echo escape($quiz['title']); ?></title>
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
        
        .quiz-info-banner {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .quiz-info-banner h2 {
            margin-bottom: 10px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
        }
        
        .form-section h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        input[type="text"],
        textarea,
        select,
        input[type="number"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        input[type="text"]:focus,
        textarea:focus,
        select:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .questions-container {
            margin-top: 20px;
        }
        
        .question-item {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .question-item:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .question-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .remove-question {
            background: linear-gradient(45deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .remove-question:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        
        .option-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .option-radio {
            width: 20px;
            height: 20px;
        }
        
        .option-input {
            flex: 1;
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
        
        .btn-secondary {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #ffc107, #e0a800);
        }
        
        .btn-warning:hover {
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .add-question-btn {
            width: 100%;
            margin-top: 15px;
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
        
        .time-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .time-selector select {
            width: auto;
            min-width: 80px;
        }
        
        .delete-quiz-section {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            padding: 20px;
            border-radius: 15px;
            margin-top: 30px;
            border: 2px solid #dc3545;
        }
        
        .delete-quiz-section h3 {
            color: #721c24;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }
        
        .btn-danger:hover {
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
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
            .form-row {
                flex-direction: column;
            }
            
            .options-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .time-selector {
                flex-direction: column;
                align-items: flex-start;
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
                <a href="prof-dashboard.php">Tableau de bord</a>
                <a href="create-quiz.php">Créer un Quiz</a>
                <a href="logout.php">Déconnexion</a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <h1>✏️ Modifier le Quiz</h1>
            
            <!-- Quiz Info Banner -->
            <div class="quiz-info-banner">
                <h2><?php echo escape($quiz['title']); ?></h2>
                <p>Matière: <?php echo escape($quiz['subject_name']); ?> • Créé le <?php echo date('d/m/Y', strtotime($quiz['created_at'])); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo escape($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="quizForm">
                <!-- Quiz Information -->
                <div class="form-section">
                    <h2>📋 Informations du Quiz</h2>
                    
                    <div class="form-group">
                        <label for="title">Titre du quiz :</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo escape($_POST['title'] ?? $quiz['title']); ?>"
                               placeholder="Ex: Quiz de Mathématiques - Chapitre 1">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (optionnelle) :</label>
                        <textarea id="description" name="description" 
                                  placeholder="Décrivez brièvement le contenu du quiz..."><?php echo escape($_POST['description'] ?? $quiz['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_id">Matière :</label>
                            <select id="subject_id" name="subject_id" required>
                                <option value="">Choisir une matière</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" 
                                            <?php echo (($_POST['subject_id'] ?? $quiz['subject_id']) == $subject['id']) ? 'selected' : ''; ?>>
                                        <?php echo $subject['icon'] . ' ' . escape($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_limit">Temps limite :</label>
                            <div class="time-selector">
                                <select id="time_minutes" name="time_minutes">
                                    <?php for ($i = 1; $i <= 60; $i++): 
                                        $selected_minutes = ($_POST['time_limit'] ?? $quiz['time_limit']) / 60;
                                    ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($i == $selected_minutes) ? 'selected' : ''; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <span>minutes</span>
                                <input type="hidden" id="time_limit" name="time_limit" value="<?php echo $_POST['time_limit'] ?? $quiz['time_limit']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Questions Section -->
                <div class="form-section">
                    <h2>❓ Questions</h2>
                    
                    <div class="questions-container" id="questionsContainer">
                        <!-- Existing questions will be loaded here by JavaScript -->
                    </div>
                    
                    <button type="button" class="btn btn-success add-question-btn" onclick="addQuestion()">
                        ➕ Ajouter une Question
                    </button>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="prof-dashboard.php" class="btn btn-secondary">
                        ↩️ Annuler
                    </a>
                    <button type="submit" class="btn btn-warning">
                        ✅ Sauvegarder les Modifications
                    </button>
                </div>
            </form>
            
            <!-- Delete Quiz Section -->
            <div class="delete-quiz-section">
                <h3>⚠️ Zone Dangereuse</h3>
                <p style="color: #721c24; margin-bottom: 15px;">
                    Supprimer ce quiz effacera définitivement toutes les questions et les résultats des étudiants.
                </p>
                <button type="button" class="btn btn-danger" onclick="deleteQuiz()">
                    🗑️ Supprimer ce Quiz
                </button>
            </div>
        </div>
    </main>

    <script>
        let questionCount = 0;
        
        // Existing questions data from PHP
        const existingQuestions = <?php echo json_encode($existing_questions); ?>;
        
        // Load existing questions when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadExistingQuestions();
            updateTimeLimit();
        });
        
        // Update time limit when minutes change
        document.getElementById('time_minutes').addEventListener('change', updateTimeLimit);
        
        function updateTimeLimit() {
            const minutes = parseInt(document.getElementById('time_minutes').value);
            document.getElementById('time_limit').value = minutes * 60;
        }
        
        function loadExistingQuestions() {
            existingQuestions.forEach(function(question) {
                addQuestion(question);
            });
            
            // If no existing questions, add one empty question
            if (existingQuestions.length === 0) {
                addQuestion();
            }
        }
        
        function addQuestion(questionData = null) {
            questionCount++;
            const container = document.getElementById('questionsContainer');
            
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-item';
            questionDiv.id = 'question_' + questionCount;
            
            const questionText = questionData ? questionData.question_text : '';
            const optionA = questionData ? questionData.option_a : '';
            const optionB = questionData ? questionData.option_b : '';
            const optionC = questionData ? questionData.option_c : '';
            const optionD = questionData ? questionData.option_d : '';
            const correctAnswer = questionData ? questionData.correct_answer : '';
            
            questionDiv.innerHTML = 
                '<div class="question-header">' +
                    '<span class="question-number">Question ' + questionCount + '</span>' +
                    '<button type="button" class="remove-question" onclick="removeQuestion(' + questionCount + ')" ' + (questionCount === 1 ? 'style="display:none"' : '') + '>' +
                        '🗑️ Supprimer' +
                    '</button>' +
                '</div>' +
                
                '<div class="form-group">' +
                    '<label>Énoncé de la question :</label>' +
                    '<input type="text" name="questions[' + questionCount + '][question]" required placeholder="Posez votre question ici..." value="' + questionText + '">' +
                '</div>' +
                
                '<div class="options-grid">' +
                    '<div class="option-group">' +
                        '<input type="radio" name="questions[' + questionCount + '][correct]" value="A" required class="option-radio" ' + (correctAnswer === 'A' ? 'checked' : '') + '>' +
                        '<input type="text" name="questions[' + questionCount + '][option_a]" required placeholder="Option A" class="option-input" value="' + optionA + '">' +
                    '</div>' +
                    
                    '<div class="option-group">' +
                        '<input type="radio" name="questions[' + questionCount + '][correct]" value="B" class="option-radio" ' + (correctAnswer === 'B' ? 'checked' : '') + '>' +
                        '<input type="text" name="questions[' + questionCount + '][option_b]" required placeholder="Option B" class="option-input" value="' + optionB + '">' +
                    '</div>' +
                    
                    '<div class="option-group">' +
                        '<input type="radio" name="questions[' + questionCount + '][correct]" value="C" class="option-radio" ' + (correctAnswer === 'C' ? 'checked' : '') + '>' +
                        '<input type="text" name="questions[' + questionCount + '][option_c]" required placeholder="Option C" class="option-input" value="' + optionC + '">' +
                    '</div>' +
                    
                    '<div class="option-group">' +
                        '<input type="radio" name="questions[' + questionCount + '][correct]" value="D" class="option-radio" ' + (correctAnswer === 'D' ? 'checked' : '') + '>' +
                        '<input type="text" name="questions[' + questionCount + '][option_d]" required placeholder="Option D" class="option-input" value="' + optionD + '">' +
                    '</div>' +
                '</div>' +
                
                '<p style="margin-top: 10px; color: #666; font-size: 0.9em;">' +
                    '💡 Sélectionnez le bouton radio correspondant à la bonne réponse' +
                '</p>';
            
            container.appendChild(questionDiv);
            
            // Show remove button on first question if there are now multiple questions
            const allQuestions = document.querySelectorAll('.question-item');
            if (allQuestions.length > 1) {
                allQuestions.forEach(function(q) {
                    const removeBtn = q.querySelector('.remove-question');
                    if (removeBtn) {
                        removeBtn.style.display = 'block';
                    }
                });
            }
        }
        
        function removeQuestion(questionId) {
            const questionElement = document.getElementById('question_' + questionId);
            if (questionElement) {
                questionElement.remove();
                
                // Renumber remaining questions
                const remainingQuestions = document.querySelectorAll('.question-item');
                remainingQuestions.forEach(function(question, index) {
                    const questionNumber = question.querySelector('.question-number');
                    if (questionNumber) {
                        questionNumber.textContent = 'Question ' + (index + 1);
                    }
                });
                
                // Hide remove button if only one question remains
                if (remainingQuestions.length === 1) {
                    const removeBtn = remainingQuestions[0].querySelector('.remove-question');
                    if (removeBtn) {
                        removeBtn.style.display = 'none';
                    }
                }
            }
        }
        
        function deleteQuiz() {
            if (confirm('⚠️ ATTENTION: Êtes-vous absolument sûr de vouloir supprimer ce quiz ?\n\nCette action supprimera définitivement :\n- Toutes les questions\n- Tous les résultats des étudiants\n- Toutes les données associées\n\nCette action est IRRÉVERSIBLE !')) {
                if (confirm('Dernière confirmation : Tapez "SUPPRIMER" dans votre tête et cliquez sur OK pour confirmer la suppression définitive du quiz.')) {
                    window.location.href = 'delete-quiz.php?id=<?php echo $quiz_id; ?>';
                }
            }
        }
        
        // Form validation before submit
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('.question-item');
            let hasValidQuestion = false;
            
            questions.forEach(function(question) {
                const questionText = question.querySelector('input[name*="[question]"]').value.trim();
                const optionA = question.querySelector('input[name*="[option_a]"]').value.trim();
                const optionB = question.querySelector('input[name*="[option_b]"]').value.trim();
                const optionC = question.querySelector('input[name*="[option_c]"]').value.trim();
                const optionD = question.querySelector('input[name*="[option_d]"]').value.trim();
                const correctAnswer = question.querySelector('input[name*="[correct]"]:checked');
                
                if (questionText && optionA && optionB && optionC && optionD && correctAnswer) {
                    hasValidQuestion = true;
                }
            });
            
            if (!hasValidQuestion) {
                e.preventDefault();
                alert('Veuillez remplir complètement au moins une question avec toutes ses options et indiquer la bonne réponse.');
            }
        });
    </script>
</body>
</html>
