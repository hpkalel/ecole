<?php
require_once 'c:/wamp64/www/projets/ecole2/config/database.php';

// Check for existing prof
$stmt = $pdo->query("SELECT username, role, nom FROM users WHERE role = 'prof' LIMIT 1");
$prof = $stmt->fetch();

if ($prof) {
    echo "PROF_FOUND: " . $prof['username'] . "\n";
} else {
    // Create an invitation and then a prof
    $code = "TEST_PROF_123";
    $pdo->exec("INSERT IGNORE INTO invitations (code, is_used) VALUES ('$code', 0)");
    echo "INVITATION_CREATED: $code\n";
}

// Also check for an assignment to visit evaluations_list.php
$stmt = $pdo->query("SELECT id FROM assignments LIMIT 1");
$assignment = $stmt->fetch();
if ($assignment) {
    echo "ASSIGNMENT_ID: " . $assignment['id'] . "\n";
} else {
    echo "NO_ASSIGNMENT_FOUND\n";
}
?>
