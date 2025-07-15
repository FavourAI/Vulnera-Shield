<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once 'models/Vulnerability.php';
require_once 'templates/header.php';

$vulnerabilityModel = new Vulnerability($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $vulnerabilityModel->create($_POST);
        $_SESSION['success'] = 'Vulnerability added successfully';
        header("Location: vulnerabilities.php");
        exit;
    } elseif (isset($_POST['update'])) {
        $vulnerabilityModel->update($_POST['id'], $_POST);
        $_SESSION['success'] = 'Vulnerability updated successfully';
        header("Location: vulnerabilities.php");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $vulnerabilityModel->delete($_GET['delete']);
    $_SESSION['success'] = 'Vulnerability deleted successfully';
    header("Location: vulnerabilities.php");
    exit;
}

$vulnerabilities = $vulnerabilityModel->getAll();
?>

    <div class="container py-4">
        <h1 class="mb-4">Vulnerability Management</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New Vulnerability</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Vulnerability Name</label>
                            <input type="text" class="form-control" name="vulnerability" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Regulations Violated</label>
                            <textarea class="form-control" name="regulations" rows="2" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remediation</label>
                            <textarea class="form-control" name="remediation" rows="3" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Risk Level</label>
                            <select class="form-select" name="risk_level" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Scan Duration (ms)</label>
                            <input type="number" class="form-control" name="scan_duration" value="2000" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="create" class="btn btn-primary">Add Vulnerability</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Current Vulnerabilities</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Category</th>
                            <th>Vulnerability</th>
                            <th>Risk</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($vulnerabilities as $vuln): ?>
                            <tr>
                                <td><?= htmlspecialchars($vuln['category']) ?></td>
                                <td><?= htmlspecialchars($vuln['vulnerability']) ?></td>
                                <td>
                                <span class="badge bg-<?=
                                $vuln['risk_level'] === 'critical' ? 'danger' :
                                    ($vuln['risk_level'] === 'high' ? 'warning' :
                                        ($vuln['risk_level'] === 'medium' ? 'info' : 'success'))
                                ?>">
                                    <?= ucfirst($vuln['risk_level']) ?>
                                </span>
                                </td>
                                <td><?= $vuln['is_active'] ? 'Active' : 'Inactive' ?></td>
                                <td>
                                    <a href="#editModal<?= $vuln['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal">
                                        Edit
                                    </a>
                                    <a href="vulnerabilities.php?delete=<?= $vuln['id'] ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $vuln['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $vuln['id'] ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Vulnerability</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Category</label>
                                                        <input type="text" class="form-control" name="category"
                                                               value="<?= htmlspecialchars($vuln['category']) ?>" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Vulnerability Name</label>
                                                        <input type="text" class="form-control" name="vulnerability"
                                                               value="<?= htmlspecialchars($vuln['vulnerability']) ?>" required>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Regulations Violated</label>
                                                        <textarea class="form-control" name="regulations" rows="2" required><?=
                                                            htmlspecialchars($vuln['regulations'])
                                                            ?></textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Remediation</label>
                                                        <textarea class="form-control" name="remediation" rows="3" required><?=
                                                            htmlspecialchars($vuln['remediation'])
                                                            ?></textarea>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Risk Level</label>
                                                        <select class="form-select" name="risk_level" required>
                                                            <option value="low" <?= $vuln['risk_level'] === 'low' ? 'selected' : '' ?>>Low</option>
                                                            <option value="medium" <?= $vuln['risk_level'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                                            <option value="high" <?= $vuln['risk_level'] === 'high' ? 'selected' : '' ?>>High</option>
                                                            <option value="critical" <?= $vuln['risk_level'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Scan Duration (ms)</label>
                                                        <input type="number" class="form-control" name="scan_duration"
                                                               value="<?= $vuln['scan_duration'] ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select" name="is_active">
                                                            <option value="1" <?= $vuln['is_active'] ? 'selected' : '' ?>>Active</option>
                                                            <option value="0" <?= !$vuln['is_active'] ? 'selected' : '' ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="update" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'templates/footer.php'; ?>