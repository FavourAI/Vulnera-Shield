<?php
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Verify CSRF token
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
}

// Clear scan session
unset($_SESSION['scan_in_progress']);
unset($_SESSION['scan_id']);
unset($_SESSION['file_path']);

echo json_encode(['success' => true]);
?>