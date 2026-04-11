<?php
// functions.php

function calculate_period_stats($pdo, $student_id, $periode, $year_id) {
    if (!$student_id) return null;

    $sql = "
        SELECT 
            s.nom as subject_name,
            a.coefficient,
            g.valeur
        FROM grades g
        JOIN evaluations e ON g.evaluation_id = e.id
        JOIN assignments a ON e.assignment_id = a.id
        JOIN subjects s ON a.subject_id = s.id

        WHERE g.student_id = ? 
        AND e.periode = ?
        AND a.school_year_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $periode, $year_id]);
    $grades = $stmt->fetchAll();

    // Group by subject to calculate subject averages first
    $subjects = [];
    foreach ($grades as $g) {
        $sub = $g['subject_name'];
        if (!isset($subjects[$sub])) {
            $subjects[$sub] = ['total' => 0, 'count' => 0, 'coeff' => $g['coefficient']];
        }
        $subjects[$sub]['total'] += $g['valeur'];
        $subjects[$sub]['count']++;
    }

    $global_total = 0;
    $global_coeff = 0;

    foreach ($subjects as $sub) {
        if ($sub['count'] > 0) {
            $avg = $sub['total'] / $sub['count'];
            $global_total += $avg * $sub['coeff'];
            $global_coeff += $sub['coeff'];
        }
    }

    return $global_coeff > 0 ? $global_total / $global_coeff : null;
}

function calculate_annual_average($pdo, $student_id, $year_id) {
    $avg_sem1 = calculate_period_stats($pdo, $student_id, 'Semestre 1', $year_id);
    $avg_sem2 = calculate_period_stats($pdo, $student_id, 'Semestre 2', $year_id);

    if ($avg_sem1 !== null && $avg_sem2 !== null) {
        return (($avg_sem1 * 2) + $avg_sem2) / 3;
    }
    return null; // Pas assez de données
}

function get_next_class_name($current_class_name) {
    // Basic logic: 6e -> 5e -> 4e -> 3e -> 2nd -> 1re -> Tle
    // Conserve suffixes like " A", " B"
    
    // Pattern to match level and suffix
    if (preg_match('/^(\d+e|2nd|1re|Tle)\s*(.*)$/i', $current_class_name, $matches)) {
        $level = strtolower($matches[1]);
        $suffix = $matches[2]; // e.g. "A"

        $next_level = '';
        switch ($level) {
            case '6e': $next_level = '5e'; break;
            case '5e': $next_level = '4e'; break;
            case '4e': $next_level = '3e'; break;
            case '3e': $next_level = '2nd'; break;
            case '2nd': $next_level = '1re'; break;
            case '1re': $next_level = 'Tle'; break;
            case 'tle': return null; // Fin de cycle
            default: return null; // Unknown
        }

        return trim($next_level . ' ' . $suffix);
    }
    
    return null;
}
?>
