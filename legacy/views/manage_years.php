<?php
// views/manage_years.php
session_start();
require_once '../config/database.php';

// Vérification accès Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// 1. Ajouter une nouvelle année
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_year'])) {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($name && $start_date && $end_date) {
        try {
            $stmt = $pdo->prepare("INSERT INTO school_years (name, start_date, end_date, is_active) VALUES (?, ?, ?, 0)");
            $stmt->execute([$name, $start_date, $end_date]);
            $_SESSION['message'] = "Année '$name' créée avec succès.";
            header("Location: manage_years.php");
            exit;
        } catch (Exception $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}

// 2. Activer une année
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_year_id'])) {
    $year_id = $_POST['activate_year_id'];
    
    $pdo->beginTransaction();
    try {
        // Désactiver toutes les années
        $pdo->exec("UPDATE school_years SET is_active = 0");
        
        // Activer l'année sélectionnée
        $stmt = $pdo->prepare("UPDATE school_years SET is_active = 1 WHERE id = ?");
        $stmt->execute([$year_id]);
        
        $pdo->commit();
        $_SESSION['message'] = "L'année sélectionnée est maintenant active.";
        header("Location: manage_years.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Erreur lors de l'activation : " . $e->getMessage();
    }
}

// Récupération de la liste
$years = $pdo->query("SELECT * FROM school_years ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les Années Scolaires</title>
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
            <a href="manage_profs.php" class="nav-link">👤 Professeurs</a>
            <a href="manage_years.php" class="nav-link active">📅 Années Scolaires</a>
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
                <h1 style="margin: 0; font-size: 1.5rem;">Années Scolaires</h1>
            </div>
            <a href="admin_dashboard.php" class="btn btn-secondary">Retour</a>
        </header>
    
    <div class="dashboard-grid grid-cols-1-2" style="gap: 2rem; margin-top: 2rem;">
        
        <!-- Création -->
        <div class="card">
            <h3 class="card-title">Nouvelle Année Scolaire</h3>
            <?php 
            $message = $message ?: ($_SESSION['message'] ?? '');
            unset($_SESSION['message']);
            if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Nom (ex: 2025-2026)</label>
                    <input type="text" name="name" required placeholder="YYYY-YYYY">
                </div>
                <div class="form-group">
                    <label>Date de début</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>Date de fin</label>
                    <input type="date" name="end_date" required>
                </div>
                <button type="submit" name="add_year" class="btn w-full">Créer l'année</button>
            </form>
        </div>

        <!-- Liste -->
        <div class="card">
            <h3 class="card-title">Historique des Années</h3>
            <?php if (count($years) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($years as $y): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo htmlspecialchars($y['name']); ?></td>
                                    <td style="font-size: 0.9rem; color: var(--text-muted);">
                                        <?php echo date('d/m/Y', strtotime($y['start_date'])) . ' au ' . date('d/m/Y', strtotime($y['end_date'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($y['is_active']): ?>
                                            <span class="badge badge-success">ACTIVE</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: var(--bg-color); color: var(--text-muted);">Archivée</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!$y['is_active']): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="activate_year_id" value="<?php echo $y['id']; ?>">
                                                <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">Activer</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">Aucune année scolaire définie.</p>
            <?php endif; ?>
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
