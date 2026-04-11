<?php
// views/promote_students.php
session_start();
require_once '../config/database.php';
require_once '../functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if Admin (assuming role is stored in session or we check DB)
// For now, minimal check as in other files
// $is_admin = ...

// 1. Get Active Year (Source Year)
$stmt = $pdo->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1");
$current_year = $stmt->fetch();
if (!$current_year) die("Aucune année active trouvée.");

$current_year_id = $current_year['id'];

// 2. Handle Promotion Execution
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote'])) {
    $target_year_name = $_POST['target_year_name'];
    
    // Check if target year exists, else create it
    $stmt = $pdo->prepare("SELECT id FROM school_years WHERE name = ?");
    $stmt->execute([$target_year_name]);
    $target_year_id = $stmt->fetchColumn();

    if (!$target_year_id) {
        // Create new year (inactive by default defined in DB, but we might want to activate it later?)
        // The user request didn't specify activating it, just promoting students TO it.
        $stmt = $pdo->prepare("INSERT INTO school_years (name, is_active) VALUES (?, 0)");
        $stmt->execute([$target_year_name]);
        $target_year_id = $pdo->lastInsertId();
    }

    // Process Promotions
    // Fetch all students enrolled in current year
    $sql = "
        SELECT s.id as student_id, s.nom, s.prenom, c.nom as class_name, c.id as class_id
        FROM student_enrollments se
        JOIN students s ON se.student_id = s.id
        JOIN classes c ON se.class_id = c.id
        WHERE se.school_year_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_year_id]);
    $students = $stmt->fetchAll();

    $promoted_count = 0;
    $repeated_count = 0;
    $errors = [];

    foreach ($students as $student) {
        $avg = calculate_annual_average($pdo, $student['student_id'], $current_year_id);
        
        // Determine target class
        if ($avg !== null && $avg >= 10) {
            // Pass -> Next Class
            $next_class_name = get_next_class_name($student['class_name']);
            if ($next_class_name) {
                // Find or Create Class
                $stmtC = $pdo->prepare("SELECT id FROM classes WHERE nom = ?");
                $stmtC->execute([$next_class_name]);
                $next_class_id = $stmtC->fetchColumn();

                if (!$next_class_id) {
                    $stmtCreate = $pdo->prepare("INSERT INTO classes (nom) VALUES (?)");
                    $stmtCreate->execute([$next_class_name]);
                    $next_class_id = $pdo->lastInsertId();
                }
                $target_class_id = $next_class_id;
                $status = 'promoted';
            } else {
                // Fin de cycle or unknown -> Stay? Or specific logic?
                // For now, if null (e.g. Terminale), maybe they leave? 
                // Let's assume they are not enrolled in next year automatically if End of Cycle.
                // But request says: "classe 6e... Tle".
                // If Tle passes, they graduate. No enrollment in school next year?
                continue; 
            }
        } else {
            // Fail -> Repeat same class
            $target_class_id = $student['class_id'];
            $status = 'repeated';
        }

        // Enroll in target year
        try {
            $stmtEnroll = $pdo->prepare("
                INSERT INTO student_enrollments (student_id, class_id, school_year_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE class_id = VALUES(class_id)
            ");
            $stmtEnroll->execute([$student['student_id'], $target_class_id, $target_year_id]);
            
            if ($status == 'promoted') $promoted_count++;
            else $repeated_count++;

        } catch (Exception $e) {
            $errors[] = "Erreur pour " . $student['nom'] . " : " . $e->getMessage();
        }
    }
    
    $message = "Promotion terminée. Promus: $promoted_count, Redoublants: $repeated_count.";
    if (!empty($errors)) {
        $message .= "<br>Erreurs: " . implode(", ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Promotion des Élèves</title>
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
            <a href="manage_years.php" class="nav-link">📅 Années Scolaires</a>
            <a href="promote_students.php" class="nav-link active">🎓 Promotion</a>
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
                <h1 style="margin: 0; font-size: 1.5rem;">Promotion des Élèves</h1>
            </div>
            <a href="admin_dashboard.php" class="btn btn-secondary">Retour</a>
        </header>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 class="card-title">Paramètres de Promotion</h3>
            <p class="text-muted">Les élèves seront promus vers l'année scolaire suivante en fonction de leur moyenne annuelle.</p>
            <div style="background: var(--bg-color); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <ul style="margin: 0; padding-left: 1.25rem; color: var(--text-main);">
                    <li><strong>Moyenne &ge; 10 :</strong> Passage en classe supérieure (ex: 6e A &rarr; 5e A)</li>
                    <li><strong>Moyenne &lt; 10 :</strong> Redoublement (maintien dans la même classe)</li>
                </ul>
            </div>

            <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir lancer la promotion ? Cela créera les inscriptions pour la nouvelle année.');">
                <div class="form-group">
                    <label for="target_year_name">Nom de la nouvelle année scolaire (Destination) :</label>
                    <?php
                    // Suggest next year name logic
                    $parts = explode('-', $current_year['name']);
                    if (count($parts) == 2 && is_numeric($parts[0])) {
                        $start = intval($parts[0]) + 1;
                        $end = intval($parts[1]) + 1;
                        $suggested_name = "$start-$end";
                    } else {
                        $suggested_name = "";
                    }
                    ?>
                    <input type="text" id="target_year_name" name="target_year_name" value="<?php echo $suggested_name; ?>" required placeholder="ex: 2025-2026">
                </div>

                <button type="submit" name="promote" class="btn w-full">Lancer la Promotion Globale</button>
            </form>
        </div>

        <div class="card mt-4">
            <h3 class="card-title">Simulation / État Actuel</h3>
            <p class="text-muted">Aperçu des 50 premiers élèves basés sur les notes actuelles.</p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Classe Actuelle</th>
                            <th>Moyenne</th>
                            <th style="text-align: right;">Décision Projetée</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php
                    // Display existing students in current year
                     $sqlPreview = "
                        SELECT s.id as student_id, s.nom, s.prenom, c.nom as class_name
                        FROM student_enrollments se
                        JOIN students s ON se.student_id = s.id
                        JOIN classes c ON se.class_id = c.id
                        WHERE se.school_year_id = ?
                        LIMIT 50
                    ";
                    $stmtPreview = $pdo->prepare($sqlPreview);
                    $stmtPreview->execute([$current_year_id]);
                    while ($row = $stmtPreview->fetch()):
                        $avg = calculate_annual_average($pdo, $row['student_id'], $current_year_id);
                        $formatted_avg = $avg !== null ? number_format($avg, 2) : 'N/A';
                        
                        $decision = "Inconnu";
                        $color = "gray";
                        
                        if ($avg !== null) {
                            if ($avg >= 10) {
                                $next_class = get_next_class_name($row['class_name']);
                                if ($next_class) {
                                    $decision = "Passage en $next_class";
                                    $color = "green";
                                } else {
                                    $decision = "Fin de cursus (Diplômé?)";
                                    $color = "blue";
                                }
                            } else {
                                $decision = "Redoublement (" . $row['class_name'] . ")";
                                $color = "red";
                            }
                        }
                    ?>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($row['nom'] . ' ' . $row['prenom']); ?></td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($row['class_name']); ?></td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; font-weight: bold;"><?php echo $formatted_avg; ?></td>
                        <td style="padding: 8px; border: 1px solid #e2e8f0; color: <?php echo $color; ?>; font-weight: bold;"><?php echo $decision; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <p class="text-center mt-4 text-muted" style="font-size: 0.85rem;">Seuls les 50 premiers élèves sont affichés pour cet aperçu.</p>
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
