<?php
// views/enter_grades.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prof') {
    header("Location: login.php");
    exit;
}

// Vérifier si le compte est toujours actif
$checkStatus = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
$checkStatus->execute([$_SESSION['user_id']]);
$userStatus = $checkStatus->fetchColumn();

if (!$userStatus) {
    session_destroy();
    header("Location: login.php?error=inactive");
    exit;
}

$evaluation_id = $_GET['evaluation_id'] ?? null;
if (!$evaluation_id) die("ID manquant.");

// Récupérer les infos de l'évaluation + vérification droits (via assignments)
$stmt = $pdo->prepare("
    SELECT e.*, a.prof_id, c.nom as class_name, s.nom as subject_name, c.id as class_id, a.id as assignment_id, a.school_year_id
    FROM evaluations e
    JOIN assignments a ON e.assignment_id = a.id
    JOIN classes c ON a.class_id = c.id
    JOIN subjects s ON a.subject_id = s.id
    WHERE e.id = ?
");
$stmt->execute([$evaluation_id]);
$evaluation = $stmt->fetch();

if (!$evaluation || $evaluation['prof_id'] != $_SESSION['user_id']) {
    die("Accès refusé ou évaluation introuvable.");
}

// Vérifier si l'année de l'évaluation est l'année active
$stmtActiveYear = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$activeYearId = $stmtActiveYear->fetchColumn();

if ($evaluation['school_year_id'] != $activeYearId) {
    header("Location: prof_dashboard.php?msg=year_changed");
    exit;
}

$message = "";

// Sauvegarde des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = $_POST['notes'] ?? []; // array [student_id => val]
    $appreciations = $_POST['appreciations'] ?? []; // array [student_id => text]
    
    $pdo->beginTransaction();
    try {
        foreach ($notes as $student_id => $val) {
            // Si la note est vide, on peut vouloir supprimer une note existante ou juste ignorer.
            // Simplification : si vide et existe, on supprime. Si vide et existe pas, on ignore.
            
            $appreciation = $appreciations[$student_id] ?? '';

            // Check si existe
            $check = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND evaluation_id = ?");
            $check->execute([$student_id, $evaluation_id]);
            $exists = $check->fetch();

            if ($val === '') {
                if ($exists) {
                    $pdo->prepare("DELETE FROM grades WHERE id = ?")->execute([$exists['id']]);
                }
            } else {
                if ($exists) {
                    $update = $pdo->prepare("UPDATE grades SET valeur = ?, appreciation = ? WHERE id = ?");
                    $update->execute([$val, $appreciation, $exists['id']]);
                } else {
                    $insert = $pdo->prepare("INSERT INTO grades (student_id, evaluation_id, valeur, appreciation) VALUES (?, ?, ?, ?)");
                    $insert->execute([$student_id, $evaluation_id, $val, $appreciation]);
                }
            }
        }
        $pdo->commit();
        $message = "Notes enregistrées avec succès !";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les élèves et leurs notes pour CETTE évaluation
$students = $pdo->prepare("
    SELECT s.id, s.nom, s.prenom, g.valeur, g.appreciation
    FROM students s
    JOIN student_enrollments se ON s.id = se.student_id
    LEFT JOIN grades g ON s.id = g.student_id AND g.evaluation_id = ?
    WHERE se.class_id = ? AND se.school_year_id = ?
    ORDER BY s.nom, s.prenom
");
$students->execute([$evaluation_id, $evaluation['class_id'], $evaluation['school_year_id']]);
$list = $students->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisie Notes - <?php echo htmlspecialchars($evaluation['nom']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .grade-input { width: 80px !important; text-align: center; font-weight: bold; }
        .comment-input { width: 100%; min-width: 150px; }
    </style>
</head>
<body>

<header class="app-header" style="background: white; padding: 1.5rem 2rem; border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100;">
        <div style="flex: 1;">
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">
                <?php echo htmlspecialchars($evaluation['class_name'] . " — " . $evaluation['subject_name']); ?>
            </div>
            <h1 style="margin: 0; font-size: 1.5rem; color: var(--text-main);">
                <?php echo htmlspecialchars($evaluation['nom']); ?> 
                <span style="font-size: 1rem; font-weight: normal; color: var(--text-muted); margin-left: 0.5rem;">
                    (<?php echo date('d/m/Y', strtotime($evaluation['date'])); ?>)
                </span>
            </h1>
        </div>
        <a href="evaluations_list.php?assignment_id=<?php echo $evaluation['assignment_id']; ?>" class="btn btn-secondary">Annuler / Retour</a>
</header>

<div class="container">
    
    <div class="card mt-4">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
            <h3>Saisie des Notes</h3>
            <?php if ($message): ?>
                <span style="color: green; font-weight: bold; padding: 0.5rem 1rem; background: #DCFCE7; border-radius: 4px;"><?php echo $message; ?></span>
            <?php endif; ?>
        </div>

        <form method="POST">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Élève</th>
                            <th style="width: 15%; text-align: center;">Note / 20</th>
                            <th>Appréciation / Commentaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $s): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($s['nom']); ?></div>
                                    <div style="font-size: 0.9rem; color: var(--text-muted);"><?php echo htmlspecialchars($s['prenom']); ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <input type="number" step="0.25" min="0" max="20" 
                                           name="notes[<?php echo $s['id']; ?>]" 
                                           value="<?php echo $s['valeur']; ?>" 
                                           class="grade-input" placeholder="-">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="appreciations[<?php echo $s['id']; ?>]" 
                                           value="<?php echo htmlspecialchars($s['appreciation'] ?? ''); ?>" 
                                           class="comment-input" placeholder="Feedback optionnel...">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 2rem; text-align: right; position: sticky; bottom: 1rem; background: white; padding: 1rem; border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);">
                <button type="submit" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem;">Enregistrer tout</button>
            </div>
        </form>
    </div>

</div>

</body>
</html>
