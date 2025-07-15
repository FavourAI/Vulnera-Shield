<?php
ob_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

if (!isset($_GET['id'])) {
    die('Invalid scan ID');
}

$scanId = (int)$_GET['id'];

// Verify scan belongs to user
$stmt = $pdo->prepare("SELECT * FROM scans WHERE id = ? AND user_id = ?");
$stmt->execute([$scanId, $_SESSION['user_id']]);
$scan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$scan) {
    die('Scan not found or access denied');
}

// Get complete scan details
$stmt = $pdo->prepare("
    SELECT sd.*, v.*
    FROM scan_details sd
    LEFT JOIN vulnerabilities v ON v.vulnerability = sd.vulnerability_name
    WHERE sd.scan_id = ? 
    ORDER BY sd.id
");
$stmt->execute([$scanId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Vulnerability Scanner');
$pdf->SetAuthor('Security Team');
$pdf->SetTitle('Detailed Scan Report - ' . $scan['file_name']);
$pdf->SetSubject('Comprehensive Vulnerability Report');

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Add a page (Cover Page)
$pdf->AddPage();
$pdf->SetFillColor(23, 55, 94); // Dark blue background
$pdf->Rect(0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), 'F');
$pdf->SetTextColor(255, 255, 255); // White text
$pdf->SetFont('helvetica', 'B', 24);
$pdf->Cell(0, 40, '', 0, 1); // Spacer
$pdf->Cell(0, 20, 'SECURITY ASSESSMENT REPORT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 16);
$pdf->Cell(0, 10, 'Comprehensive Vulnerability Analysis', 0, 1, 'C');
$pdf->Cell(0, 40, '', 0, 1); // Spacer
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, $scan['file_name'], 0, 1, 'C');
$pdf->SetFont('helvetica', '', 14);
$pdf->Cell(0, 10, 'Scan Date: ' . date('F j, Y', strtotime($scan['created_at'])), 0, 1, 'C');
$pdf->Cell(0, 10, 'Report Generated: ' . date('F j, Y'), 0, 1, 'C');
$pdf->Cell(0, 40, '', 0, 1); // Spacer
$pdf->SetFont('helvetica', 'I', 12);
$pdf->Cell(0, 10, 'Confidential - For Authorized Personnel Only', 0, 1, 'C');

// Add a page (Executive Summary)
$pdf->AddPage();
$pdf->SetTextColor(0, 0, 0); // Black text

// Header with logo
$pdf->SetFillColor(23, 55, 94);
$pdf->Rect(0, 0, $pdf->getPageWidth(), 20, 'F');
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 20, 'EXECUTIVE SUMMARY', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

// Summary box
$pdf->SetFillColor(240, 240, 240);
$pdf->Rect(15, $pdf->GetY(), 180, 30, 'F');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Scan Overview', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$threatCount = count(array_filter($results, fn($r) => $r['status'] !== 'clean'));
$riskLevel = 'Low';
if ($threatCount > 5) $riskLevel = 'Medium';
if ($threatCount > 10) $riskLevel = 'High';

$pdf->Cell(90, 7, 'File Name: ' . $scan['file_name'], 0, 0);
$pdf->Cell(90, 7, 'Scan Date: ' . date('F j, Y H:i', strtotime($scan['created_at'])), 0, 1);
$pdf->Cell(90, 7, 'File Type: ' . strtoupper($scan['file_type']), 0, 0);
$pdf->Cell(90, 7, 'File Size: ' . formatFileSize($scan['file_size']), 0, 1);
$pdf->Cell(90, 7, 'Total Checks: ' . count($results), 0, 0);
$pdf->Cell(90, 7, 'Threats Found: ' . $threatCount, 0, 1);
$pdf->Cell(0, 7, 'Overall Risk Level: ' . $riskLevel, 0, 1);
$pdf->Ln(10);

// Risk Summary Pie Chart (simulated with colored text)
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Risk Distribution', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$riskLevels = ['high' => 0, 'medium' => 0, 'low' => 0];
foreach ($results as $result) {
    if (!empty($result['risk_level'])) {
        $riskLevels[strtolower($result['risk_level'])]++;
    }
}

$pdf->Cell(0, 7, 'High Risk: ' . $riskLevels['high'], 0, 1);
$pdf->Cell(0, 7, 'Medium Risk: ' . $riskLevels['medium'], 0, 1);
$pdf->Cell(0, 7, 'Low Risk: ' . $riskLevels['low'], 0, 1);
$pdf->Ln(15);

// Critical Findings
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Critical Findings', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$criticalFindings = array_filter($results, fn($r) => isset($r['risk_level']) && strtolower($r['risk_level']) === 'high');
if (count($criticalFindings) > 0) {
    foreach ($criticalFindings as $finding) {
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, '• ' . $finding['vulnerability_name'], 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, substr($finding['details'], 0, 200) . '...', 0, 'L');
        $pdf->Ln(3);
    }
} else {
    $pdf->Cell(0, 7, 'No critical findings detected', 0, 1);
}
$pdf->Ln(10);

// Violated Regulations
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Violated Regulations', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$regulations = [];
foreach ($results as $result) {
    if (!empty($result['regulations'])) {
        $regs = explode(',', $result['regulations']);
        foreach ($regs as $reg) {
            $reg = trim($reg);
            if (!empty($reg) && !in_array($reg, $regulations)) {
                $regulations[] = $reg;
            }
        }
    }
}

if (count($regulations) > 0) {
    foreach ($regulations as $regulation) {
        $pdf->Cell(0, 7, '• ' . $regulation, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No specific regulations violated', 0, 1);
}
$pdf->Ln(15);

// Top Recommendations
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Top Recommendations', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$recommendations = [];
foreach ($results as $result) {
    if (!empty($result['remediation']) && !in_array($result['remediation'], $recommendations)) {
        $recommendations[] = $result['remediation'];
        if (count($recommendations) >= 5) break;
    }
}

if (count($recommendations) > 0) {
    foreach ($recommendations as $rec) {
        $pdf->Cell(0, 7, '• ' . $rec, 0, 1);
    }
} else {
    $pdf->Cell(0, 7, 'No specific recommendations available', 0, 1);
}

// Detailed Findings (starts on new page)
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'DETAILED VULNERABILITY FINDINGS', 0, 1, 'C');
$pdf->Ln(10);

foreach ($results as $index => $result) {
    // Vulnerability header with colored background based on risk
    $riskColor = [255, 255, 255]; // Default white
    if (isset($result['risk_level'])) {
        switch (strtolower($result['risk_level'])) {
            case 'high': $riskColor = [255, 200, 200]; break;
            case 'medium': $riskColor = [255, 235, 200]; break;
            case 'low': $riskColor = [220, 255, 220]; break;
        }
    }

    $pdf->SetFillColor($riskColor[0], $riskColor[1], $riskColor[2]);
    $pdf->Rect(15, $pdf->GetY(), 180, 10, 'F');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, ($index + 1) . '. ' . $result['vulnerability_name'], 0, 1);

    // Details table
    $pdf->SetFont('helvetica', '', 10);

    $details = [
        'Status' => ucfirst($result['status']),
        'Category' => $result['category'] ?? 'N/A',
        'Risk Level' => isset($result['risk_level']) ? ucfirst($result['risk_level']) : 'N/A',
        'Detection Method' => $result['detection_method'] ?? 'Signature-based',
        'First Detected' => date('Y-m-d H:i', strtotime($result['created_at'] ?? 'now'))
    ];

    foreach ($details as $label => $value) {
        $pdf->Cell(40, 7, $label . ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, $value, 0, 1);
        $pdf->SetFont('helvetica', '', 10);
    }

    // Description
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Description:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, $result['details'], 0, 'L');
    $pdf->Ln(3);

    // Impact
    if (!empty($result['impact'])) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'Potential Impact:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $result['impact'], 0, 'L');
        $pdf->Ln(3);
    }

    // Regulations
    if (!empty($result['regulations'])) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'Regulatory Impact:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $result['regulations'], 0, 'L');
        $pdf->Ln(3);
    }

    // Remediation
    if (!empty($result['remediation'])) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 7, 'Recommended Remediation:', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $result['remediation'], 0, 'L');
        $pdf->Ln(3);
    }

    // Technical Details
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Technical Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    $techDetails = [
        'Check Duration' => ($result['execution_time'] ?? 'N/A') . ' ms',
        'Vulnerability ID' => $result['id'] ?? 'N/A',
        'Scan Timestamp' => date('Y-m-d H:i:s', strtotime($result['created_at'] ?? 'now'))
    ];

    foreach ($techDetails as $label => $value) {
        $pdf->Cell(50, 7, $label . ':', 0, 0);
        $pdf->Cell(0, 7, $value, 0, 1);
    }

    $pdf->Ln(10);
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(10);
}

// Appendices
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'APPENDICES', 0, 1, 'C');
$pdf->Ln(10);

// Appendix A: Risk Level Definitions
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Appendix A: Risk Level Definitions', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$pdf->SetFillColor(255, 200, 200);
$pdf->Rect(15, $pdf->GetY(), 180, 10, 'F');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'High Risk', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 7, "Critical vulnerabilities that could lead to system compromise, data breach, or complete loss of system integrity. These should be addressed immediately.", 0, 'L');
$pdf->Ln(5);

$pdf->SetFillColor(255, 235, 200);
$pdf->Rect(15, $pdf->GetY(), 180, 10, 'F');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Medium Risk', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 7, "Significant vulnerabilities that could lead to limited compromise or degradation of system performance. These should be addressed in a timely manner.", 0, 'L');
$pdf->Ln(5);

$pdf->SetFillColor(220, 255, 220);
$pdf->Rect(15, $pdf->GetY(), 180, 10, 'F');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Low Risk', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(0, 7, "Minor vulnerabilities with limited impact potential or requiring unlikely conditions to be exploited. These should be addressed as part of regular maintenance.", 0, 'L');
$pdf->Ln(15);

// Appendix B: Scan Methodology
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Appendix B: Scan Methodology', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$methodology = "This security assessment was conducted using a comprehensive vulnerability detection system that employs multiple analysis techniques to identify potential security issues:\n\n" .
    "• Signature-based Detection: Identifies known vulnerabilities by matching against a database of known threat signatures\n" .
    "• Heuristic Analysis: Detects previously unknown vulnerabilities by analyzing code behavior patterns\n" .
    "• Behavioral Monitoring: Observes system behavior during execution to identify suspicious activities\n" .
    "• Compliance Checking: Verifies compliance against industry standards and regulations\n\n" .
    "The scan was performed with the most recent vulnerability definitions available at the time of scanning.";

$pdf->MultiCell(0, 7, $methodology, 0, 'L');
$pdf->Ln(15);

// Appendix C: Glossary
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Appendix C: Glossary of Terms', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$glossary = [
    'Vulnerability' => 'A weakness in the system that could be exploited by a threat',
    'Exploit' => 'A method or technique used to take advantage of a vulnerability',
    'Risk' => 'The potential for loss or damage when a threat exploits a vulnerability',
    'Remediation' => 'Actions taken to resolve or mitigate a vulnerability',
    'False Positive' => 'When a scanner incorrectly identifies a vulnerability that doesn\'t exist'
];

foreach ($glossary as $term => $definition) {
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 7, $term, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 7, $definition, 0, 'L');
    $pdf->Ln(3);
}
// At the end, before outputting PDF
ob_end_clean(); // Clean (erase) the output buffer and turn off output buffering
//$pdf->Output('detailed_scan_report_' . $scan['id'] . '.pdf', 'D');
// Output PDF
$pdf->Output('detailed_scan_report_' . $scan['id'] . '.pdf', 'D');

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>