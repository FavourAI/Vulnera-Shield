<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

// Get and validate input data
$data = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token (uncomment when ready)
// if (!isset($data['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
//     http_response_code(403);
//     die(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
// }

if (!isset($data['scan_id']) || !is_numeric($data['scan_id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid scan ID']));
}

try {
    $pdo->beginTransaction();

    // Get scan details with strict user validation
    $stmt = $pdo->prepare("SELECT file_path FROM scans WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['scan_id'], $_SESSION['user_id']]);
    $scan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scan) {
        throw new Exception('Scan record not found or access denied');
    }

    // First delete all scan_details records (as shown in scan_details.php)
    $stmt = $pdo->prepare("DELETE FROM scan_details WHERE scan_id = ?");
    $stmt->execute([$data['scan_id']]);

    // Then delete the scan record
    $stmt = $pdo->prepare("DELETE FROM scans WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['scan_id'], $_SESSION['user_id']]);

    // Verify deletion was successful
    if ($stmt->rowCount() === 0) {
        throw new Exception('No records were deleted');
    }

    // Delete the uploaded file if it exists
    if (!empty($scan['file_path'])) {
        $file_path = realpath($scan['file_path']);
        if ($file_path && is_file($file_path) && is_writable($file_path)) {
            unlink($file_path);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log('Delete Scan Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}