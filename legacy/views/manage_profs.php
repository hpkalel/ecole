<?php
// views/manage_profs.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Toggle Status (Passage en POST pour la sécurité)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $id = $_POST['toggle_id'];
    // Empêcher de se désactiver soi-même si jamais on est aussi listé (normalement non car role=prof)
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = 'prof'");
        $stmt->execute([$id]);
        $_SESSION['message'] = "Statut mis à jour avec succès.";
        header("Location: manage_profs.php");
        exit;
    }
}

// Récupération des professeurs
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'prof' ORDER BY nom ASC");
$profs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Professeurs</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Ecole 2</h2>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; margin-bottom: 0;">Espace Directeur</p>
            <button class="sidebar-close" onclick="toggleSidebar()">✕</button>
        </div>
        
        <div class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">🏠 Vue d'ensemble</a>
            <a href="manage_classes.php" class="nav-link">🏫 Classes & Élèves</a>
            <a href="manage_subjects.php" class="nav-link">📚 Matières</a>
            <a href="manage_profs.php" class="nav-link active">👤 Professeurs</a>
            <a href="manage_years.php" class="nav-link">📅 Années Scolaires</a>
            <a href="promote_students.php" class="nav-link">🎓 Promotion</a>
        </div>

        <div class="sidebar-footer">
            <div style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong>
            </div>
            <a href="logout.php" class="btn btn-secondary w-full" style="padding: 0.4rem;">Déconnexion</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="app-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; font-size: 1.5rem;">Gestion des Professeurs</h1>
            </div>
            <a href="admin_dashboard.php" class="btn btn-secondary">Retour</a>
        </header>
    <div class="card mt-4">
        <h3>Liste des Professeurs</h3>
        
        <?php 
        $message = $message ?: ($_SESSION['message'] ?? '');
        unset($_SESSION['message']);
        if ($message): ?>
            <div style="background: #D1FAE5; color: #065F46; padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 1rem;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive" style="margin-top: 1rem;">
            <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid #eee;">
                    <th style="padding: 0.75rem;">Nom</th>
                    <th style="padding: 0.75rem;">Grade</th>
                    <th style="padding: 0.75rem;">Statut / Corps</th>
                    <th style="padding: 0.75rem;">Identifiant</th>
                    <th style="padding: 0.75rem;">État</th>
                    <th style="padding: 0.75rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profs as $p): ?>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.75rem; font-weight: bold;"><?php echo htmlspecialchars($p['nom']); ?></td>
                        <td style="padding: 0.75rem;"><?php echo htmlspecialchars($p['grade'] ?? '-'); ?></td>
                        <td style="padding: 0.75rem;">
                            <div style="font-size: 0.9rem;"><?php echo htmlspecialchars($p['statut'] ?? '-'); ?></div>
                            <div style="font-size: 0.8rem; color: gray;"><?php echo htmlspecialchars($p['corps'] ?? '-'); ?></div>
                        </td>
                        <td style="padding: 0.75rem; color: gray;"><?php echo htmlspecialchars($p['username']); ?></td>
                        <td style="padding: 0.75rem;">
                            <?php if ($p['is_active']): ?>
                                <span style="background: #DCFCE7; color: #166534; padding: 4px 10px; border-radius: 99px; font-size: 0.85rem;">Actif</span>
                            <?php else: ?>
                                <span style="background: #FEE2E2; color: #991B1B; padding: 4px 10px; border-radius: 99px; font-size: 0.85rem;">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 0.75rem; text-align: right;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="toggle_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn <?php echo $p['is_active'] ? 'btn-secondary' : ''; ?>" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                    <?php echo $p['is_active'] ? 'Désactiver' : 'Activer'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        
        <?php if (count($profs) === 0): ?>
            <p style="color: gray; font-style: italic; margin-top: 1rem;">Aucun professeur inscrit.</p>
        <?php endif; ?>
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
