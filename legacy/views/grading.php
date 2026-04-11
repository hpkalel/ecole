<?php
// views/grading.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prof') {
    header("Location: login.php");
    exit;
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) die("ID manquant.");

// Vérifier que l'assignment appartient bien au prof
$stmt = $pdo->prepare("
    SELECT a.*, c.nom as class_name, s.nom as subject_name 
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.id = ? AND a.prof_id = ?
");
$stmt->execute([$assignment_id, $_SESSION['user_id']]);
$assignment = $stmt->fetch();

if (!$assignment) die("Accès refusé.");

$message = "";

// Sauvegarde des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = $_POST['notes'] ?? []; // array [student_id => val]
    $comments = $_POST['comments'] ?? []; // array [student_id => text]
    
    $pdo->beginTransaction();
    try {
        foreach ($notes as $student_id => $val) {
            // Note vide = on ignore ou on supprime ? Ici on ignore si vide
            if ($val === '') continue;
            
            $comment = $comments[$student_id] ?? '';
            
            // Check si note existe déjà
            $check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND assignment_id = ?");
            $check->execute([$student_id, $assignment_id]);
            $exists = $check->fetch();
            
            if ($exists) {
                $update = $pdo->prepare("UPDATE grades SET valeur = ?, appreciation = ? WHERE id = ?");
                $update->execute([$val, $comment, $exists['id']]);
            } else {
                $insert = $pdo->prepare("INSERT INTO grades (student_id, assignment_id, valeur, appreciation) VALUES (?, ?, ?, ?)");
                $insert->execute([$student_id, $assignment_id, $val, $comment]);
            }
        }
        $pdo->commit();
        $message = "Notes enregistrées avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les élèves et leurs notes actuelles
$students = $pdo->prepare("
    SELECT s.id, s.nom, s.prenom, g.valeur, g.appreciation
    FROM students s
    LEFT JOIN grades g ON s.id = g.student_id AND g.assignment_id = ?
    WHERE s.class_id = ?
    ORDER BY s.nom, s.prenom
");
$students->execute([$assignment_id, $assignment['class_id']]);
$list = $students->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisie des Notes</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .grade-input { width: 80px !important; text-align: center; }
        .comment-input { width: 100%; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #E5E7EB; vertical-align: middle; }
        tr:hover { background-color: #F9FAFB; }
    </style>
</head>
<body>

<nav style="background: white; padding: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="color: gray; font-size: 0.9rem;">Classe</span>
            <h1 style="margin: 0; font-size: 1.5rem;"><?php echo htmlspecialchars($assignment['class_name'] . " - " . $assignment['subject_name']); ?></h1>
        </div>
        <a href="prof_dashboard.php" class="btn btn-secondary">Retour</a>
    </div>
</nav>

<div class="container">
    
    <div class="card mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3>Liste des Élèves</h3>
            <?php if ($message): ?>
                <span style="color: green; font-weight: bold;"><?php echo $message; ?></span>
            <?php endif; ?>
        </div>

        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th style="width: 30%;">Élève</th>
                        <th style="width: 15%;">Note / 20</th>
                        <th>Appréciation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $s): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($s['nom']); ?></strong> 
                                <?php echo htmlspecialchars($s['prenom']); ?>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="0" max="20" 
                                       name="notes[<?php echo $s['id']; ?>]" 
                                       value="<?php echo $s['valeur']; ?>" 
                                       class="grade-input" placeholder="-">
                            </td>
                            <td>
                                <input type="text" 
                                       name="comments[<?php echo $s['id']; ?>]" 
                                       value="<?php echo htmlspecialchars($s['appreciation'] ?? ''); ?>" 
                                       class="comment-input" placeholder="Commentaire...">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 1.5rem; text-align: right;">
                <button type="submit" class="btn" style="padding: 1rem 2rem;">Enregistrer les Notes</button>
            </div>
        </form>
    </div>

</div>

</body>
</html>
