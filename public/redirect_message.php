<?php
require_once '../includes/auth.php';
include 'templates/header.php';
require_once '../config/db.php';

// Get message and data from query params or session fallback
$message = $_GET['message'] ?? $_SESSION['redirect_message'] ?? 'No message provided.';
$data = $_GET['data'] ?? $_SESSION['redirect_data'] ?? null;

// Store referer in session if not already set
if (!isset($_SESSION['redirect_back_url'])) {
    $_SESSION['redirect_back_url'] = $_SERVER['HTTP_REFERER'] ?? 'index.php';
}

// Optional: Clear after use (if one-time message)
unset($_SESSION['redirect_message']);
unset($_SESSION['redirect_data']);
?>


    <style>
        .message-box {
            margin-top: 80px;
            margin-bottom: 80px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            background-color: white;
        }
    </style>


<div class="container d-flex justify-content-center">
    <div class="message-box text-center w-100" style="max-width: 600px;">
        <h2 class="mb-3">🔔 Notification</h2>
        <p class="lead"><?= htmlspecialchars($message) ?></p>
        <?php if ($data): ?>
            <div class="alert alert-info mt-3"><?php echo $data ?></div>
        <?php endif; ?>
        <button class="btn btn-primary mt-4" onclick="goBack()"> Back</button>
    </div>
</div>

<script>
    function goBack() {
        const backUrl = "<?= $_SESSION['redirect_back_url'] ?? 'index.php' ?>";
        window.location.href = backUrl;
    }
</script>

<?php include 'templates/footer.php'; ?>

