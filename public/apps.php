<?php

require_once '../includes/auth.php';
include 'templates/header.php';
require_once '../config/db.php';

// Simulated device data
$device = [
    'android-123' => [
        'id' => 'android-123',
        'name' => 'Google Pixel 7',
        'type' => 'android'
    ],
    'ios-456' => [
        'id' => 'ios-456',
        'name' => 'iPhone 14 Pro',
        'type' => 'ios'
    ]
];

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get device ID from URL
$deviceId = $_GET['device_id'] ?? '';
if (!array_key_exists($deviceId, $device)) {
    header("Location: index.php");
    exit();
}

// Popular apps for each platform
$apps = [
    'android' => [
        ['name' => 'WhatsApp', 'package' => 'com.whatsapp', 'icon' => 'whatsapp.png'],
        ['name' => 'Facebook', 'package' => 'com.facebook.katana', 'icon' => 'facebook.png'],
        ['name' => 'Instagram', 'package' => 'com.instagram.android', 'icon' => 'instagram.png'],
        ['name' => 'Twitter', 'package' => 'com.twitter.android', 'icon' => 'twitter.png'],
        ['name' => 'TikTok', 'package' => 'com.zhiliaoapp.musically', 'icon' => 'tiktok.png'],
        ['name' => 'Gmail', 'package' => 'com.google.android.gm', 'icon' => 'gmail.png'],
        ['name' => 'Google Chrome', 'package' => 'com.android.chrome', 'icon' => 'chrome.png'],
        ['name' => 'YouTube', 'package' => 'com.google.android.youtube', 'icon' => 'youtube.png'],
        ['name' => 'Netflix', 'package' => 'com.netflix.mediaclient', 'icon' => 'netflix.png'],
        ['name' => 'Spotify', 'package' => 'com.spotify.music', 'icon' => 'spotify.png'],
        ['name' => 'Amazon Shopping', 'package' => 'com.amazon.mShop.android.shopping', 'icon' => 'amazon.png'],
        ['name' => 'LinkedIn', 'package' => 'com.linkedin.android', 'icon' => 'linkedin.png'],
        ['name' => 'Uber', 'package' => 'com.ubercab', 'icon' => 'uber.png'],
        ['name' => 'Zoom', 'package' => 'us.zoom.videomeetings', 'icon' => 'zoom.png'],
        ['name' => 'PayPal', 'package' => 'com.paypal.android.p2pmobile', 'icon' => 'paypal.png']
    ],
    'ios' => [
        ['name' => 'WhatsApp', 'package' => 'net.whatsapp.WhatsApp', 'icon' => 'whatsapp.png'],
        ['name' => 'Facebook', 'package' => 'com.facebook.Facebook', 'icon' => 'facebook.png'],
        ['name' => 'Instagram', 'package' => 'com.burbn.instagram', 'icon' => 'instagram.png'],
        ['name' => 'Twitter', 'package' => 'com.atebits.Tweetie2', 'icon' => 'twitter.png'],
        ['name' => 'TikTok', 'package' => 'com.zhiliaoapp.musically', 'icon' => 'tiktok.png'],
        ['name' => 'Mail', 'package' => 'com.apple.mobilemail', 'icon' => 'mail.png'],
        ['name' => 'Safari', 'package' => 'com.apple.mobilesafari', 'icon' => 'safari.png'],
        ['name' => 'YouTube', 'package' => 'com.google.ios.youtube', 'icon' => 'youtube.png'],
        ['name' => 'Netflix', 'package' => 'com.netflix.Netflix', 'icon' => 'netflix.png'],
        ['name' => 'Spotify', 'package' => 'com.spotify.client', 'icon' => 'spotify.png'],
        ['name' => 'Amazon Shopping', 'package' => 'com.amazon.Amazon', 'icon' => 'amazon.png'],
        ['name' => 'LinkedIn', 'package' => 'com.linkedin.LinkedIn', 'icon' => 'linkedin.png'],
        ['name' => 'Uber', 'package' => 'com.ubercab.UberClient', 'icon' => 'uber.png'],
        ['name' => 'Zoom', 'package' => 'us.zoom.videomeetings', 'icon' => 'zoom.png'],
        ['name' => 'PayPal', 'package' => 'com.paypal.ppclient', 'icon' => 'paypal.png']
    ]
];

$currentDevice = $device[$deviceId];
$deviceApps = $apps[$currentDevice['type']];
?>

<style>
    .loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease;
    }
    .progress {
        height: 10px;
        width: 80%;
        max-width: 500px;
    }
    .app-loading-info {
        margin-top: 20px;
        text-align: center;
        max-width: 500px;
    }
    .fade-out {
        opacity: 0;
        pointer-events: none;
    }
</style>
</head>
<body>
<!-- Loading Screen -->
<div class="loading-screen" id="loadingScreen">
    <div class="text-center mb-4">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h3 class="mt-3">Loading Applications</h3>
        <p class="text-muted">Preparing app list for scanning</p>
    </div>

    <div class="progress mb-3">
        <div id="loadingProgress" class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar" style="width: 0%"></div>
    </div>

    <div class="app-loading-info">
        <p id="currentAppInfo" class="mb-1"></p>
        <small id="currentPackageInfo" class="text-muted"></small>
    </div>
</div>
<div class="container py-4" id="mainContent" style="display: none;">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Devices
            </a>
            <h2 class="mb-0 text-center">
                    <span class="badge bg-<?= $currentDevice['type'] === 'android' ? 'success' : 'dark' ?> me-2">
                        <?= strtoupper($currentDevice['type']) ?>
                    </span>
                <?= $currentDevice['name'] ?>
            </h2>
            <div></div> <!-- Spacer for flex alignment -->
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="appSearch" class="form-control" placeholder="Search applications...">
                    <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4" id="appList">
            <?php foreach ($deviceApps as $app): ?>
                <div class="col">
                    <<button type="button"
                             class="card app-card h-100 w-100 text-center border-0 shadow-sm"
                             data-package="<?= $app['package'] ?>"
                             onclick="handleAppClick('<?= $app['package'] ?>', '<?= htmlspecialchars($app['name']) ?>', '<?= $currentDevice['type'] ?>')">
                        <div class="card-body">
                            <img src="assets/images/<?= $app['icon'] ?>" alt="<?= $app['name'] ?>" class="app-icon mb-3" width="60">
                            <h5 class="card-title mb-0"><?= $app['name'] ?></h5>
                            <small class="text-muted"><?= $app['package'] ?></small>
                        </div>
                        <div class="card-footer bg-transparent">
                            <span class="badge bg-primary">Scan for Vulnerabilities</span>
                        </div>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Note: This simulation will upload a dummy <?= $currentDevice['type'] ?> file regardless of which app you select.
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadingScreen = document.getElementById('loadingScreen');
        const mainContent = document.getElementById('mainContent');
        const progressBar = document.getElementById('loadingProgress');
        const appInfo = document.getElementById('currentAppInfo');
        const packageInfo = document.getElementById('currentPackageInfo');

        // Get all apps from PHP
        const apps = <?php echo json_encode($deviceApps); ?>;
        const totalApps = apps.length;
        const delayPerApp = 10000 / totalApps; // 10 seconds total

        let currentAppIndex = 0;

        function updateProgress() {
            if (currentAppIndex < totalApps) {
                const app = apps[currentAppIndex];
                const progress = ((currentAppIndex + 1) / totalApps) * 100;

                // Update progress bar
                progressBar.style.width = `${progress}%`;

                // Update app info
                appInfo.textContent = `Loading: ${app.name}`;
                packageInfo.textContent = app.package;

                currentAppIndex++;
                setTimeout(updateProgress, delayPerApp);
            } else {
                // Loading complete
                loadingScreen.classList.add('fade-out');
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                    mainContent.style.display = 'block';
                }, 500);
            }
        }

        // Start the loading process
        setTimeout(updateProgress, 500);
    });


    // App search functionality
    document.getElementById('appSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const appCards = document.querySelectorAll('.app-card');

        appCards.forEach(card => {
            const appName = card.querySelector('.card-title').textContent.toLowerCase();
            const appPackage = card.querySelector('.text-muted').textContent.toLowerCase();
            const matches = appName.includes(searchTerm) || appPackage.includes(searchTerm);
            card.closest('.col').style.display = matches ? 'block' : 'none';
        });
    });

    document.getElementById('clearSearch').addEventListener('click', function() {
        document.getElementById('appSearch').value = '';
        document.querySelectorAll('.app-card').forEach(card => {
            card.closest('.col').style.display = 'block';
        });
    });

    function handleAppClick(packageName, appName, deviceType) {
        // Create a hidden form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'upload.php';
        form.enctype = 'multipart/form-data';
        form.style.display = 'none';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $_SESSION['csrf_token'] ?>';
        form.appendChild(csrfInput);

        // Add device info
        const deviceIdInput = document.createElement('input');
        deviceIdInput.type = 'hidden';
        deviceIdInput.name = 'device_id';
        deviceIdInput.value = '<?= $currentDevice['id'] ?>';
        form.appendChild(deviceIdInput);

        const deviceTypeInput = document.createElement('input');
        deviceTypeInput.type = 'hidden';
        deviceTypeInput.name = 'device_type';
        deviceTypeInput.value = deviceType;
        form.appendChild(deviceTypeInput);

        // Add app info
        const appNameInput = document.createElement('input');
        appNameInput.type = 'hidden';
        appNameInput.name = 'app_name';
        appNameInput.value = appName;
        form.appendChild(appNameInput);

        const appPackageInput = document.createElement('input');
        appPackageInput.type = 'hidden';
        appPackageInput.name = 'app_package';
        appPackageInput.value = packageName;
        form.appendChild(appPackageInput);

        // Create a file input with the dummy file
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.name = 'file';

        // Create a dummy file name based on the app name and device type
        const fileExtension = deviceType === 'android' ? 'apk' : 'ipa';
        const sanitizedAppName = appName.toLowerCase().replace(/[^a-z0-9]/g, '-');
        const dummyFileName = `${sanitizedAppName}.${fileExtension}`;

        // Create a dummy file object
        const file = new File([''], dummyFileName, { type: 'application/octet-stream' });

        // Create a DataTransfer object to simulate file selection
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
        form.appendChild(fileInput);

        // Add form to body and submit
        document.body.appendChild(form);
        form.submit();
    }
</script>
