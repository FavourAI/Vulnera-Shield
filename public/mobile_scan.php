<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once 'templates/header.php';

// Simulate device detection with delay
sleep(2); // Simulate device detection delay
$devices = [
    [
        'id' => 'android-123',
        'name' => 'Google Pixel 7',
        'type' => 'android',
        'os_version' => 'Android 13',
        'icon' => 'android.png',
        'battery' => 78,
        'storage' => '64GB available'
    ],
    [
        'id' => 'ios-456',
        'name' => 'iPhone 14 Pro',
        'type' => 'ios',
        'os_version' => 'iOS 16',
        'icon' => 'ios.png',
        'battery' => 92,
        'storage' => '128GB available'
    ]
];
?>

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --dark-color: #2c3e50   ;
        }

        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: all 0.5s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .spinner {
            width: 4rem;
            height: 4rem;
            border-width: 0.25rem;
        }

        .device-icon {
            height: 100px;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
            transition: transform 0.3s ease;
        }

        .device-card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .device-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }

        .device-card .card-body {
            padding: 2rem;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            width: 100%;
            max-width: 400px;
        }

        .progress-bar {
            background-color: var(--secondary-color);
        }

        .connection-steps {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            max-width: 500px;
            margin: 0 auto;
        }

        .connection-steps li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }

        .connection-steps li i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .device-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
        }

        .device-meta {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .device-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .device-meta-item i {
            margin-right: 0.25rem;
        }

        .page-title {
            position: relative;
            display: inline-block;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
    </style>

    <!-- Step 1: Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="row">
            <img class="mt-4 mb-4" src="assets/img.png" height="200px">
        </div>
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3 class="mt-4 mb-3 fw-bold">Initializing Device Scanner</h3>
            <div class="connection-steps">
                <p class="text-muted mb-3">Please complete these steps:</p>
                <ul class="list-unstyled">
                    <li><i class="bi bi-usb-plug"></i> Connect your device via USB cable</li>
                    <li><i class="bi bi-phone"></i> Enable USB debugging in developer options</li>
                    <li><i class="bi bi-shield-check"></i> Authorize this computer when prompted</li>
                    <li><i class="bi bi-hourglass"></i> Wait for device detection to complete</li>
                </ul>
            </div>
            <div class="progress mt-4">
                <div id="connectionProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            <p class="text-muted mt-3">This may take a few moments...</p>
        </div>
    </div>

    <!-- Step 2: Device Selection (initially hidden) -->
    <div id="deviceSelection" class="container py-5" style="display: none;">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-dark mb-3 page-title">Mobile Security Scan</h1>
            <p class="lead text-muted">Connected devices ready for scanning</p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Dashboard
            </a>
            <div class="text-end">
                <button class="btn btn-outline-primary" id="refreshDevices">
                    <i class="bi bi-arrow-clockwise me-2"></i> Refresh
                </button>
            </div>
        </div>

        <div class="row justify-content-center g-4">
            <?php foreach ($devices as $index => $device): ?>
                <div class="col-md-6 col-lg-4 fade-in" style="animation-delay: <?= $index * 0.1 ?>s">
                    <div class="card device-card h-100">
                        <div class="card-body text-center">
                            <img src="assets/images/<?= $device['icon'] ?>" alt="<?= $device['type'] ?>" class="device-icon mb-3">
                            <h3 class="h4 mb-1 fw-bold"><?= $device['name'] ?></h3>
                            <p class="text-muted mb-2"><?= $device['os_version'] ?></p>

                            <div class="device-meta">
                            <span class="device-meta-item">
                                <i class="bi bi-battery-half"></i> <?= $device['battery'] ?>%
                            </span>
                                <span class="device-meta-item">
                                <i class="bi bi-device-hdd"></i> <?= $device['storage'] ?>
                            </span>
                            </div>

                            <div class="mt-3 mb-3">
                            <span class="badge device-badge bg-<?= $device['type'] === 'android' ? 'success' : 'dark' ?>">
                                <i class="bi bi-<?= $device['type'] === 'android' ? 'android2' : 'apple' ?> me-1"></i>
                                <?= strtoupper($device['type']) ?>
                            </span>
                            </div>

                            <a href="apps.php?device_id=<?= $device['id'] ?>" class="btn btn-primary px-4 py-2 stretched-link">
                                <i class="bi bi-shield-check me-2"></i> Scan Device
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5 pt-3">
            <div class="alert alert-light border">
                <i class="bi bi-info-circle-fill text-primary me-2"></i>
                Need assistance with device connection?
                <a href="#" class="fw-bold text-decoration-none">View our connection guide</a>
            </div>
        </div>
    </div>

    <script>
        // Simulate device detection process
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            const deviceSelection = document.getElementById('deviceSelection');
            const progressBar = document.getElementById('connectionProgress');
            const refreshBtn = document.getElementById('refreshDevices');

            // Simulate progress with more realistic timing
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 100) progress = 100;
                progressBar.style.width = progress + '%';

                if (progress >= 100) {
                    clearInterval(progressInterval);
                    // Animate out loading screen
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                        // Show device selection with animation
                        deviceSelection.style.display = 'block';
                    }, 800);
                }
            }, 600);

            // Refresh button functionality
            refreshBtn.addEventListener('click', function() {
                window.location.reload();
            });

            // In a real implementation, you would have actual device detection logic here
            // This could be via AJAX polling or WebSockets
        });
    </script>

<?php require_once 'templates/footer.php'; ?>