<?php
require_once '../includes/auth.php';
include 'templates/header.php';
require_once '../config/db.php';
require_once 'models/FileType.php';

$fileType = new FileType($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $fileType->create($_POST);
        header("Location: file_types.php?success=created");
        exit;
    } elseif (isset($_POST['update'])) {
        $fileType->update($_POST['id'], $_POST);
        header("Location: file_types.php?success=updated");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $fileType->delete($_GET['delete']);
    header("Location: file_types.php?success=deleted");
    exit;
}

$fileTypes = $fileType->getAll();
$activeExtensions = $fileType->getActive();
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Allowed File Types</h2>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            File type <?= htmlspecialchars($_GET['success']) ?> successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Add New File Type</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="extension" class="form-label">Extension</label>
                        <input type="text" class="form-control" id="extension" name="extension" required
                               placeholder="pdf" pattern="[a-zA-Z0-9]+" title="Alphanumeric characters only">
                    </div>
                    <div class="col-md-5">
                        <label for="mime_type" class="form-label">MIME Type</label>
                        <input type="text" class="form-control" id="mime_type" name="mime_type" required
                               placeholder="application/pdf">
                    </div>
                    <div class="col-md-4">
                        <label for="is_active" class="form-label">Status</label>
                        <select class="form-select" id="is_active" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description"
                               placeholder="Portable Document Format">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="create" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add File Type
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Current Allowed File Types</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th>Extension</th>
                        <th>MIME Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fileTypes as $type): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($type['extension']) ?></code></td>
                            <td><?= htmlspecialchars($type['mime_type']) ?></td>
                            <td><?= htmlspecialchars($type['description']) ?></td>
                            <td>
                                    <span class="badge bg-<?= $type['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $type['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                            </td>
                            <td>
                                <a href="#editModal<?= $type['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="file_types.php?delete=<?= $type['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this file type?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $type['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit File Type</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="extension<?= $type['id'] ?>" class="form-label">Extension</label>
                                                <input type="text" class="form-control" id="extension<?= $type['id'] ?>"
                                                       name="extension" value="<?= htmlspecialchars($type['extension']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="mime_type<?= $type['id'] ?>" class="form-label">MIME Type</label>
                                                <input type="text" class="form-control" id="mime_type<?= $type['id'] ?>"
                                                       name="mime_type" value="<?= htmlspecialchars($type['mime_type']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="description<?= $type['id'] ?>" class="form-label">Description</label>
                                                <input type="text" class="form-control" id="description<?= $type['id'] ?>"
                                                       name="description" value="<?= htmlspecialchars($type['description']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label for="is_active<?= $type['id'] ?>" class="form-label">Status</label>
                                                <select class="form-select" id="is_active<?= $type['id'] ?>" name="is_active">
                                                    <option value="1" <?= $type['is_active'] ? 'selected' : '' ?>>Active</option>
                                                    <option value="0" <?= !$type['is_active'] ? 'selected' : '' ?>>Inactive</option>
                                                </select>
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

<?php include 'templates/footer.php';