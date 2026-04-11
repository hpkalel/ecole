<?php
// views/manage_subjects.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg_subject = "";
$msg_assign = "";

// Récupérer l'année active
$stmtYear = $pdo->query("SELECT id FROM school_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmtYear->fetch();
$current_year_id = $active_year ? $active_year['id'] : null;
if (!$current_year_id) die("Aucune année scolaire active.");

// 1. Ajout Matière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $nom = trim($_POST['nom_matiere']);
    if (!empty($nom)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (nom) VALUES (?)");
            $stmt->execute([$nom]);
            $_SESSION['msg_subject'] = "Matière '$nom' ajoutée.";
            header("Location: manage_subjects.php");
            exit;
        } catch (Exception $e) { $msg_subject = "Erreur (existe déjà ?) : " . $e->getMessage(); }
    }
}

// 2. Attribution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_prof'])) {
    $prof_id = $_POST['prof_id'];
    $subject_id = $_POST['subject_id'];
    $classes = $_POST['classes'] ?? []; // Array of class IDs
    $coefficient = $_POST['coefficient'] ?? 1;

    if ($prof_id && $subject_id && !empty($classes)) {
        $count = 0;
        foreach ($classes as $class_id) {
            try {
                $stmt = $pdo->prepare("INSERT INTO assignments (prof_id, subject_id, class_id, school_year_id, coefficient) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$prof_id, $subject_id, $class_id, $current_year_id, $coefficient]);
                $count++;
            } catch (Exception $e) {
                if ($e->getCode() == 23000) {
                    $msg_assign = "Erreur : La matière est déjà enseignée dans l'une des classes choisies par un autre professeur.";
                } else {
                    $msg_assign = "Erreur : " . $e->getMessage();
                }
            }
        }
        $_SESSION['msg_assign'] = "$count attributions réussies.";
        header("Location: manage_subjects.php");
        exit;
    } else {
        $msg_assign = "Veuillez sélectionner un prof, une matière et au moins une classe.";
    }
}

// 3. Suppression d'une attribution (Passage en POST pour la sécurité)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assign'])) {
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$_POST['delete_assign']]);
    $_SESSION['msg_assign'] = "Attribution supprimée.";
    header("Location: manage_subjects.php");
    exit;
}

// 4. Transfert d'attribution (Changement de prof)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assign'])) {
    $assign_id = $_POST['assign_id'];
    $new_prof_id = $_POST['new_prof_id'];
    
    if ($assign_id && $new_prof_id) {
        $stmt = $pdo->prepare("UPDATE assignments SET prof_id = ? WHERE id = ?");
        $stmt->execute([$new_prof_id, $assign_id]);
        $_SESSION['msg_assign'] = "Transfert effectué avec succès.";
        header("Location: manage_subjects.php");
        exit;
    }
}

// 5. Supprimer une Matière (Check if used first)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject_id'])) {
    $sid = $_POST['delete_subject_id'];
    
    // Check if used in assignments
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE subject_id = ?");
    $stmtCheck->execute([$sid]);
    if ($stmtCheck->fetchColumn() > 0) {
        $_SESSION['msg_subject'] = "Impossible de supprimer : Cette matière est déjà attribuée à des professeurs.";
        $_SESSION['msg_type'] = "error";
    } else {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$sid]);
        $_SESSION['msg_subject'] = "Matière supprimée avec succès.";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: manage_subjects.php");
    exit;
}

// 6. Modifier une Matière (Rename)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject_id'])) {
    $sid = $_POST['edit_subject_id'];
    $new_nom = trim($_POST['new_subject_name']);
    
    if (!empty($new_nom)) {
        try {
            $stmt = $pdo->prepare("UPDATE subjects SET nom = ? WHERE id = ?");
            $stmt->execute([$new_nom, $sid]);
            $_SESSION['msg_subject'] = "Matière renommée en '$new_nom'.";
            $_SESSION['msg_type'] = "success";
        } catch (Exception $e) {
            $_SESSION['msg_subject'] = "Erreur : Ce nom existe peut-être déjà.";
            $_SESSION['msg_type'] = "error";
        }
    }
    header("Location: manage_subjects.php");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] == 'supprime') $msg_assign = "Attribution supprimée.";

// Données pour les formulaires
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY nom")->fetchAll();
$profs = $pdo->query("SELECT * FROM users WHERE role = 'prof' AND is_active = 1 ORDER BY nom")->fetchAll();
$classes = $pdo->query("SELECT * FROM classes ORDER BY nom")->fetchAll();
$assignments = $pdo->prepare("
    SELECT a.id, u.nom as prof, s.nom as subject, c.nom as class, a.coefficient 
    FROM assignments a
    JOIN users u ON a.prof_id = u.id
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    WHERE a.school_year_id = ?
    ORDER BY u.nom, c.nom
");
$assignments->execute([$current_year_id]);
$assignments = $assignments->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Matières & Attributions - Ecole2</title>
    <link rel="stylesheet" href="../assets/style.css?v=71">
    <style>
        /* Force local responsiveness overrides */
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
                margin-bottom: 1rem !important;
            }
            .dashboard-grid {
                display: block !important; /* Force stacking of columns on mobile */
            }
            .dashboard-grid > div {
                margin-bottom: 1.5rem !important;
            }
            .app-header {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 1rem !important;
                padding: 1rem !important;
            }
            .app-header > div {
                width: 100% !important;
                justify-content: space-between !important;
            }
            .app-header .btn {
                width: 100% !important;
                text-align: center !important;
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
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; margin-bottom: 0;">Espace Directeur</p>
            <button class="sidebar-close" onclick="toggleSidebar()">✕</button>
        </div>
        
        <div class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link">🏠 Vue d'ensemble</a>
            <a href="manage_classes.php" class="nav-link">🏫 Classes & Élèves</a>
            <a href="manage_subjects.php" class="nav-link active">📚 Matières</a>
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
        <header class="app-header">
            <div>
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0;">Matières & Attributions</h1>
            </div>
            <a href="admin_dashboard.php" class="btn btn-secondary">Dashboard</a>
        </header>

        <div class="dashboard-grid grid-cols-1-2" style="margin-top: 1.5rem;">
            
            <!-- Colonne Gauche : Créer Matière -->
            <div>
                <div class="card">
                    <h3 class="card-title">Nouvelle Matière</h3>
                    
                    <?php 
                    $msg_subject = $msg_subject ?: ($_SESSION['msg_subject'] ?? '');
                    $msg_type = $_SESSION['msg_type'] ?? 'success';
                    unset($_SESSION['msg_subject'], $_SESSION['msg_type']);
                    if ($msg_subject): ?>
                        <div class="alert alert-<?php echo $msg_type === 'error' ? 'error' : 'success'; ?>">
                            <?php echo $msg_subject; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Nom de la matière</label>
                            <input type="text" name="nom_matiere" required placeholder="Ex: Mathématiques">
                        </div>
                        <button type="submit" name="add_subject" class="btn w-full">Créer la matière</button>
                    </form>

                    <h4 class="mt-4 mb-2">Matières existantes</h4>
                    <div style="background: var(--bg-color); border-radius: var(--radius-md); padding: 1rem; max-height: 250px; overflow-y: auto;">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($subjects as $s): ?>
                                <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center;">
                                        <span style="color: var(--primary); margin-right: 0.5rem;">📘</span>
                                        <?php echo htmlspecialchars($s['nom']); ?>
                                    </div>
                                    <div style="display: flex; gap: 0.25rem;">
                                        <button type="button" onclick="showEditSubjectModal(<?php echo $s['id']; ?>, '<?php echo addslashes($s['nom']); ?>')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px; width: 30px;" title="Modifier">✏️</button>
                                        <form method="POST" id="deleteSubjectForm-<?php echo $s['id']; ?>" style="margin: 0;">
                                            <input type="hidden" name="delete_subject_id" value="<?php echo $s['id']; ?>">
                                            <button type="button" onclick="showDeleteSubjectModal(<?php echo $s['id']; ?>, '<?php echo addslashes($s['nom']); ?>')" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.4rem; height: 30px; width: 30px; color: var(--danger);" title="Supprimer">🗑️</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Colonne Droite : Attributions -->
            <div>
                <div class="card">
                    <h3 class="card-title">Attribuer une matière à un Professeur</h3>
                    <p class="text-muted mb-4">Le professeur enseignera cette matière dans les classes sélectionnées.</p>
                    
                    <?php 
                    $msg_assign = $msg_assign ?: ($_SESSION['msg_assign'] ?? (($_GET['msg'] ?? '') == 'supprime' ? 'Attribution supprimée.' : ''));
                    unset($_SESSION['msg_assign']);
                    if ($msg_assign): ?>
                        <div class="alert alert-info" style="background: #E0F2FE; color: #0369A1; border-color: #BAE6FD;">
                            <?php echo $msg_assign; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group mb-0">
                                <label>Professeur</label>
                                <select name="prof_id" required>
                                    <option value="">-- Choisir un professeur --</option>
                                    <?php foreach ($profs as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label>Matière</label>
                                <select name="subject_id" required>
                                    <option value="">-- Choisir une matière --</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['nom']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Coefficient</label>
                                <input type="number" name="coefficient" value="1" min="1" max="10" required>
                                <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">(Appliqué aux bulletins)</span>
                            </div>

                            <div class="form-group">
                                <label>Sélectionner les Classes concernées :</label>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.5rem; max-height: 150px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem; background: var(--bg-color);">
                                    <?php foreach ($classes as $c): ?>
                                        <label style="display: flex; align-items: center; font-weight: 500; cursor: pointer; margin: 0; padding: 0.25rem;">
                                            <input type="checkbox" name="classes[]" value="<?php echo $c['id']; ?>" style="width: auto; margin: 0 0.5rem 0 0;">
                                            <?php echo htmlspecialchars($c['nom']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="assign_prof" class="btn w-full mt-2">Valider l'attribution</button>
                    </form>
                </div>

                <div class="card mt-4">
                    <h3 class="card-title">Attributions Actuelles</h3>
                    
                    <?php if (count($assignments) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Professeur</th>
                                        <th>Matière</th>
                                        <th>Classe</th>
                                        <th style="min-width: 180px;">Remplaçant / Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($a['prof']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($a['subject']); ?> 
                                                <br><span style="font-size: 0.8rem; color: var(--text-muted);">Coeff: <?php echo $a['coefficient']; ?></span>
                                            </td>
                                            <td><span class="badge" style="background: var(--bg-color); color: var(--text-main); border: 1px solid var(--border-color);"><?php echo htmlspecialchars($a['class']); ?></span></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                                    <form method="POST" style="margin: 0; flex: 1; min-width: 120px;">
                                                        <input type="hidden" name="assign_id" value="<?php echo $a['id']; ?>">
                                                        <input type="hidden" name="edit_assign" value="1">
                                                        <select name="new_prof_id" onchange="if(confirm('Transférer cette matière à ce nouveau professeur ?')) this.form.submit()" style="padding: 0.3rem; font-size: 0.8rem; height: 32px;">
                                                            <option value="">Transférer...</option>
                                                            <?php foreach ($profs as $p): ?>
                                                                <?php if ($p['nom'] != $a['prof']): ?>
                                                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nom']); ?></option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                    <form method="POST" id="deleteForm-<?php echo $a['id']; ?>" style="margin: 0; display: inline;">
                                                        <input type="hidden" name="delete_assign" value="<?php echo $a['id']; ?>">
                                                        <button type="button" onclick="showDeleteModal(<?php echo $a['id']; ?>)" class="btn btn-danger" style="font-size: 1rem; padding: 0; background: #fff; color: #dc2626; border: 1px solid #fee2e2; box-shadow: var(--shadow-sm); height: 32px; width: 32px; display: inline-flex; align-items: center; justify-content: center;" title="Supprimer">🗑️</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted" style="padding: 2rem;">
                            Aucune attribution n'a été faite pour cette année scolaire.
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
        <div class="modal-title" id="modalTitle">Suppression</div>
        <div class="modal-text" id="modalText">
            Êtes-vous sûr de vouloir supprimer cette attribution ? <br>
            <strong>Cette action est irréversible.</strong>
        </div>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-cancel" onclick="hideDeleteModal()">Annuler</button>
            <button class="modal-btn modal-btn-delete" id="confirmDeleteBtn">Supprimer</button>
        </div>
    </div>
</div>

<!-- Modal de Modification de Matière -->
<div class="modal-overlay" id="editSubjectModal">
    <div class="modal-content">
        <div class="modal-title">Modifier la Matière</div>
        <form method="POST">
            <input type="hidden" name="edit_subject_id" id="edit_subject_id">
            <div class="form-group" style="text-align: left; margin-top: 1rem;">
                <label>Nouveau nom</label>
                <input type="text" name="new_subject_name" id="new_subject_name" required>
            </div>
            <div class="modal-actions" style="margin-top: 2rem;">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="hideEditSubjectModal()">Annuler</button>
                <button type="submit" class="modal-btn" style="background: var(--primary); color: white;">Enregistrer</button>
            </div>
        </form>
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
    document.getElementById('modalTitle').innerText = "Suppression";
    document.getElementById('modalText').innerHTML = "Êtes-vous sûr de vouloir supprimer cette attribution ? <br><strong>Cette action est irréversible.</strong>";
    const modal = document.getElementById('deleteModal');
    modal.classList.add('show');
}

function showDeleteSubjectModal(id, name) {
    formToSubmit = document.getElementById('deleteSubjectForm-' + id);
    document.getElementById('modalTitle').innerText = "Supprimer la Matière";
    document.getElementById('modalText').innerHTML = "Supprimer la matière <strong>" + name + "</strong> ? <br><em>Note: Vous ne pourrez pas supprimer une matière déjà reliée à des classes.</em>";
    const modal = document.getElementById('deleteModal');
    modal.classList.add('show');
}

function hideDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('show');
    formToSubmit = null;
}

function showEditSubjectModal(id, name) {
    document.getElementById('edit_subject_id').value = id;
    document.getElementById('new_subject_name').value = name;
    document.getElementById('editSubjectModal').classList.add('show');
}

function hideEditSubjectModal() {
    document.getElementById('editSubjectModal').classList.remove('show');
}

document.getElementById('confirmDeleteBtn').onclick = function() {
    if (formToSubmit) {
        formToSubmit.submit();
    }
};

// Close modals when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const editModal = document.getElementById('editSubjectModal');
    if (event.target == deleteModal) hideDeleteModal();
    if (event.target == editModal) hideEditSubjectModal();
}
</script>

</body>
</html>
