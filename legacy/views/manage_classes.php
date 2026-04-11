<?php
// views/manage_classes.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Récupérer l'année scolaire active OU la plus récente
$stmtYear = $pdo->query("SELECT id, name FROM school_years WHERE is_active = 1 LIMIT 1");
$active_year = $stmtYear->fetch();
if (!$active_year) {
    // Fallback : take the most recent one
    $stmtYear = $pdo->query("SELECT id, name FROM school_years ORDER BY id DESC LIMIT 1");
    $active_year = $stmtYear->fetch();
}

if (!$active_year) {
    die("Erreur critique : Aucune année scolaire définie dans la base de données.");
}
$current_year_id = $active_year['id'];


// Traitement Import CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmpName, 'r');
        
        if ($handle) {
            $pdo->beginTransaction();
            try {
                $countStudents = 0;
                $countClasses = 0;
                
                // Ignorer l'en-tête si présent? On suppose format: Nom,Prenom,Classe
                // On peut ajouter une checkbox "Has Header"
                // Pour simplifier, on assume que la première ligne est des données ou on la skip si elle contient "Nom"
                
                // Auto-detect delimiter
                $firstLine = fgets($handle);
                rewind($handle); // Reset pointer
                $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    // data[0] = Nom, data[1] = Prenom, data[2] = Classe
                    if (count($data) < 3) continue;
                    
                    $nom = trim($data[0]);
                    $prenom = trim($data[1]);
                    $classeNom = trim($data[2]);
                    $sexe = isset($data[3]) ? strtoupper(trim($data[3])) : 'M';
                    $studentStatut = isset($data[4]) ? trim($data[4]) : 'Nouveau';
                    $matricule = isset($data[5]) ? trim($data[5]) : null;

                    // Normalisation sexe
                    if ($sexe !== 'F') $sexe = 'M';
                    // Normalisation statut
                    if (stripos($studentStatut, 'Redoubl') !== false) $studentStatut = 'Redoublant';
                    else $studentStatut = 'Nouveau';
                    
                    if (strtolower($nom) == 'nom' && strtolower($classeNom) == 'classe') continue; // Skip header simple check

                    // 1. Gérer la Classe
                    // Vérifier si la classe existe, sinon la créer
                    // Utilisation d'un cache local pour éviter trop de requêtes SELECT
                    static $classesCache = [];
                    
                    if (!isset($classesCache[$classeNom])) {
                        $stmtClass = $pdo->prepare("SELECT id FROM classes WHERE nom = ?");
                        $stmtClass->execute([$classeNom]);
                        $classId = $stmtClass->fetchColumn();
                        
                        if (!$classId) {
                            $stmtInsertClass = $pdo->prepare("INSERT INTO classes (nom) VALUES (?)");
                            $stmtInsertClass->execute([$classeNom]);
                            $classId = $pdo->lastInsertId();
                            $countClasses++;
                        }
                        $classesCache[$classeNom] = $classId;
                    } else {
                        $classId = $classesCache[$classeNom];
                    }

                    // 2. Insérer l'Élève (ou récupérer ID si existe déjà)
                    // On vérifie par Matricule en priorité, sinon Nom + Prénom
                    $studentId = null;
                    if ($matricule) {
                        $stmtCheckMatricule = $pdo->prepare("SELECT id FROM students WHERE matricule = ?");
                        $stmtCheckMatricule->execute([$matricule]);
                        $studentId = $stmtCheckMatricule->fetchColumn();
                    }

                    if (!$studentId) {
                        $stmtCheckStudent = $pdo->prepare("SELECT id FROM students WHERE nom = ? AND prenom = ?");
                        $stmtCheckStudent->execute([$nom, $prenom]);
                        $studentId = $stmtCheckStudent->fetchColumn();
                    }

                    if (!$studentId) {
                        $stmtStudent = $pdo->prepare("INSERT INTO students (nom, prenom, sexe, matricule) VALUES (?, ?, ?, ?)");
                        $stmtStudent->execute([$nom, $prenom, $sexe, $matricule]); 
                        $studentId = $pdo->lastInsertId();
                        $countStudents++;
                    } else if ($matricule) {
                        // Mettre à jour le matricule si l'élève a été trouvé par nom/prénom mais n'en avait pas
                        $stmtUpdateMatricule = $pdo->prepare("UPDATE students SET matricule = ? WHERE id = ? AND matricule IS NULL");
                        $stmtUpdateMatricule->execute([$matricule, $studentId]);
                    }

                    // 3. Inscription Annuelle (Enrollment)
                    $stmtEnroll = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, school_year_id, statut) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE class_id = VALUES(class_id), statut = VALUES(statut)");
                    $stmtEnroll->execute([$studentId, $classId, $current_year_id, $studentStatut]);
                }
                
                $pdo->commit();
                $_SESSION['message'] = "Import réussi : $countStudents élèves ajoutés, $countClasses nouvelles classes créées.";
                header("Location: manage_classes.php");
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Erreur lors de l'import : " . $e->getMessage();
            }
            fclose($handle);
        }
    } else {
        $message = "Erreur de fichier.";
    }
}

// Récupération des classes pour affichage
// Récupération des classes pour affichage avec nombre d'élèves INSCRITS cette année
$classes = $pdo->prepare("
    SELECT c.id, c.nom, COUNT(e.id) as student_count 
    FROM classes c 
    LEFT JOIN student_enrollments e ON c.id = e.class_id AND e.school_year_id = ?
    GROUP BY c.id
    ORDER BY c.nom
");
$classes->execute([$current_year_id]);
$classes = $classes->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Classes - Admin</title>
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
        <header class="app-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="mobile-nav-toggle" onclick="toggleSidebar()">☰</button>
                <h1 style="margin: 0; font-size: 1.5rem;">Classes & Élèves</h1>
            </div>
            <button class="btn btn-secondary" onclick="document.getElementById('importCard').scrollIntoView({behavior: 'smooth'})">Importer CSV</button>
        </header>
    
    <div class="card mt-4" id="importCard">
        <h3 class="card-title">Importer Élèves (CSV)</h3>
        <p>Le fichier doit avoir le format : <code>Nom, Prénom, Classe, Sexe (M/F), Statut, Matricule</code>. Les colonnes Sexe, Statut et Matricule sont optionnelles.</p>
        
        <?php 
        $message = $message ?: ($_SESSION['message'] ?? '');
        unset($_SESSION['message']);
        if ($message): ?>
            <div class="alert alert-info">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="dashboard-grid grid-cols-1-2" style="align-items: flex-end; gap: 1rem;">
                <div class="form-group mb-0">
                    <label for="csv_file">Fichier CSV</label>
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required style="padding: 0.5rem; background: white;">
                </div>
                <button type="submit" class="btn" style="height: 48px;">Lancer l'importation</button>
            </div>
        </form>
    </div>

    <div class="card mt-4">
        <h3 class="card-title">Liste des Classes (Année <?php echo htmlspecialchars($active_year['name']); ?>)</h3>
        <?php if (count($classes) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Nombre d'élèves</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($c['nom']); ?></td>
                                <td><span class="badge" style="background: var(--bg-color); color: var(--text-main);"><?php echo $c['student_count']; ?> élèves</span></td>
                                <td style="text-align: right;">
                                    <a href="view_students.php?class_id=<?php echo $c['id']; ?>" class="btn btn-secondary" style="font-size: 0.85rem;">Voir la liste</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>Aucune classe existante.</p>
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
