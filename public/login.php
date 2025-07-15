

<?php
include 'templates\header.php';
session_start();

require_once '../config/db.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>



<!-- Login Section -->
<section class="auth-section py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="auth-card">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold">Welcome Back</h2>
                        <p class="text-muted">Sign in to your SecureScan Pro account</p>
                    </div>
                    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
                    <form id="loginForm" method="POST">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember me</label>
                            </div>
                            <a href="/forgot-password" class="text-decoration-none">Forgot password?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-3">Sign In</button>
                        <div class="text-center">
                            <p class="text-muted">Don't have an account? <a href="/register" class="text-decoration-none">Sign up</a></p>
                        </div>
                    </form>

                    <div class="auth-divider my-4">
                        <span>OR</span>
                    </div>

                    <div class="social-auth">
                        <button class="btn btn-outline-secondary w-100 mb-3">
                            <i class="fab fa-google me-2"></i> Sign in with Google
                        </button>
                        <button class="btn btn-outline-secondary w-100">
                            <i class="fab fa-microsoft me-2"></i> Sign in with Microsoft
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates\footer.php'; ?>