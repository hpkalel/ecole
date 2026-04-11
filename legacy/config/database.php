<?php
// config/database.php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'ecole_bulletins');
define('DB_USER', 'root'); // Par défaut sur WAMP
define('DB_PASS', '');     // Par défaut sur WAMP

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la base n'existe pas encore, on tente de se connecter sans dbname pour pouvoir lancer le script de setup
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $ex) {
            die("Erreur de connexion MySQL : " . $ex->getMessage());
        }
    } else {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
