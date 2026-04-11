<?php
// views/edit_evaluation.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prof') {
    header("Location: login.php");
    exit;
}

$evaluation_id = $_GET['evaluation_id'] ?? null;
if (!$evaluation_id) die("ID manquant.");

// Récupérer l'évaluation et vérifier qu'elle appartient au prof connecté
$stmt = $pdo->prepare("
    SELECT e.*, a.class_id, a.subject_id, a.prof_id, c.nom as class_name, s.nom as subject_name
    FROM evaluations e
    JOIN assignments a ON e.assignment_id = a.id
    JOIN classes c ON a.class_id = c.id
    JOIN subjects s ON a.subject_id = s.id
    WHERE e.id = ? AND a.prof_id = ?
");
$stmt->execute([$evaluation_id, $_SESSION['user_id']]);
$evaluation = $stmt->fetch();

if (!$evaluation) die("Accès refusé ou évaluation introuvable.");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_eval'])) {
    $nom = trim($_POST['nom']);
    $type = $_POST['type'];
    $periode = $_POST['periode'];
    $date = $_POST['date'];

    if (!empty($nom) && !empty($date)) {
        try {
            $update = $pdo->prepare("UPDATE evaluations SET nom = ?, type = ?, periode = ?, date = ? WHERE id = ?");
            $update->execute([$nom, $type, $periode, $date, $evaluation_id]);
            header("Location: evaluations_list.php?assignment_id=" . $evaluation['assignment_id'] . "&msg=updated");
            exit;
        } catch (Exception $e) {
            $msg = "Erreur : " . $e->getMessage();
        }
    } else {
        $msg = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'évaluation - <?php echo htmlspecialchars($evaluation['nom']); ?></title>
    <link rel="stylesheet" href="../assets/style.css?v=2">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar (Minimal for edit) -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Ecole 2</h2>
            <button class="sidebar-close" onclick="toggleSidebar()">✕</button>
        </div>
        <div class="sidebar-nav">
            <a href="prof_dashboard.php" class="nav-link">🏠 Tableau de bord</a>
            <a href="evaluations_list.php?assignment_id=<?php echo $evaluation['assignment_id']; ?>" class="nav-link">📝 Retour à la liste</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="app-header">
            <div>
                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">Matière : <?php echo htmlspecialchars($evaluation['subject_name']); ?> | Classe : <?php echo htmlspecialchars($evaluation['class_name']); ?></span>
                <h1 style="margin: 0; font-size: 1.5rem; color: var(--text-main);">Modifier l'évaluation</h1>
            </div>
            <a href="evaluations_list.php?assignment_id=<?php echo $evaluation['assignment_id']; ?>" class="btn btn-secondary">Annuler</a>
        </header>

        <div style="max-width: 600px; margin-top: 2rem;">
            <div class="card">
                <?php if ($msg): ?>
                    <div class="alert alert-error"><?php echo $msg; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Nom de l'évaluation</label>
                        <input type="text" name="nom" required value="<?php echo htmlspecialchars($evaluation['nom']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="interrogation" <?php echo $evaluation['type'] === 'interrogation' ? 'selected' : ''; ?>>Interrogation</option>
                            <option value="devoir" <?php echo $evaluation['type'] === 'devoir' ? 'selected' : ''; ?>>Devoir Synthèse</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Période</label>
                        <select name="periode" required>
                            <option value="Semestre 1" <?php echo $evaluation['periode'] === 'Semestre 1' ? 'selected' : ''; ?>>Semestre 1</option>
                            <option value="Semestre 2" <?php echo $evaluation['periode'] === 'Semestre 2' ? 'selected' : ''; ?>>Semestre 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date de l'épreuve</label>
                        <input type="date" name="date" required value="<?php echo $evaluation['date']; ?>">
                    </div>
                    
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" name="update_eval" class="btn" style="flex: 1;">Enregistrer les modifications</button>
                        <a href="evaluations_list.php?assignment_id=<?php echo $evaluation['assignment_id']; ?>" class="btn btn-secondary" style="flex: 1;">Retour</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>

</body>
</html>
