<?php
require_once '../config/db.php';
require_once 'models/FileType.php';
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$fileTypes=new FileType($pdo);
$allowedTypes = $fileTypes->getActive();
?>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .upload-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card-upload {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card-upload:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }

        .upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: white;
            margin-bottom: 1.5rem;
        }

        .upload-area:hover {
            border-color: var(--primary);
            background-color: rgba(52, 152, 219, 0.05);
        }

        .upload-area.dragover {
            border-color: var(--primary);
            background-color: rgba(52, 152, 219, 0.1);
        }

        .upload-icon {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .file-info-card {
            border-left: 4px solid var(--primary);
            border-radius: 8px;
        }

        .btn-scan {
            background-color: var(--primary);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-scan:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        .progress-bar {
            background-color: var(--primary);
        }

        .step-badge {
            background-color: var(--light);
            color: var(--dark);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
        }

        .file-type-badge {
            background-color: var(--light);
            color: var(--dark);
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 12px;
        }
    </style>

<div class="upload-container py-5">
    <div class="card card-upload">
        <div class="card-header text-center">
            <h2><i class="fas fa-shield-alt me-2"></i> Vulnera Shield Application Scanner</h2>
            <p class="mb-0">Advanced Application Vulnerability Scanning</p>
        </div>

        <div class="card-body p-4 p-md-5">

                <!-- Upload Form -->
                <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="upload-area" id="uploadContainer">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h4 class="mb-3">Drag & Drop Your File Here</h4>
                        <p class="text-muted mb-4">Or click to browse your files</p>
                        <span class="step-badge">Step 1: Select File</span>
                        <input type="file" name="file" id="fileInput" class="d-none" required>
                    </div>

                    <div id="fileInfo" class="mb-4 d-none">
                        <div class="card file-info-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">Selected File:</h5>
                                        <div class="d-flex align-items-center">
                                            <p class="mb-0 text-dark fw-medium" id="fileNameDisplay"></p>
                                            <span class="file-type-badge ms-2" id="fileTypeBadge"></span>
                                        </div>
                                        <p class="small text-muted mb-0" id="fileSizeDisplay"></p>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="changeFileBtn">
                                        <i class="fas fa-times me-1"></i> Change
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-scan btn-lg" id="scanBtn" disabled>
                            <i class="fas fa-shield-alt me-2"></i> Scan File
                        </button>
                        <p class="small text-muted mt-2">Maximum file size: 100MB • Supported formats: <?php echo implode(', ', $allowedTypes); ?></p>
                    </div>
                </form>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // File selection elements
        const fileInput = document.getElementById('fileInput');
        const uploadContainer = document.getElementById('uploadContainer');
        const fileInfo = document.getElementById('fileInfo');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        const fileTypeBadge = document.getElementById('fileTypeBadge');
        const fileSizeDisplay = document.getElementById('fileSizeDisplay');
        const changeFileBtn = document.getElementById('changeFileBtn');
        const scanBtn = document.getElementById('scanBtn');
        const uploadForm = document.getElementById('uploadForm');

        // Scan progress elements
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const scanSteps = document.getElementById('scanSteps');
        const resultsSection = document.getElementById('resultsSection');
        const finalResults = document.getElementById('finalResults');

        const maxSize = 100 * 1024 * 1024; // 100MB

        // File selection handlers
        uploadContainer.addEventListener('click', () => fileInput.click());

        uploadContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadContainer.classList.add('dragover');
        });

        uploadContainer.addEventListener('dragleave', () => {
            uploadContainer.classList.remove('dragover');
        });

        uploadContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadContainer.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection();
            }
        });

        fileInput.addEventListener('change', handleFileSelection);
        changeFileBtn.addEventListener('click', resetFileSelection);

        function handleFileSelection() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];

                // Validate file
                if (file.size > maxSize) {
                    showAlert('File size exceeds maximum limit of 100MB.', 'danger');
                    resetFileSelection();
                    return;
                }

                // Check file extension
                // const allowedExtensions = ['exe', 'dll', 'docx', 'xlsx', 'pptx', 'pdf', 'zip'];
                const fileExt = file.name.split('.').pop().toLowerCase();

                // if (!allowedExtensions.includes(fileExt)) {
                //     showAlert('Invalid file type. Please upload a supported file format.', 'danger');
                //     resetFileSelection();
                //     return;
                // }

                // Update UI
                fileNameDisplay.textContent = file.name;
                fileTypeBadge.textContent = fileExt.toUpperCase();
                fileSizeDisplay.textContent = formatFileSize(file.size);
                fileInfo.classList.remove('d-none');
                scanBtn.disabled = false;
            }
        }

        function resetFileSelection() {
            fileInput.value = '';
            fileInfo.classList.add('d-none');
            scanBtn.disabled = true;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.replaceWith(alertDiv);
            } else {
                uploadForm.after(alertDiv);
            }
        }

    });
</script>
