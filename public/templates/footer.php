</main>
<footer class="py-4 mt-4" style="background: var(--footer-gradient);">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3">Vulnera Shield</h5>
                <p class="text-muted">Comprehensive APK vulnerability scanning for enhanced mobile security.</p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
                    <li><a href="about.php" class="text-decoration-none text-muted">About</a></li>
                    <li><a href="contact.php" class="text-decoration-none text-muted">Contact</a></li>
                    <li><a href="privacy.php" class="text-decoration-none text-muted">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="mb-3">Connect</h5>
                <div class="d-flex">
                    <a href="#" class="text-decoration-none text-muted me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-decoration-none text-muted me-3"><i class="fab fa-github fa-lg"></i></a>
                    <a href="#" class="text-decoration-none text-muted me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" class="text-decoration-none text-muted"><i class="fas fa-envelope fa-lg"></i></a>
                </div>
            </div>
        </div>
        <hr class="my-4 bg-secondary">
        <div class="text-center text-muted">
            <small>&copy; <?php echo date('Y'); ?> Vulnera Shield. All rights reserved.</small>
        </div>
    </div>
</footer>
<script>
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        const scrollPosition = window.scrollY;

        if (scrollPosition > 50) {
            navbar.style.background = 'linear-gradient(135deg, #2a404f 40%, #3496d7 100%)';
        } else {
            navbar.style.background = 'var(--primary-gradient)';
        }
    });
</script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>