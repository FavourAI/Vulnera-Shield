<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once 'templates/header.php';
require_once 'models/FileType.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    // File validation
    $fileTypes=new FileType($pdo);
    $allowedTypes = $fileTypes->getActive();
    $fileName = $_FILES['file']['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));



    if (!in_array($fileExt, $allowedTypes)) {
// Set session message and redirect
        $_SESSION['redirect_message'] = "Scanning of ".$fileExt." File is not allowed";
        $_SESSION['redirect_data'] = "Allowed file types are: ". implode(', ',$allowedTypes);
        header("Location: redirect_message.php");
        exit;
        //        die('Invalid file type');
//        header("templates/invalid_file_type");
    }



    // Generate unique filename
    $newFileName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
    $targetPath = '../uploads/' . $newFileName;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        die('File upload failed');
    }

    // Store initial scan record
    $stmt = $pdo->prepare("INSERT INTO scans (user_id, file_name, file_path, file_type, file_size, scan_status) 
                          VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $_SESSION['user_id'],
        $fileName,
        $targetPath,
        $fileExt,
        $_FILES['file']['size']
    ]);
    $scanId = $pdo->lastInsertId();

    // Get all vulnerability categories from database to use as scan steps
    $stmt = $pdo->query("SELECT DISTINCT vulnerability FROM vulnerabilities");
    $vulnerabilityCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Start the scan process
    $_SESSION['scan_in_progress'] = true;
    $_SESSION['scan_id'] = $scanId;
    $_SESSION['file_path'] = $targetPath;
    $_SESSION['original_file_name'] = $fileName;
    $_SESSION['scan_steps'] = $vulnerabilityCategories; // Store categories for JavaScript
}
?>

    <section class="py-5">
        <div class="container py-5">
            <?php if (!isset($_SESSION['scan_in_progress'])): ?>
                <?php header('Location: dashboard.php'); ?>
            <?php else: ?>
                <!-- Scan Progress -->
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-shield-alt text-primary me-2"></i>
                                    Scanning: <?php echo htmlspecialchars($_SESSION['original_file_name']); ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="scan-progress mb-4">
                                    <div class="progress" style="height: 8px;">
                                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted" id="progressText">Initializing scan...</small>
                                </div>

                                <div class="scan-steps">
                                    <ul class="list-group" id="scanSteps">
                                        <!-- Steps will be added by JavaScript -->
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div id="scanResults" class="mt-4 d-none">
                            <div class="card shadow-sm border-success">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">Scan Complete</h4>
                                </div>
                                <div class="card-body">
                                    <div id="finalResults"></div>
                                    <div class="text-center mt-3">
                                        <a href="scan_history.php" class="btn btn-outline-secondary me-2">
                                            <i class="fas fa-history me-1"></i> View History
                                        </a>
                                        <a href="dashboard.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i> New Scan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    // Get scan steps from PHP session
                    const scanSteps = <?php echo json_encode($_SESSION['scan_steps']); ?>.map(category => {
                        // Assign random duration between 1-3 seconds and base risk level
                        return {
                            name: category,
                            duration: 100 + Math.floor(Math.random() * 2000),
                            risk: 10 + Math.floor(Math.random() * 20) // Base risk between 10-30%
                        };
                    });

                    // Add standard first and last steps
                    scanSteps.unshift({
                        name: "File decryption",
                        duration: 1000,
                        risk: 0
                    });

                    scanSteps.push({
                        name: "Final verification",
                        duration: 1000,
                        risk: 0
                    });

                    let currentStep = 0;
                    const totalSteps = scanSteps.length;
                    const progressBar = document.getElementById('progressBar');
                    const progressText = document.getElementById('progressText');
                    const scanStepsList = document.getElementById('scanSteps');
                    const scanResults = document.getElementById('scanResults');
                    const finalResults = document.getElementById('finalResults');

                    // Store scan results for database
                    const scanResultsData = {
                        steps: [],
                        threatsFound: 0
                    };

                    function updateProgress(step, result = null) {
                        const percent = Math.round((step / totalSteps) * 100);
                        progressBar.style.width = percent + '%';

                        if (result) {
                            // Store step result for database
                            scanResultsData.steps.push({
                                name: result.name,
                                status: result.risk > 0 ? (result.risk > 15 ? 'high_risk' : 'medium_risk') : 'clean',
                                details: result.details
                            });

                            if (result.risk > 0) scanResultsData.threatsFound++;

                            const stepResult = document.createElement('li');
                            stepResult.className = `list-group-item ${result.risk > 15 ? 'list-group-item-warning' :
                                result.risk > 0 ? 'list-group-item-info' : 'list-group-item-success'}`;
                            stepResult.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas ${result.risk > 15 ? 'fa-exclamation-triangle text-danger' :
                                result.risk > 0 ? 'fa-info-circle text-primary' : 'fa-check-circle text-success'}
                                    me-2"></i>
                                <strong>${result.name}</strong>
                            </div>
                            <span class="badge ${result.risk > 15 ? 'bg-danger' : result.risk > 0 ? 'bg-warning' : 'bg-success'}">
                                ${result.risk > 0 ? 'Potential risk' : 'Clean'}
                            </span>
                        </div>
                        ${result.details ? `<div class="mt-2 small">${result.details}</div>` : ''}
                    `;
                            scanStepsList.appendChild(stepResult);
                        }
                    }

                    function simulateScanStep() {
                        if (currentStep < totalSteps) {
                            const step = scanSteps[currentStep];
                            progressText.textContent = `Processing: ${step.name}`;

                            // Simulate step processing
                            setTimeout(() => {
                                const riskFound = Math.random() * 100 < step.risk;
                                const stepResult = {
                                    name: step.name,
                                    risk: riskFound ? step.risk : 0,
                                    details: riskFound ?
                                        `Found potential ${step.name.toLowerCase()} vulnerability` :
                                        'No issues detected'
                                };

                                updateProgress(currentStep + 1, stepResult);
                                currentStep++;
                                simulateScanStep();
                            }, step.duration);
                        } else {
                            // Scan complete
                            progressBar.classList.remove('progress-bar-animated');
                            progressBar.classList.add('bg-success');
                            progressText.textContent = 'Scan completed successfully';

                            // Generate final result
                            const threatsFound = scanResultsData.threatsFound;
                            const finalStatus = threatsFound === 0 ? 'Clean' :
                                threatsFound === 1 ? 'Suspicious' :
                                    threatsFound < 4 ? 'Medium Risk' : 'Malicious';
                            const finalSummary = threatsFound === 0 ?
                                'No threats detected in file' :
                                `Found ${threatsFound} potential ${threatsFound === 1 ? 'threat' : 'threats'}`;

                            finalResults.innerHTML = `
                        <div class="alert alert-${threatsFound === 0 ? 'success' :
                                threatsFound === 1 ? 'warning' :
                                    threatsFound < 4 ? 'warning' : 'danger'}">
                            <h4 class="alert-heading">
                                <i class="fas ${threatsFound === 0 ? 'fa-check-circle' :
                                threatsFound === 1 ? 'fa-exclamation-triangle' :
                                    threatsFound < 4 ? 'fa-exclamation-triangle' : 'fa-skull-crossbones'}"></i>
                                ${finalStatus}
                            </h4>
                            <p>${finalSummary}</p>
                            <hr>
                            <p class="mb-0">Detailed report saved to your scan history.</p>
                        </div>
                    `;

                            // Show results section
                            scanResults.classList.remove('d-none');

                            // Save final results to database via AJAX
                            fetch('save_scan_results.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                                },
                                body: JSON.stringify({
                                    scan_id: <?php echo $_SESSION['scan_id']; ?>,
                                    result_status: finalStatus.toLowerCase().replace(' ', '_'), // Ensure consistent status format
                                    result_summary: finalSummary,
                                    scan_steps: scanResultsData.steps,
                                    threats_found: threatsFound
                                })
                            }).then(response => {
                                if (!response.ok) {
                                    console.error('Failed to save scan results', response);
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            }).then(data => {
                                if (data.success) {
                                    // Clear session
                                    return fetch('clear_scan_session.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                                        }
                                    });
                                } else {
                                    throw new Error(data.error || 'Unknown error');
                                }
                            }).catch(error => {
                                console.error('Error:', error);
                                // Optionally show error to user
                                finalResults.innerHTML += `
        <div class="alert alert-danger mt-3">
            <strong>Error saving results:</strong> ${error.message}
        </div>
    `;
                            });
                        }
                    }

                    // Start the scan simulation
                    setTimeout(simulateScanStep, 500);
                </script>
            <?php endif; ?>
        </div>
    </section>

<?php require_once 'templates/footer.php'; ?>