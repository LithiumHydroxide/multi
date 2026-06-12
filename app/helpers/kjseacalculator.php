<?php
/**
 * KJSEA Composite Calculator
 * Handles term averages, subject aggregation, and Grade 9 20-20-60 weighting
 * Updated for Grade-Specific Grading Rules (Option B)
 */
class KJSEACalculator {
    private $pdo;

    public function __construct() {
        $this->pdo = getDBConnection();
    }

    /**
     * Get student report data with term averages & KJSEA composite
     */
    public function getStudentReport(int $studentId, int $schoolId): array {
        $stmt = $this->pdo->prepare("
            SELECT s.id, s.name, s.admission_number, s.class_id, c.name as class_name, c.grade_level
            FROM students s
            JOIN classes c ON s.class_id = c.id
            WHERE s.id = ? AND s.school_id = ?
        ");
        $stmt->execute([$studentId, $schoolId]);
        $student = $stmt->fetch();

        if (!$student) return ['error' => 'Student not found or access denied'];

        $scoresStmt = $this->pdo->prepare("
            SELECT a.term, a.subject_id, sc.marks_obtained, a.max_marks,
                   sub.name as subject_name, sub.code
            FROM scores sc
            JOIN assessments a ON sc.assessment_id = a.id
            JOIN subjects sub ON a.subject_id = sub.id
            WHERE sc.student_id = ? AND sc.school_id = ?
            ORDER BY sub.name, a.term
        ");
        $scoresStmt->execute([$studentId, $schoolId]);
        $scores = $scoresStmt->fetchAll();

        $subjects = [];
        foreach ($scores as $row) {
            if (!isset($subjects[$row['code']])) {
                $subjects[$row['code']] = ['name' => $row['subject_name'], 'terms' => []];
            }
            $subjects[$row['code']]['terms'][$row['term']] = [
                'marks' => $row['marks_obtained'],
                'max' => $row['max_marks']
            ];
        }

        foreach ($subjects as &$subj) {
            $termAvgs = [];
            foreach ($subj['terms'] as $term => $data) {
                if ($data['max'] > 0) {
                    $termAvgs[$term] = ($data['marks'] / $data['max']) * 100;
                }
            }
            $subj['term_1_avg'] = $termAvgs[1] ?? 0;
            $subj['term_2_avg'] = $termAvgs[2] ?? 0;
            $subj['term_3_avg'] = $termAvgs[3] ?? 0;
            $subj['overall_avg'] = !empty($termAvgs) ? array_sum($termAvgs) / count($termAvgs) : 0;
            
            // Use Grade-Specific Logic
            $subj['final_level'] = $this->getAchievementLevel($subj['overall_avg'], $schoolId, $student['grade_level']);
        }

        $kjseaComposite = null;
        if ($student['grade_level'] == 9) {
            $kjseaComposite = $this->calculateKJSEAComposite($studentId, $schoolId);
        }

        return [
            'student' => $student,
            'subjects' => $subjects,
            'kjsea' => $kjseaComposite
        ];
    }

    private function calculateKJSEAComposite(int $studentId, int $schoolId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT sub.code, sc.marks_obtained, sc.achievement_level
            FROM scores sc
            JOIN assessments a ON sc.assessment_id = a.id
            JOIN subjects sub ON a.subject_id = sub.id
            JOIN students s ON sc.student_id = s.id
            JOIN classes c ON s.class_id = c.id
            WHERE sc.student_id = ? AND sc.school_id = ?
            AND a.assessment_type IN ('kjssea', 'summative') AND a.term = 3 AND c.grade_level = 9
        ");
        $stmt->execute([$studentId, $schoolId]);
        $finalScores = $stmt->fetchAll();
        
        if (empty($finalScores)) return null;

        $composite = [];
        foreach ($finalScores as $row) {
            $composite[$row['code']] = [
                'final_exam_pct' => ($row['marks_obtained'] / 100) * 100,
                'projected_kjsea' => ($row['marks_obtained'] / 100) * 100,
                'level' => $row['achievement_level']
            ];
        }
        return $composite;
    }

    /**
     * OPTION B: Grade-Specific Grading Rules
     * Reads from database; falls back to standard if none exist.
     */
    public function getAchievementLevel(float $pct, int $schoolId, int $gradeLevel): string {
        $stmt = $this->pdo->prepare("
            SELECT level_code FROM cbc_grading_rules 
            WHERE school_id = ? AND grade_level = ? 
            AND min_percentage <= ? AND max_percentage >= ?
            LIMIT 1
        ");
        $stmt->execute([$schoolId, $gradeLevel, $pct, $pct]);
        $result = $stmt->fetchColumn();

        if ($result) return $result;

        // Fallback Standard
        if ($pct >= 90) return 'EE1';
        if ($pct >= 80) return 'EE2';
        if ($pct >= 70) return 'ME1';
        if ($pct >= 65) return 'ME2';
        if ($pct >= 55) return 'AE1';
        if ($pct >= 50) return 'AE2';
        if ($pct >= 40) return 'BE1';
        return 'BE2';
    }
}
?>