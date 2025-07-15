<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Verify CSRF token from header
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token missing']);
    exit;
}

if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

// Validate required fields
$requiredFields = ['scan_id', 'result_status', 'result_summary', 'scan_steps', 'threats_found'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // 1. Update main scan record
    $stmt = $pdo->prepare("
        UPDATE scans 
        SET scan_status = 'completed',
            result_status = ?,
            result_summary = ?,
            threats_found = ?,
            completed_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([
        $input['result_status'],
        $input['result_summary'],
        (int)$input['threats_found'],
        (int)$input['scan_id'],
        $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Scan record not found or not owned by user');
    }

    // 2. Insert scan details
    $detailStmt = $pdo->prepare("
        INSERT INTO scan_details (
            scan_id,
            vulnerability_name,
            status,
            details,
            execution_time
        ) VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($input['scan_steps'] as $step) {
        if (!is_array($step)) {
            throw new Exception('Invalid step format');
        }

        $detailStmt->execute([
            (int)$input['scan_id'],
            $step['name'] ?? 'Unknown',
            $step['status'] ?? 'clean',
            $step['details'] ?? 'No details provided',
            $step['duration'] ?? 0
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'scan_id' => $input['scan_id'],
        'threats_found' => $input['threats_found']
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}