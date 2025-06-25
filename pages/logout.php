 <?php

// Start session
session_start();

// Include basic functions
require_once '../includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Logout user
    logoutUser();
    
    // Set success message for next page
    session_start();
    setFlashMessage('success', 'Vous avez été déconnecté avec succès.');
}

// Redirect to home page
redirect('../index.php');
?>