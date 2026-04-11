<?php
// update_schema.php
require_once 'config/database.php';

try {
    $pdo->exec("ALTER TABLE users 
                ADD COLUMN IF NOT EXISTS grade VARCHAR(100) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS statut VARCHAR(100) DEFAULT NULL,
                ADD COLUMN IF NOT EXISTS corps VARCHAR(100) DEFAULT NULL");
    echo "Schéma mis à jour avec succès !";
} catch (Exception $e) {
    echo "Erreur lors de la mise à jour : " . $e->getMessage();
}
