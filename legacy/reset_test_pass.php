<?php
require_once 'c:/wamp64/www/projets/ecole2/config/database.php';
$hash = password_hash('password', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'ad'")->execute([$hash]);
echo "PASSWORD_RESET_SUCCESS\n";
?>
