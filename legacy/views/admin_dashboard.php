<?php
// views/admin_dashboard.php
session_start();
require_once '../config/database.php';

// Vérification accès Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Génération d'un code d'invitation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
    $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8)); // Code aléatoire de 8 caractères
    $stmt = $pdo->prepare("INSERT INTO invitations (code) VALUES (?)");
    if ($stmt->execute([$code])) {
        $_SESSION['message'] = "Nouveau code généré : <strong>$code</strong>";
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $message = "Erreur lors de la génération.";
    }
}

// Récupération de toutes les années scolaires
$stmt_years = $pdo->query("SELECT * FROM school_years ORDER BY name DESC");
$all_years = $stmt_years->fetchAll();

// Détermination de l'année sélectionnée (par défaut l'active)
$selected_year_id = $_GET['year_id'] ?? null;
if (!$selected_year_id) {
    $stmt_active = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
    $selected_year_id = $stmt_active->fetchColumn();
}

// Récupération des codes non utilisés
$stmt = $pdo->prepare("SELECT * FROM invitations WHERE is_used = 0 ORDER BY created_at DESC");
$stmt->execute();
$codes = $stmt->fetchAll();

// Récupération des professeurs inscrits
$stmt_profs = $pdo->query("SELECT * FROM users WHERE role = 'prof' ORDER BY created_at DESC");
$profs = $stmt_profs->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Directeur - Ecole2</title>
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
            <a href="admin_dashboard.php" class="nav-link active">🏠 Vue d'ensemble</a>
            <a href="manage_classes.php" class="nav-link">🏫 Classes & Élèves</a>
            <a href="manage_subjects.php" class="nav-link">📚 Matières</a>
            <a href="manage_profs.php" class="nav-link">👤 Professeurs</a>
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
        <!-- Topbar for Mobile Toggle & Year Selector -->
        <header class="app-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; font-size: 1.5rem;">Tableau de bord</h1>
            </div>
            
            <form method="GET" style="margin: 0;">
                <select name="year_id" onchange="this.form.submit()" style="padding: 0.4rem; font-weight: 500;">
                    <?php foreach ($all_years as $y): ?>
                        <option value="<?php echo $y['id']; ?>" <?php echo ($selected_year_id == $y['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($y['name']); ?> <?php echo $y['is_active'] ? '(Active)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </header>

        <!-- Quick Actions -->
        <h2 class="mt-4 mb-4">Raccourcis</h2>
        <div class="dashboard-grid mb-4" style="margin-top: 1rem;">
            <a href="manage_classes.php" class="card" style="text-decoration: none; color: inherit; text-align: center; margin-bottom: 0;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🏫</div>
                <h4 style="margin: 0;">Gérer Classes</h4>
            </a>
            <a href="manage_subjects.php" class="card" style="text-decoration: none; color: inherit; text-align: center; margin-bottom: 0;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📚</div>
                <h4 style="margin: 0;">Gérer Matières</h4>
            </a>
            <a href="manage_profs.php" class="card" style="text-decoration: none; color: inherit; text-align: center; margin-bottom: 0;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👤</div>
                <h4 style="margin: 0;">Gérer Employés</h4>
            </a>
            <a href="promote_students.php" class="card" style="text-decoration: none; color: inherit; text-align: center; margin-bottom: 0; border: 2px solid var(--info); background: var(--primary-light);">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🎓</div>
                <h4 style="margin: 0; color: var(--primary);">Promotions</h4>
            </a>
        </div>

        <div class="dashboard-grid">
            <!-- Codes & Invitations -->
            <div class="card">
                <h3 class="card-title">Codes d'Invitation</h3>
                <p>Générez des codes pour permettre aux professeurs de s'inscrire.</p>
                
                <?php 
                $message = $message ?: ($_SESSION['message'] ?? '');
                unset($_SESSION['message']);
                if ($message): ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <button type="submit" name="generate_code" class="btn w-full">Générer un code</button>
                </form>

                <h4 class="mt-4 mb-2">Codes disponibles :</h4>
                <?php if (count($codes) > 0): ?>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <?php foreach ($codes as $c): ?>
                            <div style="background: var(--bg-color); padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-family: monospace; font-weight: 700; font-size: 1.1rem; color: var(--primary);"><?php echo $c['code']; ?></span>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Le <?php echo date('d/m/Y', strtotime($c['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Aucun code en attente.</p>
                <?php endif; ?>
            </div>

            <!-- Liste Professeurs -->
            <div class="card">
                <h3 class="card-title">Professeurs Récents</h3>
                <div class="table-responsive">
                    <?php if (count($profs) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom Complet</th>
                                    <th>Grade</th>
                                    <th>Statut / Corps</th>
                                    <th>Identifiant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($profs, 0, 8) as $p): ?>
                                    <tr>
                                        <td style="font-weight: 500; font-size: 0.95rem;"><?php echo htmlspecialchars($p['nom']); ?></td>
                                        <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($p['grade'] ?? '-'); ?></td>
                                        <td style="font-size: 0.85rem;">
                                            <div style="font-weight: 500;"><?php echo htmlspecialchars($p['statut'] ?? '-'); ?></div>
                                            <div style="font-size: 0.75rem; color: gray;"><?php echo htmlspecialchars($p['corps'] ?? '-'); ?></div>
                                        </td>
                                        <td class="text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars($p['username']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted" style="padding: 1rem;">Aucun professeur inscrit.</p>
                    <?php endif; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="manage_profs.php" class="btn btn-secondary">Voir tout</a>
                </div>
            </div>
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
