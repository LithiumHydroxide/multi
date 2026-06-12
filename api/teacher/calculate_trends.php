<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher');
header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

try {
    // 1. Get all active students in the school
    $stmt = $pdo->prepare("SELECT id FROM students WHERE school_id = ? AND status = 'active'");
    $stmt->execute([$schoolId]);
    $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($students)) {
        echo json_encode(['success' => true, 'message' => 'No active students found']);
        exit;
    }

    // 2. Get all subjects that actually have scores for these students
    $placeholders = implode(',', array_fill(0, count($students), '?'));
    $subjectsStmt = $pdo->prepare("
        SELECT DISTINCT a.subject_id 
        FROM scores sc
        JOIN assessments a ON sc.assessment_id = a.id
        WHERE sc.student_id IN ($placeholders) AND sc.school_id = ?
    ");
    $subjectsStmt->execute(array_merge($students, [$schoolId]));
    $subjectIds = $subjectsStmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Calculate trends per student, per subject
    foreach ($students as $studentId) {
        foreach ($subjectIds as $subjectId) {
            $termStats = [1 => [], 2 => [], 3 => []];
            
            // Fetch scores for this specific student and subject
            $scores = $pdo->prepare("
                SELECT a.term, (sc.marks_obtained / NULLIF(a.max_marks, 0)) * 100 as pct
                FROM scores sc
                JOIN assessments a ON sc.assessment_id = a.id
                WHERE sc.student_id = ? AND a.subject_id = ? AND sc.school_id = ?
            ");
            $scores->execute([$studentId, $subjectId, $schoolId]);
            
            while($row = $scores->fetch()) {
                if(isset($termStats[$row['term']])) {
                    $termStats[$row['term']][] = $row['pct'];
                }
            }
            
            // Skip if no scores exist for this subject
            if (empty($termStats[1]) && empty($termStats[2]) && empty($termStats[3])) {
                continue;
            }

            // Calculate Term Averages
            $t1 = !empty($termStats[1]) ? array_sum($termStats[1])/count($termStats[1]) : null;
            $t2 = !empty($termStats[2]) ? array_sum($termStats[2])/count($termStats[2]) : null;
            $t3 = !empty($termStats[3]) ? array_sum($termStats[3])/count($termStats[3]) : null;
            
            // Calculate Growth Slope (Term 3 - Term 1)
            $growth_slope = ($t1 !== null && $t3 !== null) ? $t3 - $t1 : 0;
            $trend_label = 'stable';
            if($growth_slope > 5) $trend_label = 'improving';
            elseif($growth_slope < -5) $trend_label = 'declining';
            
            // Calculate Consistency (Standard Deviation)
            $dataPoints = array_filter([$t1, $t2, $t3]);
            $consistency = 0;
            if(count($dataPoints) > 1) {
                $mean = array_sum($dataPoints)/count($dataPoints);
                $variance = array_sum(array_map(fn($x)=>pow($x-$mean,2), $dataPoints))/count($dataPoints);
                $consistency = sqrt($variance);
            }
            if($consistency > 10) $trend_label = 'highly_variable';
            
            // Insert or Update the Trend Record (Now using a VALID subject_id)
            $updateStmt = $pdo->prepare("
                INSERT INTO student_performance_trends 
                (student_id, subject_id, term_1_avg, term_2_avg, term_3_avg, growth_slope, consistency_score, trend_label)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                term_1_avg=VALUES(term_1_avg), 
                term_2_avg=VALUES(term_2_avg), 
                term_3_avg=VALUES(term_3_avg),
                growth_slope=VALUES(growth_slope), 
                consistency_score=VALUES(consistency_score), 
                trend_label=VALUES(trend_label)
            ");
            $updateStmt->execute([
                $studentId, 
                $subjectId, // ✅ FIXED: Uses actual subject ID instead of 0
                $t1, $t2, $t3, 
                $growth_slope, 
                $consistency, 
                $trend_label
            ]);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Trends calculated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>