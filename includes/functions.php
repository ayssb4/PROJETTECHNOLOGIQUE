 <?php
 
if (!isset($_SESSION)) {
    session_start();
}

// Fonction pour échapper les données HTML
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction pour rediraction
function redirect($url) {
    header("Location: $url");
    exit();
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est un professeur
function isProfessor() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'professor';
}

// Fonction pour vérifier si l'utilisateur est un étudiant
function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student';
}

// Fonction pour obtenir l'ID de l'utilisateur connecté
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Fonction pour obtenir le nom de l'utilisateur connecté
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? null;
}

// Fonction pour définir un message flash
function setFlashMessage($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

// Fonction pour récupérer et supprimer un message flash
function getFlashMessage($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

// Fonction pour valider l'email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Fonction pour hasher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fonction pour vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fonction pour requérir une connexion
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Vous devez être connecté pour accéder à cette page.');
        redirect('../pages/login.php');
    }
}

// Fonction pour requérir le rôle professeur
function requireProfessor() {
    requireLogin();
    if (!isProfessor()) {
        setFlashMessage('error', 'Accès réservé aux professeurs.');
        redirect('../pages/student-dashboard.php');
    }
}

// Fonction pour requérir le rôle étudiant
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        setFlashMessage('error', 'Accès réservé aux étudiants.');
        redirect('../pages/prof-dashboard.php');
    }
}

// Fonction pour formater le temps
function formatTime($seconds) {
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $seconds);
}

// Fonction pour calculer le pourcentage
function calculatePercentage($correct, $total) {
    if ($total == 0) return 0;
    return round(($correct / $total) * 100, 2);
}

// Fonction pour obtenir la couleur du score
function getScoreColor($percentage) {
    if ($percentage >= 80) return '#28a745'; // Vert
    if ($percentage >= 60) return '#ffc107'; // Jaune
    if ($percentage >= 40) return '#fd7e14'; // Orange
    return '#dc3545'; // Rouge
}

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour vérifier la force du mot de passe
function isStrongPassword($password) {
    return strlen($password) >= 6;
}

// Fonction pour obtenir les matières
function getSubjects($pdo) {
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
    return $stmt->fetchAll();
}

// Fonction pour obtenir un utilisateur par email
function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

// Fonction pour créer un nouvel utilisateur
function createUser($pdo, $email, $password, $name, $role) {
    $hashedPassword = hashPassword($password);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$email, $hashedPassword, $name, $role]);
}

// Fonction pour connecter un utilisateur
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
}

// Fonction pour déconnecter un utilisateur
function logoutUser() {
    session_destroy();
}

?>
