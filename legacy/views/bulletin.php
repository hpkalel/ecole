<?php
// views/bulletin.php
session_start();
require_once '../config/database.php';

// Configuration
define('SCHOOL_NAME', 'GROUPE SCOLAIRE ECOLE 2');
define('SCHOOL_ADDRESS', 'Quartier Administratif, Ville'); // Optionnel

// Accessible par Admin ou Prof (si implémenté pour Prof) ou Public link ?
// Pour l'instant, disons Admin, ou n'importe qui avec un lien protégé (mais ici on va faire simple : Admin only pour voir tout le monde)
// Ou alors on ajoute une page "Liste des Bulletins" dans Admin Dashboard.

// Si on est Admin, on doit fournir un student_id
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) die("Aucun élève sélectionné.");

// Récupérer l'année active (ou via GET si on implémente un sélecteur)
$school_year_id = $_GET['school_year_id'] ?? null;

if ($school_year_id) {
    $stmtYear = $pdo->prepare("SELECT * FROM school_years WHERE id = ?");
    $stmtYear->execute([$school_year_id]);
} else {
    $stmtYear = $pdo->query("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1");
}

$active_year = $stmtYear->fetch();
$current_year_id = $active_year ? $active_year['id'] : null;
$current_year_name = $active_year ? $active_year['name'] : 'N/A';
if (!$current_year_id) die("Aucune année scolaire active.");

// Déterminer la période (par défaut : Semestre 1 ou actuel)
$periode_actuelle = (date('n') < 2 || date('n') >= 9) ? 'Semestre 1' : 'Semestre 2';
$selected_periode = $_GET['periode'] ?? $periode_actuelle;

// Info Élève (Via Enrollment pour l'année en cours)
$stmt = $pdo->prepare("
    SELECT s.*, c.nom as class_name, se.class_id, se.statut as enrollment_status
    FROM students s 
    JOIN student_enrollments se ON s.id = se.student_id 
    JOIN classes c ON se.class_id = c.id 
    WHERE s.id = ? AND se.school_year_id = ?
");
$stmt->execute([$student_id, $current_year_id]);
$student = $stmt->fetch();

if (!$student) die("Élève introuvable.");

// Récupérer l'effectif de la classe pour cette année
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE class_id = ? AND school_year_id = ?");
$stmtCount->execute([$student['class_id'] ?? 0, $current_year_id]);
$effectif_total = $stmtCount->fetchColumn();

// Fonction utilitaire pour calculer la moyenne d'une période
require_once '../functions.php';


// Récupérer les notes via les évaluations pour la PÉRIODE sélectionnée
$sql = "
    SELECT 
        s.nom as subject_name,
        a.coefficient,
        u.nom as prof_name,
        e.nom as eval_name,
        e.type,
        g.valeur,
        g.appreciation
    FROM grades g
    JOIN evaluations e ON g.evaluation_id = e.id
    JOIN assignments a ON e.assignment_id = a.id
    JOIN subjects s ON a.subject_id = s.id
    JOIN users u ON a.prof_id = u.id

    WHERE g.student_id = ? 
    AND e.periode = ?
    AND a.school_year_id = ?
    ORDER BY s.nom, e.date
";
$stmtGrades = $pdo->prepare($sql);
$stmtGrades->execute([$student_id, $selected_periode, $current_year_id]);
$raw_grades = $stmtGrades->fetchAll();

// Traitement : Regrouper par matière et par type
$subjects_data = [];
$max_interros_count = 0;
$max_devoirs_count = 0;

foreach ($raw_grades as $row) {
    $subject = $row['subject_name'];
    if (!isset($subjects_data[$subject])) {
        $subjects_data[$subject] = [
            'prof' => $row['prof_name'],
            'coeff' => $row['coefficient'],
            'interros' => [],
            'devoirs' => [],
            'total_val' => 0,
            'count_val' => 0,
            
            // Stats Interros
            'total_interro' => 0,
            'count_interro' => 0
        ];
    }
    
    // Adding type to the array
    if ($row['type'] === 'interrogation') {
        $subjects_data[$subject]['interros'][] = $row;
        $subjects_data[$subject]['total_interro'] += $row['valeur'];
        $subjects_data[$subject]['count_interro']++;
    } else {
        $subjects_data[$subject]['devoirs'][] = $row;
    }

    // Global subject stats
    $subjects_data[$subject]['total_val'] += $row['valeur'];
    $subjects_data[$subject]['count_val']++;
    
    // Update Max Counts
    if (count($subjects_data[$subject]['interros']) > $max_interros_count) {
        $max_interros_count = count($subjects_data[$subject]['interros']);
    }
    if (count($subjects_data[$subject]['devoirs']) > $max_devoirs_count) {
        $max_devoirs_count = count($subjects_data[$subject]['devoirs']);
    }
}

// Calcul des moyennes pour l'affichage principal
$global_total = 0;
$global_coeff = 0;

foreach ($subjects_data as &$data) {
    // Moyenne Interro
    if ($data['count_interro'] > 0) {
        $data['avg_interro'] = $data['total_interro'] / $data['count_interro'];
    } else {
        $data['avg_interro'] = null;
    }

    // Moyenne Globale Matière
    if ($data['count_val'] > 0) {
        $data['average'] = $data['total_val'] / $data['count_val'];
        $data['weighted_score'] = $data['average'] * $data['coeff'];
        
        $global_total += $data['weighted_score'];
        $global_coeff += $data['coeff'];
    } else {
        $data['average'] = null;
        $data['weighted_score'] = 0;
    }
}
unset($data);

$global_average = $global_coeff > 0 ? ($global_total / $global_coeff) : null;
$global_average_display = $global_average !== null ? number_format($global_average, 2) : "N/A";

// LOGIQUE MOYENNE ANNUELLE (Uniquement si Semestre 2)
$avg_sem1 = null;
$avg_annuelle_display = null;

if ($selected_periode === 'Semestre 2') {
    // Calcul Moyenne Semestre 1 (2 Coefficients)
    $avg_sem1 = calculate_period_stats($pdo, $student_id, 'Semestre 1', $current_year_id);
    
    // Calcul Moyenne Semestre 2 (1 Coefficient - déjà calculé dans $global_average)
    $avg_sem2 = $global_average;

    if ($avg_sem1 !== null && $avg_sem2 !== null) {
        // Formule : (Moy Sem1 * 2 + Moy Sem2) / 3
        $moyenne_annuelle = (($avg_sem1 * 2) + $avg_sem2) / 3;
        $avg_annuelle_display = number_format($moyenne_annuelle, 2);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin - <?php echo htmlspecialchars($student['nom']); ?> - Ecole 2</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        :root {
            --bulletin-primary: #1e40af; /* Deep blue */
            --bulletin-secondary: #3b82f6; 
            --bulletin-bg: #f8fafc;
            --bulletin-card-bg: #ffffff;
            --bulletin-border: #e2e8f0;
            --bulletin-text: #0f172a;
            --bulletin-text-muted: #64748b;
        }
        body { background: var(--bulletin-bg); font-family: 'Inter', system-ui, -apple-system, sans-serif; color: var(--bulletin-text); min-height: 100vh; padding: 2rem 0; line-height: 1.5; }
        .bulletin-wrapper { max-width: 1100px; margin: 0 auto; padding: 0 1rem; }
        .bulletin-container { background: var(--bulletin-card-bg); padding: 3.5rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02); border: 1px solid var(--bulletin-border); position: relative; overflow: hidden; }
        
        /* Decorative Watermark */
        .bulletin-container::before {
            content: 'ECOLE 2';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 8rem;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.02);
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
        }

        .bulletin-header { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2.5rem; border-bottom: 2px solid var(--bulletin-primary); padding-bottom: 1.5rem; text-align: left; }
        .header-left { flex: 1.2; }
        .header-right { flex: 0.8; text-align: right; }
        .bulletin-header h2 { font-size: 1.7rem; margin: 0; letter-spacing: 0.04em; text-transform: uppercase; color: var(--bulletin-primary); font-weight: 900; white-space: nowrap; }
        .school-name { font-size: 1.4rem; font-weight: 800; color: var(--bulletin-text); text-transform: uppercase; margin-bottom: 0.3rem; letter-spacing: 0.02em; }
        .bulletin-subtitle { font-size: 0.85rem; color: var(--bulletin-text-muted); text-transform: uppercase; margin-bottom: 0; font-weight: 700; letter-spacing: 0.05em; }
        
        /* Table Styles Specific to Bulletin */
        .table-responsive { position: relative; z-index: 1; border-radius: 8px; border: 1px solid var(--bulletin-border); background: white; }
        .bulletin-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; border: none; }
        .bulletin-table th, .bulletin-table td { border-bottom: 1px solid var(--bulletin-border); border-right: 1px solid var(--bulletin-border); padding: 12px 8px; text-align: center; }
        .bulletin-table th:last-child, .bulletin-table td:last-child { border-right: none; }
        .bulletin-table th { background: #f1f5f9; font-weight: 700; color: var(--bulletin-text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; }
        .bulletin-table tr:last-child td { border-bottom: none; }
        .bulletin-table tbody tr:hover { background-color: #f8fafc; }
        
        .bulletin-table .average-row { font-weight: 800; background: #f8fafc; }
        .bulletin-table .align-left { text-align: left !important; padding-left: 1rem; }
        .bulletin-table td { color: var(--bulletin-text); }
        
        .student-info { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 2.5rem; background: #f8fafc; padding: 2rem; border-radius: 10px; border: 1px solid var(--bulletin-border); }
        .info-column { display: flex; flex-direction: column; gap: 0.9rem; }
        .info-row { display: grid; grid-template-columns: 100px 1fr; align-items: baseline; gap: 1rem; }
        .info-label { color: var(--bulletin-text-muted); text-transform: uppercase; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; }
        .info-value { color: var(--bulletin-text); font-weight: 600; font-size: 1rem; }
        
        .school-year-display { font-weight: 600; color: var(--bulletin-text-muted); margin-top: 0.2rem; font-size: 1.1rem; white-space: nowrap; }
        .moyenne-label { text-align: right; padding-right: 1.5rem; text-transform: uppercase; font-size: 0.95rem; white-space: nowrap; }
        .total-value { font-size: 1rem; background: #e2e8f0; }
        .average-value { font-size: 1.15rem; color: var(--bulletin-primary); background: #cbd5e1; font-weight: 800; }
        
        .signatures { position: relative; z-index: 1; display: flex; justify-content: space-between; margin-top: 5rem; padding: 0 1rem; }
        .signatures div { text-align: center; color: var(--bulletin-text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; display: flex; flex-direction: column; align-items: center; gap: 5rem; }
        .signatures div::after { content: ''; display: block; width: 180px; height: 1px; border-bottom: 2px solid var(--bulletin-border); }

        .badge-average { padding: 0.3rem 0.6rem; border-radius: 6px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; }
        .avg-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
        .avg-success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .avg-warning { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
        .avg-info { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }

        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: white; padding: 0; margin: 0; font-size: 10pt; }
            .no-print { display: none !important; }
            .only-print { display: block !important; }
            .bulletin-wrapper { max-width: 190mm; width: 190mm; padding: 0; margin: 0; }
            .bulletin-container { box-shadow: none; padding: 0; border-radius: 0; border: none; width: 100%; box-sizing: border-box; }
            .bulletin-container::before { opacity: 0.05; }
            .bulletin-table { font-size: 7.5pt; table-layout: fixed; width: 100%; border-spacing: 0; }
            .bulletin-table th, .bulletin-table td { border: 1px solid #000 !important; color: #000; padding: 3px 1px !important; min-width: 0 !important; overflow: hidden; text-overflow: ellipsis; }
            .bulletin-table th { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; font-size: 6.5pt; }
            
            /* Specific column widths for print */
            .col-matiere { width: 25%; }
            .col-coeff { width: 6%; }
            .col-note { width: 6%; }
            .col-moy { width: 8%; }
            .col-apprec { width: 15%; }
            .col-prof { width: 10%; }

            .bulletin-table .average-row { background: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .app-layout { display: block; }
            .bulletin-header { border-bottom-color: #000; margin-bottom: 1rem; }
            .student-info { background: transparent !important; border-color: #000; padding: 0.8rem; margin-bottom: 1rem; gap: 0.5rem; display: flex; justify-content: space-between; }
            .info-column { gap: 0.4rem; }
            .table-responsive { overflow: visible !important; border: none; border-radius: 0; width: 100%; }
            .signatures { margin-top: 3rem; }
        }

        /* Scale down for mobile while preserving side-by-side layout */
        @media (max-width: 768px) {
            .bulletin-wrapper { padding: 0.2rem; }
            .bulletin-container { 
                padding: 1.2rem; 
                width: auto; 
                max-width: 100%;
                border-radius: 4px;
            }
            .bulletin-container::before { font-size: 3rem; }
            .bulletin-header { margin-bottom: 1.2rem; gap: 0.3rem; padding-bottom: 1rem; }
            .bulletin-header h2 { font-size: 0.95rem; }
            .school-name { font-size: 0.8rem; }
            .bulletin-subtitle { font-size: 0.55rem; }
            .header-right .no-print select { font-size: 0.75rem; padding-right: 1.2rem; }
            .header-right .school-year-display { font-size: 0.75rem !important; }
            .moyenne-label { font-size: 0.7rem !important; padding-right: 0.5rem !important; }
            .total-value { font-size: 0.75rem !important; }
            .average-value { font-size: 0.85rem !important; }

            .student-info { 
                gap: 0.8rem; 
                padding: 0.8rem; 
                margin-bottom: 1.2rem;
            }
            .info-row { grid-template-columns: 65px 1fr; gap: 0.3rem; }
            .info-label { font-size: 0.55rem; }
            .info-value { font-size: 0.75rem; }
            .info-value[style*="font-size: 1.2rem"] { font-size: 0.85rem !important; }

            .bulletin-table { font-size: 0.6rem; }
            .bulletin-table th, .bulletin-table td { padding: 5px 2px; }
            .bulletin-table th { font-size: 0.5rem; }

            .signatures { margin-top: 2rem; font-size: 0.65rem; gap: 1rem; }
            .signatures div::after { width: 100px; }
            .signatures div { gap: 2.5rem; }
        }
    </style>
</head>
<body>

<div class="bulletin-wrapper">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;" class="no-print">
        <button onclick="history.back()" class="btn btn-secondary">&larr; Retour</button>
        <button onclick="window.print()" class="btn">🖨️ Imprimer le bulletin</button>
    </div>

    <div class="bulletin-container card">
        <div class="bulletin-header">
            <div class="header-left">
                <div class="school-name"><?php echo SCHOOL_NAME; ?></div>
                <div class="bulletin-subtitle">RÉPUBLIQUE DU BÉNIN</div>
                <div class="bulletin-subtitle" style="font-size: 0.75rem; margin-top: 0.2rem;">Fraternité - Justice - Travail</div>
            </div>
            
            <div class="header-right">
                <h2>BULLETIN DE NOTES</h2>
                <div class="school-year-display">ANNÉE SCOLAIRE : <?php echo htmlspecialchars($current_year_name); ?></div>
                <form method="GET" style="display: inline-block; margin-top: 0.5rem;" class="no-print">
                    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
                    <select name="periode" onchange="this.form.submit()" style="font-size: 1rem; font-weight: 600; font-family: inherit; cursor: pointer; padding-right: 2rem;">
                        <option value="Semestre 1" <?php if($selected_periode == 'Semestre 1') echo 'selected'; ?>>Semestre 1</option>
                        <option value="Semestre 2" <?php if($selected_periode == 'Semestre 2') echo 'selected'; ?>>Semestre 2</option>
                    </select>
                </form>
                <h3 class="only-print" style="display: none; font-size: 1.1rem; color: #666; font-weight: bold; margin-top: 0.3rem; text-transform: uppercase;"><?php echo htmlspecialchars($selected_periode); ?></h3>
            </div>
        </div>

        <div class="student-info">
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Élève :</span>
                    <span class="info-value" style="font-size: 1.2rem;"><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Matricule :</span>
                    <span class="info-value"><?php 
                        $mat = $student['matricule'] ?: str_pad($student['id'], 6, '0', STR_PAD_LEFT);
                        // Fix scientific notation if it happens (e.g. from Excel import)
                        if (is_numeric($mat) && strpos(strtoupper($mat), 'E') !== false) {
                            echo number_format((float)$mat, 0, '.', '');
                        } else {
                            echo htmlspecialchars($mat);
                        }
                    ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sexe :</span>
                    <span class="info-value"><?php echo ($student['sexe'] == 'M' ? 'Masculin' : 'Féminin'); ?></span>
                </div>
            </div>
            
            <div class="info-column">
                <div class="info-row">
                    <span class="info-label">Classe :</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['class_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value"><?php echo htmlspecialchars($student['enrollment_status']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label" style="min-width: 120px;">Effectif :</span>
                    <span class="info-value"><?php echo $effectif_total; ?> élèves</span>
                </div>
            </div>
        </div>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="bulletin-table">
                <thead>
                    <tr>
                        <th class="align-left col-matiere">Matière</th>
                        <th class="col-coeff">Coeff</th>
                        
                        <?php 
                        for ($i = 1; $i <= $max_interros_count; $i++) echo "<th class='col-note'>Int. $i</th>";
                        ?>
                        
                        <th class="col-moy" style="background: #f3f4f6;">Moy.<br>Int.</th>

                        <?php 
                        for ($i = 1; $i <= $max_devoirs_count; $i++) echo "<th class='col-note'>Dev. $i</th>";
                        ?>

                        <th class="col-moy" style="background: #e5e7eb;">Moy.<br>Gén.</th>
                        <th class="col-moy" style="background: #e5e7eb;">Moy.<br>Coeff.</th>
                        <th class="align-left col-apprec">Appréciation</th>
                        <th class="col-prof">Professeur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects_data as $subject => $data): ?>
                        <tr>
                            <td class="align-left">
                                <strong style="display: block;"><?php echo htmlspecialchars($subject); ?></strong>
                            </td>
                            <td style="color: var(--text-muted);"><?php echo $data['coeff']; ?></td>
                            
                            <!-- Interrogations -->
                            <?php 
                            for ($i = 0; $i < $max_interros_count; $i++) {
                                if (isset($data['interros'][$i])) {
                                    echo '<td>' . $data['interros'][$i]['valeur'] . '</td>';
                                } else {
                                    echo '<td style="background: #fafafa;"></td>';
                                }
                            }
                            ?>

                            <!-- Moyenne Interro -->
                            <td style="font-style: italic; background: #fafbfc; color: var(--text-muted);">
                                <?php echo $data['avg_interro'] !== null ? number_format($data['avg_interro'], 2) : '-'; ?>
                            </td>

                            <!-- Devoirs -->
                            <?php 
                            for ($i = 0; $i < $max_devoirs_count; $i++) {
                                if (isset($data['devoirs'][$i])) {
                                    echo '<td>' . $data['devoirs'][$i]['valeur'] . '</td>';
                                } else {
                                    echo '<td style="background: #fafafa;"></td>';
                                }
                            }
                            ?>

                            <!-- Moyenne Générale -->
                            <td style="font-weight: bold;">
                                <?php echo $data['average'] !== null ? number_format($data['average'], 2) : '-'; ?>
                            </td>
                            
                            <!-- Moyenne Coefficiée -->
                            <td style="font-weight: bold; background: #f9fafb;">
                                <?php echo $data['average'] !== null ? number_format($data['weighted_score'], 2) : '-'; ?>
                            </td>

                            <!-- Appréciation Automatique -->
                            <td class="align-left">
                                <?php 
                                if ($data['average'] !== null) {
                                    $avg = $data['average'];
                                    if ($avg < 8) echo "<span class='badge-average avg-danger'>Médiocre</span>";
                                    elseif ($avg < 10) echo "<span class='badge-average avg-danger'>Insuffisant</span>";
                                    elseif ($avg < 12) echo "<span class='badge-average avg-warning'>Passable</span>";
                                    elseif ($avg < 14) echo "<span class='badge-average avg-info'>Assez Bien</span>";
                                    elseif ($avg < 16) echo "<span class='badge-average avg-success'>Bien</span>";
                                    else echo "<span class='badge-average avg-success' style='background: #4f46e5; color: white;'>T. Bien</span>";
                                } else {
                                    echo "-";
                                }
                                ?>
                            </td>

                            <!-- Émargement / Prof -->
                            <td style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">
                                <?php echo htmlspecialchars($data['prof']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: bold;">
                        <td class="align-left">TOTAL / COEFF.</td>
                        <td><?php echo $global_coeff; ?></td>
                        <?php 
                        $colspan_filler = $max_interros_count + 1 + $max_devoirs_count + 1;
                        ?>
                        <td colspan="<?php echo $colspan_filler; ?>" style="background: #f8fafc;"></td>
                        
                        <!-- Moyenne Coeff (Total Weighted) -->
                        <td class="total-value"><?php echo number_format($global_total, 2); ?></td>
                        
                        <td colspan="2" style="background: #f8fafc;"></td>
                    </tr>
                    <tr class="average-row" style="background: #e2e8f0;">
                        <?php 
                        $colspan_label = 2 + $max_interros_count + 1 + $max_devoirs_count + 1;
                        ?>
                        <td colspan="<?php echo $colspan_label; ?>" class="moyenne-label">MOYENNE GÉNÉRALE <?php echo strtoupper($selected_periode); ?></td>
                        <td class="average-value"><?php echo $global_average_display; ?></td>
                        <td colspan="2" style="background: #e2e8f0;"></td>
                    </tr>

                    <?php if ($selected_periode === 'Semestre 2' && $avg_sem1 !== null): ?>
                        <tr style="background: #fff; font-weight: bold; border-top: 1px solid var(--bulletin-border);">
                            <td colspan="<?php echo $colspan_label; ?>" style="text-align: right; padding-right: 1.5rem; color: var(--bulletin-text-muted); text-transform: uppercase; font-size: 0.9rem;">Moyenne Semestre 1</td>
                            <td style="font-size: 1rem; color: var(--bulletin-text-muted);"><?php echo number_format($avg_sem1, 2); ?></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr style="background: #ecfdf5; font-weight: bold; border-top: 2px solid #10b981;">
                            <td colspan="<?php echo $colspan_label; ?>" style="text-align: right; padding-right: 1.5rem; color: #065f46; font-size: 1rem; text-transform: uppercase;">MOYENNE ANNUELLE</td>
                            <td style="font-size: 1.25rem; color: #065f46; background: #d1fae5;"><?php echo $avg_annuelle_display ?? '-'; ?></td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

        <div class="signatures">
            <div>Signatures des Parents</div>
            <div>Signature du Directeur</div>
        </div>
    </div>

</div>

</body>
</html>
