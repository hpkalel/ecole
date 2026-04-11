<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: views/admin_dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'prof') {
        header('Location: views/prof_dashboard.php');
        exit;
    } else {
        session_destroy();
    }
}

// Tous les autres cas (non connecté ou rôle inconnu) arrivent ici
header('Location: views/login.php');
exit;
?>
