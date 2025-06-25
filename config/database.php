 <?php
 
$servername = "localhost";
$username = "root";           
$password = "";               
$dbname = "quizz";  

 
$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
);

try {
    // Création de la connexion PDO
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password, $options);
    
    // Message de succès (optionnel, à retirer en production)
    // echo "Connexion réussie à la base de données !";
    
} catch(PDOException $e) {
    // Gestion des erreurs de connexion
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour obtenir la connexion (optionnelle)
function getConnection() {
    global $pdo;
    return $pdo;
}
?>