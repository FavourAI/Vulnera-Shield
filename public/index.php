<?php include 'templates/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section bg-dark text-white py-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content animate__animated animate__fadeIn">
                <span class="badge bg-primary mb-3">ENTERPRISE-GRADE SECURITY</span>
                <h1 class="display-4 fw-bold mb-4">Advanced File Vulnerability Scanning</h1>
                <p class="lead mb-5">Protect your systems from malicious applications with our cutting-edge scanning technology. Upload any application and get instant security analysis.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="login.php" class="btn btn-primary btn-lg px-4 py-3 animate__animated animate__pulse animate__infinite animate__slow">Login to Scan a File</a>
                    <a href="#how-it-works" class="btn btn-outline-light btn-lg px-4 py-3">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block animate__animated animate__fadeIn animate__delay-1s">
                <img src="assets/security-illustration.svg" alt="Security Illustration" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- File Upload Section -->
<section id="upload" class="py-5 bg-light">
    <div class="container py-5">
        <div class="row justify-content-center animate__animated animate__fadeIn">
            <div class="col-lg-8">
                <div class="file-upload-container p-5 text-center bg-white rounded-3 shadow-sm">
                    <div class="file-upload-icon mb-4 animate__animated animate__bounceIn">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                    </div>
                    <h3 class="mb-3 fw-bold">Scan a File Now</h3>
                    <p class="text-muted mb-4">Drag & drop your file here or click to browse</p>
                    <button class="btn btn-primary px-4 py-2 mb-3">Select File</button>
                    <p class="small text-muted mt-3">Maximum file size: 100MB. Supported formats: APK, IPA, IPSW</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5">
    <div class="container py-5">
        <div class="text-center mb-5 animate__animated animate__fadeIn">
            <h2 class="fw-bold mb-3">Comprehensive Application Security</h2>
            <p class="lead text-muted">Our advanced scanning technology detects a wide range of vulnerabilities</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 animate__animated animate__fadeInUp">
                <div class="feature-card text-center p-4 h-100 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon mb-4">
                        <i class="fas fa-virus fa-2x text-danger"></i>
                    </div>
                    <h4 class="mb-3">Malware Detection</h4>
                    <p class="text-muted">Identify viruses, trojans, ransomware, and other malicious code embedded in your files with our multi-engine scanning.</p>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp animate__delay-1s">
                <div class="feature-card text-center p-4 h-100 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon mb-4">
                        <i class="fas fa-code fa-2x text-primary"></i>
                    </div>
                    <h4 class="mb-3">Code Analysis</h4>
                    <p class="text-muted">Deep inspection of scripts and executables to detect vulnerabilities, backdoors, and suspicious patterns.</p>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp animate__delay-2s">
                <div class="feature-card text-center p-4 h-100 bg-white rounded-3 shadow-sm">
                    <div class="feature-icon mb-4">
                        <i class="fas fa-file-contract fa-2x text-success"></i>
                    </div>
                    <h4 class="mb-3">Document Exploits</h4>
                    <p class="text-muted">Detect malicious macros, embedded objects, and other exploits in all kinds of mobile applications.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="bg-light py-5">
    <div class="container py-5">
        <div class="text-center mb-5 animate__animated animate__fadeIn">
            <h2 class="fw-bold mb-3">How Vulnera Scan Works</h2>
            <p class="lead text-muted">Simple steps to secure your applications</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 animate__animated animate__fadeInLeft">
                <div class="text-center p-4 h-100 bg-white rounded-3 shadow-sm">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4">1</div>
                    <h4 class="mb-3">Upload Your File</h4>
                    <p class="text-muted">Drag and drop or browse to select the file you want to scan. We support all common file types.</p>
                </div>
            </div>
            <div class="col-lg-4 animate__animated animate__fadeInUp">
                <div class="text-center p-4 h-100 bg-white rounded-3 shadow-sm">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4">2</div>
                    <h4 class="mb-3">Deep Analysis</h4>
                    <p class="text-muted">Our system performs static and dynamic analysis using multiple detection engines and AI algorithms.</p>
                </div>
            </div>
            <div class="col-lg-4 animate__animated animate__fadeInRight">
                <div class="text-center p-4 h-100 bg-white rounded-3 shadow-sm">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4">3</div>
                    <h4 class="mb-3">Get Results</h4>
                    <p class="text-muted">Receive a detailed report with identified vulnerabilities, risk assessment, and remediation advice.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>

<!-- Add Animate.css for animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<!-- Add Intersection Observer for scroll animations -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const animateElements = document.querySelectorAll('.animate__animated');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const animation = entry.target.getAttribute('data-animate');
                    entry.target.classList.add(animation);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });

        animateElements.forEach(element => {
            observer.observe(element);
        });
    });
</script>