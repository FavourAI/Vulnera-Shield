<?php
class Scan
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM scans ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }


    function getScanRecommendations($scan_id, $user_id)
    {
        global $pdo; // Assuming you have a PDO connection in $pdo

        try {
            // Query to get all non-clean results with their recommendations
            $stmt = $pdo->prepare("
            SELECT r.recommendation, r.severity 
            FROM scan_results r
            JOIN scans s ON r.scan_id = s.id
            WHERE r.scan_id = :scan_id 
            AND s.user_id = :user_id
            AND r.status != 'clean'
            ORDER BY 
                CASE r.severity
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END
        ");

            $stmt->execute([':scan_id' => $scan_id, ':user_id' => $user_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return '<div class="alert alert-success">No issues found - your scan is clean!</div>';
            }

            // Generate HTML output
            $html = '<div class="scan-recommendations">';
            $html .= '<h4>Security Recommendations</h4>';
            $html .= '<ul class="recommendation-list">';

            foreach ($results as $row) {
                $severityClass = strtolower($row['severity'] ?? 'medium');
                $html .= sprintf(
                    '<li class="%s"><strong>%s:</strong> %s</li>',
                    htmlspecialchars($severityClass),
                    htmlspecialchars(ucfirst($severityClass)),
                    htmlspecialchars($row['recommendation'])
                );
            }

            $html .= '</ul></div>';

            return $html;

        } catch (PDOException $e) {
            error_log("Database error getting recommendations: " . $e->getMessage());
            return '<div class="alert alert-danger">Error loading recommendations. Please try again.</div>';
        }
    }

}