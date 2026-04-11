<?php
require_once 'config/database.php';

try {
    // Ajouter sexe à students
    $pdo->exec("ALTER TABLE students ADD COLUMN sexe ENUM('M', 'F') DEFAULT 'M' AFTER prenom");
    
    // Ajouter statut à student_enrollments
    $pdo->exec("ALTER TABLE student_enrollments ADD COLUMN statut ENUM('Nouveau', 'Redoublant') DEFAULT 'Nouveau' AFTER school_year_id");
    
    echo "Base de données mise à jour avec succès (sexe et statut).";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Les colonnes existent déjà.";
    } else {
        die("Erreur : " . $e->getMessage());
    }
}
?>
