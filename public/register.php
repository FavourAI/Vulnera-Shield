<?php
require_once '../config/db.php';
include 'templates/header.php';
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check for existing user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$email, $hash])) {
                $success = "Registration successful. <a href='login.php'>Login here</a>.";
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>
    <div class="register-container">
        <h2>Create Account</h2>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="your@email.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" placeholder="Create password" required>
                <div id="passwordRequirements" class="requirements">
                    <small>Password must contain:</small>
                    <ul>
                        <li id="req-upper">1 uppercase letter</li>
                        <li id="req-lower">1 lowercase letter</li>
                        <li id="req-number">1 number</li>
                        <li id="req-length">8+ characters</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
                <div id="passwordMatch" class="match-status"></div>
            </div>

            <div class="form-group terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the terms and conditions</label>
            </div>

            <button type="submit">Register</button>
        </form>

        <p class="login-link">Already have an account? <a href="login.php">Log in</a></p>
    </div>

    <style>
        .register-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            border-left: 3px solid #c62828;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 3px solid #2e7d32;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .requirements {
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.85rem;
        }

        .requirements ul {
            margin: 0.25rem 0 0 1rem;
        }

        .requirements li {
            list-style-type: disc;
            color: #d32f2f;
        }

        .requirements li.valid {
            color: #388e3c;
        }

        .match-status {
            margin-top: 0.25rem;
            font-size: 0.85rem;
            color: #d32f2f;
        }

        .match-status.valid {
            color: #388e3c;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: #1976d2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #1565c0;
        }

        .terms {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
        }

        .terms input {
            margin-right: 0.5rem;
        }

        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const requirements = {
                upper: document.getElementById('req-upper'),
                lower: document.getElementById('req-lower'),
                number: document.getElementById('req-number'),
                length: document.getElementById('req-length')
            };
            const matchStatus = document.getElementById('passwordMatch');

            function checkPassword() {
                const password = passwordInput.value;

                // Check requirements
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /\d/.test(password);
                const hasLength = password.length >= 8;

                // Update requirement indicators
                requirements.upper.classList.toggle('valid', hasUpper);
                requirements.lower.classList.toggle('valid', hasLower);
                requirements.number.classList.toggle('valid', hasNumber);
                requirements.length.classList.toggle('valid', hasLength);

                // Check password match if confirm field has value
                if (confirmInput.value) {
                    checkPasswordMatch();
                }
            }

            function checkPasswordMatch() {
                if (passwordInput.value === confirmInput.value) {
                    matchStatus.textContent = 'Passwords match';
                    matchStatus.className = 'match-status valid';
                } else {
                    matchStatus.textContent = 'Passwords do not match';
                    matchStatus.className = 'match-status';
                }
            }

            passwordInput.addEventListener('input', checkPassword);
            confirmInput.addEventListener('input', checkPasswordMatch);
        });
    </script>
<?php
include 'templates/footer.php';