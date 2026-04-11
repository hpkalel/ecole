<?php
// views/view_students.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$class_id = $_GET['class_id'] ?? null;
if (!$class_id) { 
    header("Location: manage_classes.php"); 
    exit; 
}

// Info Classe
$stmtClass = $pdo->prepare("SELECT nom FROM classes WHERE id = ?");
$stmtClass->execute([$class_id]);
$classe = $stmtClass->fetch();

// Active Year (Fallback to most recent if none active)
$stmtYear = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$active_year_id = $stmtYear->fetchColumn();
if (!$active_year_id) {
    $stmtYear = $pdo->query("SELECT id FROM school_years ORDER BY id DESC LIMIT 1");
    $active_year_id = $stmtYear->fetchColumn();
}

// Liste Élèves
$stmtStudents = $pdo->prepare("
    SELECT s.*, se.statut
    FROM students s
    JOIN student_enrollments se ON s.id = se.student_id
    WHERE se.class_id = ? AND se.school_year_id = ?
    ORDER BY s.nom, s.prenom
");
$stmtStudents->execute([$class_id, $active_year_id]);
$students = $stmtStudents->fetchAll();

// Logique Ajout Manuel
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $nom = strtoupper(trim($_POST['nom']));
    $prenom = trim($_POST['prenom']);
    $sexe = $_POST['sexe'];
    $statut = $_POST['statut'];
    $matricule = trim($_POST['matricule']);

    if ($nom && $prenom) {
        try {
            $pdo->beginTransaction();

            // 1. Insérer l'élève
            $stmt = $pdo->prepare("INSERT INTO students (nom, prenom, sexe, matricule) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $sexe, $matricule]);
            $new_student_id = $pdo->lastInsertId();

            // 2. L'inscrire dans la classe
            $stmtEnrol = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, school_year_id, statut) VALUES (?, ?, ?, ?)");
            $stmtEnrol->execute([$new_student_id, $class_id, $active_year_id, $statut]);

            $pdo->commit();
            header("Location: view_students.php?class_id=$class_id&success=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Erreur : " . $e->getMessage();
        }
    } else {
        $message = "Veuillez remplir au moins le nom et le prénom.";
    }
}

// Logique Suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    $del_id = $_POST['delete_student_id'];
    try {
        $pdo->beginTransaction();
        // Optionnel : On peut garder l'élève en base et juste supprimer l'enrollment 
        // ou tout supprimer. Généralement on supprime l'enrollment.
        // Ici on va supprimer l'élève s'il n'a qu'un enrollment, ou juste l'enrollment.
        // Par simplicité et sécurité, on supprime l'inscription (enrollment) 
        // pour ne pas casser les archives des années précédentes si l'élève y était.
        $stmtDel = $pdo->prepare("DELETE FROM student_enrollments WHERE student_id = ? AND class_id = ? AND school_year_id = ?");
        $stmtDel->execute([$del_id, $class_id, $active_year_id]);
        
        $pdo->commit();
        header("Location: view_students.php?class_id=$class_id&msg=deleted");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Élèves - <?php echo htmlspecialchars($classe['nom']); ?> - Ecole2</title>
    <link rel="stylesheet" href="../assets/style.css?v=63">
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
            <a href="manage_classes.php" class="nav-link active">🏫 Classes & Élèves</a>
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
        <header class="app-header" style="flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 200px;">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: block; line-height: 1;">Liste des élèves</span>
                    <h1 style="margin: 0; font-size: 1.25rem; color: var(--text-main); line-height: 1.2;">Classe : <?php echo htmlspecialchars($classe['nom']); ?></h1>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: nowrap;">
                <button onclick="document.getElementById('add-student-form').style.display = 'block'" class="btn" style="padding: 0.5rem 0.8rem; font-size: 0.85rem;">
                    <span>➕</span> <span class="hide-mobile">Ajouter un élève</span><span class="show-mobile">Ajouter</span>
                </button>
                <a href="manage_classes.php" class="btn btn-secondary" style="padding: 0.5rem 0.8rem; font-size: 0.85rem;">
                    <span class="hide-mobile">Retour aux classes</span><span class="show-mobile">Retour</span>
                </a>
            </div>
        </header>

        <style>
            .show-mobile { display: none; }
            @media (max-width: 600px) {
                .hide-mobile { display: none; }
                .show-mobile { display: inline; }
                .app-header { padding: 0.8rem !important; }
            }
        </style>

        <?php if ($message): ?>
            <div class="alert alert-error mt-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success mt-4">L'élève a été ajouté avec succès !</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success mt-4">L'inscription de l'élève a été supprimée.</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success mt-4">Les informations de l'élève ont été mises à jour.</div>
        <?php endif; ?>

        <!-- Formulaire d'ajout manuel (Caché par défaut) -->
        <div id="add-student-form" class="card mt-4" style="display: none; border-top: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 class="card-title" style="margin: 0; border: none; padding: 0;">Nouvel Élève</h3>
                <button onclick="document.getElementById('add-student-form').style.display = 'none'" class="btn btn-secondary" style="padding: 0.2rem 0.5rem;">✕ Fermer</button>
            </div>
            <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Nom</label>
                    <input type="text" name="nom" required placeholder="ex: KODJO">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Prénom</label>
                    <input type="text" name="prenom" required placeholder="ex: Ange">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Sexe</label>
                    <select name="sexe">
                        <option value="M">Masculin (M)</option>
                        <option value="F">Féminin (F)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Statut</label>
                    <select name="statut">
                        <option value="Nouveau">Nouveau</option>
                        <option value="Redoublant">Redoublant</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Matricule (Optionnel)</label>
                    <input type="text" name="matricule" placeholder="ex: 123456">
                </div>
                <div>
                    <button type="submit" name="add_student" class="btn w-full">Enregistrer</button>
                </div>
            </form>
        </div>

        <div class="card mt-4">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 class="card-title" style="margin-bottom: 0; border: none; padding: 0;">Effectif (<?php echo count($students); ?> élèves)</h3>
            </div>
            
            <?php if (count($students) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Sexe</th>
                                <th>Statut</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td style="font-family: monospace; font-weight: 600; color: var(--text-muted);"><?php echo htmlspecialchars($s['matricule'] ?: '-'); ?></td>
                                    <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($s['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($s['prenom']); ?></td>
                                    <td><?php echo $s['sexe']; ?></td>
                                    <td><span class="badge" style="background: <?php echo $s['statut'] == 'Redoublant' ? '#fef2f2; color: #dc2626;' : '#f0fdf4; color: #16a34a;'; ?>"><?php echo $s['statut']; ?></span></td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                            <a href="bulletin.php?student_id=<?php echo $s['id']; ?>" class="btn btn-secondary" style="font-size: 1rem; padding: 0.4rem 0.6rem; height: 38px; width: 38px; background: #fff; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);" title="Bulletin">
                                                📄
                                            </a>
                                            <a href="edit_student.php?student_id=<?php echo $s['id']; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-secondary" style="font-size: 1rem; padding: 0.4rem 0.6rem; height: 38px; width: 38px; color: var(--info); background: #fff; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);" title="Modifier">
                                                ✏️
                                            </a>
                                            <form method="POST" id="deleteForm-<?php echo $s['id']; ?>" style="display:inline;">
                                                <input type="hidden" name="delete_student_id" value="<?php echo $s['id']; ?>">
                                                <button type="button" onclick="showDeleteModal(<?php echo $s['id']; ?>)" class="btn btn-secondary" style="font-size: 1rem; padding: 0.4rem 0.6rem; height: 38px; width: 38px; color: var(--danger); background: #fff; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);" title="Retirer de la classe">
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
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚸</div>
                    <p>Aucun élève n'est encore inscrit dans cette classe.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Premium Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-icon">⚠️</div>
        <div class="modal-title">Retrait Élève</div>
        <div class="modal-text">
            Voulez-vous vraiment retirer cet élève de la classe ? <br>
            <strong>L'élève restera en base mais ne sera plus inscrit ici.</strong>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="hideDeleteModal()">Annuler</button>
            <button class="modal-btn modal-btn-delete" id="confirmDeleteBtn">Retirer</button>
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

function showDeleteModal(id) {
    formToSubmit = document.getElementById('deleteForm-' + id);
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
