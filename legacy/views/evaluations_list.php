<?php
// views/evaluations_list.php
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

$assignment_id = $_GET['assignment_id'] ?? null;
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

// Vérifier si l'année de l'attribution est l'année active
$stmtActiveYear = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$activeYearId = $stmtActiveYear->fetchColumn();

if ($assignment['school_year_id'] != $activeYearId) {
    header("Location: prof_dashboard.php?msg=year_changed");
    exit;
}

$msg = "";

// Ajout d'une évaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_eval'])) {
    $nom = trim($_POST['nom']);
    $type = $_POST['type'];
    $date = $_POST['date'];

    if (!empty($nom) && !empty($date)) {
        try {
            $periode = $_POST['periode'] ?? 'Semestre 1';
            $stmt = $pdo->prepare("INSERT INTO evaluations (assignment_id, type, nom, date, periode) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$assignment_id, $type, $nom, $date, $periode]);
            $msg = "Évaluation '$nom' ajoutée avec succès.";
        } catch (Exception $e) {
            $msg = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupérer les évaluations existantes par ordre personnalisé (Semestre 2 d'abord, Devoir avant Interro, puis décroissant)
$evals = $pdo->prepare("SELECT * FROM evaluations WHERE assignment_id = ? ORDER BY periode DESC, type DESC, date DESC, nom DESC");
$evals->execute([$assignment_id]);
$evaluations = $evals->fetchAll();

// Suppression d'une évaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_eval'])) {
    $eval_id = $_POST['evaluation_id'];
    
    // Vérifier que l'évaluation appartient bien à cet assignment (sécurité supplémentaire)
    $checkEval = $pdo->prepare("SELECT id FROM evaluations WHERE id = ? AND assignment_id = ?");
    $checkEval->execute([$eval_id, $assignment_id]);
    
    if ($checkEval->fetch()) {
        try {
            $stmt = $pdo->prepare("DELETE FROM evaluations WHERE id = ?");
            $stmt->execute([$eval_id]);
            header("Location: evaluations_list.php?assignment_id=$assignment_id&msg=deleted");
            exit;
        } catch (Exception $e) {
            $msg = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $msg = "Erreur : Évaluation introuvable ou accès refusé.";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $msg = "Évaluation supprimée avec succès.";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $msg = "Évaluation mise à jour avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluations - <?php echo htmlspecialchars($assignment['class_name']); ?> - Ecole2</title>
    <link rel="stylesheet" href="../assets/style.css?v=61">
    <style>
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 1rem !important;
                width: 100% !important;
                max-width: 100vw !important;
                min-width: 0 !important;
            }
            .app-layout {
                flex-direction: column !important;
            }
            .card {
                padding: 1rem !important;
                overflow: visible !important; 
            }
            .table-responsive {
                width: 100% !important;
                display: block !important;
                overflow-x: auto !important;
                border: 1px solid var(--border-color) !important;
                margin-top: 1rem !important;
            }
            .dashboard-grid {
                display: block !important;
            }
            .add-eval-card {
                margin-bottom: 2rem !important;
                position: static !important;
            }
        }
    </style>
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
            <a href="prof_dashboard.php" class="nav-link">🏠 Tableau de bord</a>
            <div style="padding: 1rem 1.5rem; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; margin-top: 1rem;">
                Action actuelle
            </div>
            <a href="#" class="nav-link active">📝 Évaluations</a>
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
            <div>
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; line-height: 1.1;">Matière : <?php echo htmlspecialchars($assignment['subject_name']); ?></span>
                    <h1>Classe : <?php echo htmlspecialchars($assignment['class_name'] . " - Évaluations"); ?></h1>
                </div>
            </div>
            <div>
                <a href="prof_dashboard.php" class="btn btn-secondary">
                    <span class="hide-mobile">Retour aux classes</span><span class="show-mobile">Retour</span>
                </a>
            </div>
        </header>

        <style>
            .show-mobile { display: none; }
            @media (max-width: 600px) {
                .hide-mobile { display: none; }
                .show-mobile { display: inline; }
            }
        </style>

        <div class="dashboard-grid mt-4">
            
            <!-- Créer une évaluation -->
            <div>
                <div class="card add-eval-card">
                    <h3 class="card-title">Nouvelle Évaluation</h3>
                    
                    <?php if ($msg): ?>
                        <div class="alert <?php echo strpos($msg, 'Erreur') !== false ? 'alert-error' : 'alert-success'; ?>">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Nom de l'évaluation</label>
                            <input type="text" name="nom" required placeholder="ex: Interro Chap 1">
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" required>
                                <option value="interrogation">Interrogation</option>
                                <option value="devoir">Devoir Synthèse</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Période</label>
                            <select name="periode" required>
                                <option value="Semestre 1" <?php echo (date('n') < 2 || date('n') >= 9) ? 'selected' : ''; ?>>Semestre 1</option>
                                <option value="Semestre 2" <?php echo (date('n') >= 2 && date('n') < 9) ? 'selected' : ''; ?>>Semestre 2</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date de l'épreuve</label>
                            <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button type="submit" name="add_eval" class="btn w-full mt-2">Valider la création</button>
                    </form>
                </div>
            </div>

            <!-- Liste des évaluations -->
            <div>
                <div class="card">
                    <h3 class="card-title">Historique des évaluations</h3>
                    
                    <?php if (count($evaluations) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th style="min-width: 110px;">Période</th>
                                        <th>Type & Nom</th>
                                        <th style="min-width: 140px; text-align: right;">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evaluations as $eval): ?>
                                        <tr>
                                            <td style="color: var(--text-muted); font-weight: 500;">
                                                <?php echo date('d/m/Y', strtotime($eval['date'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: var(--bg-color); color: var(--text-muted); border: 1px solid var(--border-color);">
                                                    <?php echo htmlspecialchars($eval['periode'] ?? 'Semestre 1'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: var(--text-main); margin-bottom: 0.25rem;">
                                                    <?php echo htmlspecialchars($eval['nom']); ?>
                                                </div>
                                                <span class="badge" style="background: <?php echo $eval['type'] == 'devoir' ? 'var(--info)' : 'var(--warning)'; ?>; color: #FFF; font-size: 0.7rem;">
                                                    <?php echo htmlspecialchars($eval['type']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <div class="eval-actions" style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                                    <a href="enter_grades.php?evaluation_id=<?php echo $eval['id']; ?>" class="btn btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.6rem; min-width: 80px; height: 38px;" title="Saisir les notes">
                                                        Notes &rarr;
                                                    </a>

                                                    <a href="edit_evaluation.php?evaluation_id=<?php echo $eval['id']; ?>" class="btn btn-secondary" style="font-size: 1rem; padding: 0.4rem 0.6rem; background: #fff; color: var(--primary); border: 1px solid rgba(79, 70, 229, 0.2); box-shadow: var(--shadow-sm); height: 38px; width: 38px;" title="Modifier">
                                                        ✏️
                                                    </a>
                                                    
                                                    <form method="POST" id="deleteForm-<?php echo $eval['id']; ?>" style="margin: 0;">
                                                        <input type="hidden" name="evaluation_id" value="<?php echo $eval['id']; ?>">
                                                        <input type="hidden" name="delete_eval" value="1">
                                                        <button type="button" onclick="showDeleteModal(<?php echo $eval['id']; ?>)" class="btn btn-danger" style="font-size: 1rem; padding: 0.4rem 0.6rem; background: #fff; color: #dc2626; border: 1px solid #fee2e2; box-shadow: var(--shadow-sm); height: 38px; width: 38px;" title="Supprimer">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted" style="padding: 3rem 1rem;">
                            <div style="font-size: 2.5rem; margin-bottom: 1rem;">📄</div>
                            <p>Aucune évaluation n'a encore été créée pour cette classe dans cette matière.</p>
                            <p style="font-size: 0.9rem;">Utilisez le formulaire ci-contre pour programmer la première évaluation.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Premium Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-icon">⚠️</div>
        <div class="modal-title">Suppression</div>
        <div class="modal-text">
            Êtes-vous sûr de vouloir supprimer cette évaluation ? <br>
            <strong>Toutes les notes seront définitivement perdues.</strong>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="hideDeleteModal()">Annuler</button>
            <button class="modal-btn modal-btn-delete" id="confirmDeleteBtn">Supprimer</button>
        </div>
    </div>
</div>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
let formToSubmit = null;

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

function showDeleteModal(evalId) {
    formToSubmit = document.getElementById('deleteForm-' + evalId);
    const modal = document.getElementById('deleteModal');
    modal.classList.add('show');
}

function hideDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('show');
    formToSubmit = null;
}

document.getElementById('confirmDeleteBtn').onclick = function() {
    if (formToSubmit) {
        formToSubmit.submit();
    }
};

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        hideDeleteModal();
    }
}
</script>

</body>
</html>
