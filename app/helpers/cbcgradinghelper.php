<?php
/**
 * CBC Grading Engine for Junior Secondary (Grades 7-9)
 * Maps marks to official KNEC achievement levels
 */
class CBCGradingHelper {
    
    /**
     * Convert raw marks to CBC Achievement Level
     * Ranges based on official CBC JSS rubric
     */
    public static function getAchievementLevel(float $marks, float $maxMarks): string {
        if ($maxMarks <= 0 || $marks < 0) {
            return 'BE2'; // Default fallback
        }
        
        $percentage = ($marks / $maxMarks) * 100;
        
        // EE: Exceeding Expectations
        if ($percentage >= 90) return 'EE1';
        if ($percentage >= 80) return 'EE2';
        
        // ME: Meeting Expectations
        if ($percentage >= 70) return 'ME1';
        if ($percentage >= 65) return 'ME2';
        
        // AE: Approaching Expectations
        if ($percentage >= 55) return 'AE1';
        if ($percentage >= 50) return 'AE2';
        
        // BE: Below Expectations
        if ($percentage >= 40) return 'BE1';
        
        return 'BE2';
    }
    
    /**
     * Calculate Term SBA Average (Formative + Summative)
     */
    public static function calculateTermAverage(array $marks): float {
        if (empty($marks)) return 0.0;
        return round(array_sum($marks) / count($marks), 2);
    }
    
    /**
     * Convert Achievement Level to Grade Point (for reporting)
     */
    public static function levelToPoint(string $level): int {
        $points = [
            'EE1' => 8, 'EE2' => 7,
            'ME1' => 6, 'ME2' => 5,
            'AE1' => 4, 'AE2' => 3,
            'BE1' => 2, 'BE2' => 1
        ];
        return $points[$level] ?? 1;
    }
}
?>