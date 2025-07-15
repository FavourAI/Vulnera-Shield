<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once 'templates/header.php';

// Pagination
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($currentPage - 1) * $perPage;

// Get total scans count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM scans WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalScans = $stmt->fetchColumn();

// Get scans for current page with threat count
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM scan_details sd 
            WHERE sd.scan_id = s.id AND sd.status != 'clean') as threats_found
    FROM scans s 
    WHERE s.user_id = ? 
    ORDER BY s.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$scans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = ceil($totalScans / $perPage);
?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Your Scan History</h2>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> New Scan
                    </a>
                </div>

                <?php if (count($scans) > 0): ?>
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Scan Date</th>
                                        <th>Status</th>
                                        <th>Threats</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($scans as $scan): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas <?= getFileIcon($scan['file_type']) ?> me-2 text-muted"></i>
                                                    <span><?= htmlspecialchars($scan['file_name']) ?></span>
                                                </div>
                                            </td>
                                            <td><?= strtoupper($scan['file_type']) ?></td>
                                            <td><?= formatFileSize($scan['file_size']) ?></td>
                                            <td><?= date('M j, Y g:i a', strtotime($scan['created_at'])) ?></td>
                                            <td>
                                            <span class="badge <?= getStatusBadgeClass($scan['scan_status']) ?>">
                                                <?= htmlspecialchars(ucfirst($scan['scan_status'])) ?>
                                            </span>
                                            </td>
                                            <td>
                                                <?php if ($scan['scan_status'] === 'completed'): ?>
                                                    <span class="badge <?= getThreatBadgeClass($scan['threats_found']) ?>">
                                                    <?= $scan['threats_found'] ?> threat<?= $scan['threats_found'] != 1 ? 's' : '' ?>
                                                </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="scan_details.php?id=<?= $scan['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-file-archive fa-4x text-muted mb-4"></i>
                            <h4 class="mb-3">No scans found</h4>
                            <p class="text-muted mb-4">You haven't scanned any files yet.</p>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Scan Your First File
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
// Helper functions
function getFileIcon($fileType) {
    $icons = [
        'exe' => 'fa-cog',
        'dll' => 'fa-puzzle-piece',
        'docx' => 'fa-file-word',
        'xlsx' => 'fa-file-excel',
        'pptx' => 'fa-file-powerpoint',
        'pdf' => 'fa-file-pdf',
        'zip' => 'fa-file-archive'
    ];
    return $icons[$fileType] ?? 'fa-file';
}

function formatFileSize($bytes) {
    // Handle non-numeric, null, or zero values
    if (!is_numeric($bytes))
    return '0 Bytes';
    if ($bytes <= 0) return '0 Bytes';

    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];

    try {
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    } catch (Exception $e) {
        return '0 Bytes';
    }
}
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed': return 'bg-success';
        case 'in_progress': return 'bg-info';
        case 'pending': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getThreatBadgeClass($threatCount) {
    if ($threatCount === 0) return 'bg-success';
    if ($threatCount <= 3) return 'bg-warning';
    return 'bg-danger';
}

require_once 'templates/footer.php';
?>