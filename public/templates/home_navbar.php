<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm" style="background: var(--primary-gradient);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/logo-bg-white-cropped.png" width="40" height="40" class="me-2" alt="Vulnera Shield Logo">
            <span class="fw-bold">Vulnera Shield</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mobile_scan.php"><i class="fas fa-mobile-alt me-1"></i> Mobile Scan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="file_types.php"><i class="fas fa-file-alt me-1"></i> File Types</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="vulnerabilities.php"><i class="fas fa-shield-alt me-1"></i> Vulnerabilities</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="scan_history.php"><i class="fas fa-history me-1"></i> Scan History</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link btn btn-outline-light btn-sm" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn btn-primary btn-sm text-white" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>