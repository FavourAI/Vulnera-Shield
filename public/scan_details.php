<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Normal page display
require_once 'templates/header.php';

if (!isset($_GET['id'])) {
    header('Location: scan_history.php');
    exit;
}

$scanId = (int)$_GET['id'];

// Get scan details with threat count
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM scan_details sd 
            WHERE sd.scan_id = s.id AND sd.status != 'clean') as threats_found
    FROM scans s 
    WHERE s.id = ? AND s.user_id = ?
");
$stmt->execute([$scanId, $_SESSION['user_id']]);
$scan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$scan) {
    header('Location: scan_history.php');
    exit;
}

// Get scan results with vulnerability details
$stmt = $pdo->prepare("
    SELECT sd.*, v.category, v.regulations, v.remediation, v.risk_level
    FROM scan_details sd
    LEFT JOIN vulnerabilities v ON v.vulnerability = sd.vulnerability_name
    WHERE sd.scan_id = ? 
    ORDER BY sd.id
");
$stmt->execute([$scanId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalSteps = count($results);
$cleanSteps = count(array_filter($results, fn($r) => $r['status'] === 'clean'));
$threatSteps = count(array_filter($results, fn($r) => $r['status'] !== 'clean'));

// Get all non-clean results for summary
$threatResults = array_filter($results, fn($r) => $r['status'] !== 'clean');

function getFileIcon($fileType) {
    $icons = [
        'exe' => 'fa-cog',
        'apk' => 'fa-robot',
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
    $bytes = (int)$bytes;
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function getAlertClass($resultStatus) {
    switch (strtolower($resultStatus)) {
        case 'malicious': return 'danger';
        case 'medium_risk':
        case 'suspicious': return 'warning';
        default: return 'success';
    }
}

function getResultIcon($resultStatus) {
    switch (strtolower($resultStatus)) {
        case 'malicious': return 'fa-skull-crossbones';
        case 'medium_risk':
        case 'suspicious': return 'fa-exclamation-triangle';
        default: return 'fa-check-circle';
    }
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'detected': return 'bg-danger';
        case 'clean': return 'bg-success';
        default: return 'bg-secondary';
    }
}

// Prepare data for charts
$riskLevels = array_count_values(
    array_map(
        fn($r) => $r['risk_level'] ?? 'unknown',
        $results
    )
);
$categories = array_count_values(
    array_map(
        fn($r) => $r['category'] ?? 'uncategorized',
        $results
    )
);
?>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold">Scan Analysis Report</h2>
                    <div>
                        <a href="generate_pdf_report.php?id=<?= $scanId ?>" class="btn btn-danger me-2">
                            <i class="fas fa-file-pdf me-2"></i> Download PDF Report
                        </a>
                        <a href="scan_history.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to History
                        </a>
                    </div>
                </div>



                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">File Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="bg-light p-3 rounded me-3">
                                        <i class="fas <?= getFileIcon($scan['file_type']) ?> fa-3x text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($scan['file_name']) ?></h5>
                                        <div class="text-muted mb-1">
                                            <span class="badge bg-light text-dark"><?= strtoupper($scan['file_type']) ?></span>
                                            <span class="mx-2">•</span>
                                            <span><?= formatFileSize($scan['file_size']) ?></span>
                                        </div>
                                        <small class="text-muted">Uploaded: <?= date('M j, Y g:i a', strtotime($scan['created_at'])) ?></small>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="<?= htmlspecialchars($scan['file_path']) ?>"
                                       class="btn btn-outline-primary" download>
                                        <i class="fas fa-download me-2"></i> Download Original File
                                    </a>
                                    <button class="btn btn-outline-danger" id="deleteScanBtn">
                                        <i class="fas fa-trash-alt me-2"></i> Delete Scan Record
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Scan Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-<?= getAlertClass($scan['result_status']) ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="fas <?= getResultIcon($scan['result_status']) ?> fa-2x me-3"></i>
                                        <div>
                                            <h4 class="alert-heading mb-1">
                                                <?= ucfirst(str_replace('_', ' ', $scan['result_status'])) ?>
                                            </h4>
                                            <p class="mb-0"><?= htmlspecialchars($scan['result_summary']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="display-4 text-success"><?= $cleanSteps ?></div>
                                        <small class="text-muted">Clean</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="display-4 text-warning"><?= $threatSteps ?></div>
                                        <small class="text-muted">Threats</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="display-4 text-primary"><?= $totalSteps ?></div>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($threatResults)): ?>
                    <div class="card shadow-sm mb-4 border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Security Recommendations</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($threatResults as $threat): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?= htmlspecialchars($threat['vulnerability_name']) ?></strong>
                                                <span class="badge bg-<?= $threat['risk_level'] === 'high' ? 'danger' : 'warning' ?> ms-2">
                                            <?= ucfirst($threat['risk_level']) ?> risk
                                        </span>
                                                <div class="mt-1">
                                                    <?php if (!empty($threat['remediation'])): ?>
                                                        <?= nl2br(htmlspecialchars($threat['remediation'])) ?>
                                                    <?php else: ?>
                                                        No specific remediation provided. Consider consulting security documentation.
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="badge <?= getStatusBadgeClass($threat['status']) ?>">
                                        <?= ucfirst($threat['status']) ?>
                                    </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>


                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Detailed Vulnerability Analysis</h5>
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-filter me-1"></i> <?= $totalSteps ?> checks performed
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th width="25%">Vulnerability</th>
                                    <th width="60%">Details</th>
                                    <th width="15%">Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($result['vulnerability_name']) ?></strong>
                                            <?php if (!empty($result['category'])): ?>
                                                <br>
                                                <small class="text-muted">Category: <?= htmlspecialchars($result['category']) ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($result['risk_level'])): ?>
                                                <br>
                                                <span class="badge bg-<?= $result['risk_level'] === 'high' ? 'danger' : ($result['risk_level'] === 'medium' ? 'warning' : 'secondary') ?>">
                                                Risk: <?= ucfirst($result['risk_level']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($result['details']) ?>
                                            <?php if (!empty($result['regulations'])): ?>
                                                <div class="mt-2">
                                                    <strong>Regulations:</strong>
                                                    <p class="small mb-1"><?= nl2br(htmlspecialchars($result['regulations'])) ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($result['remediation'])): ?>
                                                <div class="mt-2">
                                                    <strong>Remediation:</strong>
                                                    <p class="small mb-1"><?= nl2br(htmlspecialchars($result['remediation'])) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                        <span class="badge <?= getStatusBadgeClass($result['status']) ?>">
                                            <?= ucfirst($result['status']) ?>
                                        </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>

        document.getElementById('deleteScanBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this scan record?')) {
                fetch('delete_scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'
                    },
                    body: JSON.stringify({
                        scan_id: <?= $scanId ?>
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'scan_history.php';
                        } else {
                            alert('Error: ' + (data.error || 'Failed to delete scan'));
                        }
                    });
            }
        });
    </script>

<?php
require_once 'templates/footer.php';
?>