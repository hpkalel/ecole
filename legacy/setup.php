<?php
// setup.php
require_once 'config/database.php';

echo "<h1>Installation de la base de données...</h1>";

try {
    $sql = file_get_contents('database_setup.sql');
    
    if (!$sql) {
        die("Erreur : Impossible de lire le fichier database_setup.sql");
    }

    // Exécution des requêtes
    $pdo->exec($sql);
    
    echo "<p style='color: green;'>Base de données et tables créées avec succès !</p>";
    
    // Création d'un compte admin par défaut si aucun n'existe
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $username = 'directeur';
        $password = 'admin123'; // À changer
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $nom = 'Directeur';
        
        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, nom, role) VALUES (?, ?, ?, 'admin')");
        $insert->execute([$username, $hash, $nom]);
        
        echo "<p>Compte Administrateur créé par défaut : <br>User: <strong>$username</strong> <br>Pass: <strong>$password</strong></p>";
    } else {
        echo "<p>Un compte administrateur existe déjà.</p>";
    }
    
    echo "<p><a href='index.php'>Aller à l'accueil</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de l'installation : " . $e->getMessage() . "</p>";
}
?>
