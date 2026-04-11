<?php
// views/prof_dashboard.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prof') {
    header("Location: login.php");
    exit;
}

// Vérifier si le compte est toujours actif
$checkStatus = $pdo->prepare("SELECT is_active, nom, grade, statut, corps FROM users WHERE id = ?");
$checkStatus->execute([$_SESSION['user_id']]);
$userData = $checkStatus->fetch();

if (!$userData || !$userData['is_active']) {
    session_destroy();
    header("Location: login.php?error=inactive");
    exit;
}

// Récupération de l'année scolaire active
$stmtYear = $pdo->query("SELECT id, name FROM school_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmtYear->fetch();
$current_year_id = $active_year ? $active_year['id'] : null;
$current_year_name = $active_year ? $active_year['name'] : 'N/A';

$msg_year = "";
if (isset($_GET['msg']) && $_GET['msg'] === 'year_changed') {
    $msg_year = "L'année scolaire a été changée par l'administration. Vous avez été redirigé vers l'année en cours : " . $current_year_name;
}

// Récupération des attributions du prof connecté pour l'année active
$prof_id = $_SESSION['user_id'];
$assignments = $pdo->prepare("
    SELECT a.id, c.nom as class_name, s.nom as subject_name,
           (SELECT COUNT(*) FROM student_enrollments se WHERE se.class_id = c.id AND se.school_year_id = ?) as student_count
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN subjects s ON a.subject_id = s.id
    WHERE a.prof_id = ? AND a.school_year_id = ?
    ORDER BY c.nom, s.nom
");
$assignments->execute([$current_year_id, $prof_id, $current_year_id]);
$my_assignments = $assignments->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Professeur - Ecole2</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Ecole 2</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; margin-bottom: 0;">Espace Professeur</p>
            <button class="sidebar-close" onclick="toggleSidebar()">✕</button>
        </div>
        
        <div class="sidebar-nav">
            <a href="prof_dashboard.php" class="nav-link active">🏠 Tableau de bord</a>
        </div>

        <div class="sidebar-footer">
            <div style="font-size: 0.9rem; margin-bottom: 0.25rem;">
                <strong><?php echo htmlspecialchars($userData['nom']); ?></strong>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.2;">
                <?php echo htmlspecialchars($userData['grade'] ?? ''); ?><br>
                <?php echo htmlspecialchars($userData['statut'] ?? ''); ?> (<?php echo htmlspecialchars($userData['corps'] ?? ''); ?>)
            </div>
            <a href="logout.php" class="btn btn-secondary w-full mt-3" style="padding: 0.4rem;">Déconnexion</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="app-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; font-size: 1.5rem;">Mes Classes & Matières</h1>
            </div>
            <div style="font-weight: 600; color: var(--text-muted); padding: 0.4rem 0.8rem; background: var(--bg-color); border-radius: var(--radius-md);">
                Année <?php echo htmlspecialchars($current_year_name); ?>
            </div>
        </header>

        <?php if ($msg_year): ?>
            <div class="alert alert-info" style="background: #E0F2FE; color: #0369A1; border-color: #BAE6FD;">
                ℹ️ <?php echo htmlspecialchars($msg_year); ?>
            </div>
        <?php endif; ?>

        <p class="text-muted mb-4">Sélectionnez une classe pour gérer les évaluations et saisir les notes.</p>
        
        <?php if (count($my_assignments) > 0): ?>
            <div class="dashboard-grid">
                <?php foreach ($my_assignments as $assign): ?>
                    <a href="evaluations_list.php?assignment_id=<?php echo $assign['id']; ?>" class="card" style="text-decoration: none; border-left: 4px solid var(--primary); display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
                        <div>
                            <h3 style="color: var(--primary); margin-bottom: 0.25rem;"><?php echo htmlspecialchars($assign['class_name']); ?></h3>
                            <p style="font-size: 1.25rem; font-weight: 600; color: var(--text-main); margin-bottom: 1rem;"><?php echo htmlspecialchars($assign['subject_name']); ?></p>
                            <span class="badge" style="background: var(--bg-color); color: var(--text-muted);">👥 <?php echo $assign['student_count']; ?> élèves</span>
                        </div>
                        <div style="margin-top: 1.5rem; text-align: right; color: var(--primary); font-weight: 500; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
                            Saisir notes <span>&rarr;</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card text-center text-muted" style="padding: 4rem 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                <p>Aucune classe ne vous a été attribuée pour l'année scolaire en cours.</p>
            </div>
        <?php endif; ?>
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
