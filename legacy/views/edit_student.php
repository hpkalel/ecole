<?php
// views/edit_student.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$student_id = $_GET['student_id'] ?? null;
$class_id = $_GET['class_id'] ?? null; // Pour le retour

if (!$student_id) {
    header("Location: manage_classes.php");
    exit;
}

// 1. Récupérer les infos de l'élève
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) die("Élève introuvable.");

// 2. Récupérer son enrollment actuel (pour la classe spécifiée ou la dernière)
$stmtYear = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$active_year_id = $stmtYear->fetchColumn();

$stmtEnrol = $pdo->prepare("SELECT * FROM student_enrollments WHERE student_id = ? AND school_year_id = ?");
$stmtEnrol->execute([$student_id, $active_year_id]);
$enrollment = $stmtEnrol->fetch();

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = strtoupper(trim($_POST['nom']));
    $prenom = trim($_POST['prenom']);
    $sexe = $_POST['sexe'];
    $matricule = trim($_POST['matricule']);
    $statut = $_POST['statut'];

    if ($nom && $prenom) {
        try {
            $pdo->beginTransaction();

            // Update student
            $stmtUpd = $pdo->prepare("UPDATE students SET nom = ?, prenom = ?, sexe = ?, matricule = ? WHERE id = ?");
            $stmtUpd->execute([$nom, $prenom, $sexe, $matricule, $student_id]);

            // Update enrollment status if exists
            if ($enrollment) {
                $stmtEnrolUpd = $pdo->prepare("UPDATE student_enrollments SET statut = ? WHERE student_id = ? AND school_year_id = ?");
                $stmtEnrolUpd->execute([$statut, $student_id, $active_year_id]);
            }

            $pdo->commit();
            $redirect = "view_students.php?class_id=" . ($class_id ?: $enrollment['class_id']);
            header("Location: $redirect&msg=updated");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Erreur : " . $e->getMessage();
        }
    } else {
        $message = "Le nom et le prénom sont obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Élève - Ecole2</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Ecole 2</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; margin-bottom: 0;">Espace Directeur</p>
        </div>
        <div class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">🏠 Vue d'ensemble</a>
            <a href="manage_classes.php" class="nav-link active">🏫 Classes & Élèves</a>
            <a href="manage_subjects.php" class="nav-link">📚 Matières</a>
            <a href="manage_profs.php" class="nav-link">👤 Professeurs</a>
            <a href="manage_years.php" class="nav-link">📅 Années Scolaires</a>
        </div>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-secondary w-full">Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="app-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0;">Modifier l'élève</h1>
            </div>
            <a href="view_students.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary">Annuler</a>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-error mt-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card mt-4">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?php echo htmlspecialchars($student['nom']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($student['prenom']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Sexe</label>
                    <select name="sexe">
                        <option value="M" <?php if($student['sexe'] == 'M') echo 'selected'; ?>>Masculin (M)</option>
                        <option value="F" <?php if($student['sexe'] == 'F') echo 'selected'; ?>>Féminin (F)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Matricule</label>
                    <input type="text" name="matricule" value="<?php echo htmlspecialchars($student['matricule']); ?>">
                </div>
                <div class="form-group">
                    <label>Statut Inscription</label>
                    <select name="statut">
                        <option value="Nouveau" <?php if($enrollment && $enrollment['statut'] == 'Nouveau') echo 'selected'; ?>>Nouveau</option>
                        <option value="Redoublant" <?php if($enrollment && $enrollment['statut'] == 'Redoublant') echo 'selected'; ?>>Redoublant</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>

</body>
</html>

</body>
</html>
