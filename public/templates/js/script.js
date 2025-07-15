// Add loading indicators when forms are submitted
document.querySelectorAll('.app-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Scanning...
        `;

        // For demo purposes, we'll simulate file upload
        if (this.getAttribute('data-simulated') === 'true') {
            e.preventDefault();
            setTimeout(() => {
                window.location.href = 'scan-results.html';
            }, 1500);
        }
    });
});

// Add animation when page loads
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.device-card, .app-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 50));
    });
});

// In assets/js/script.js or in a <script> tag in apps.php
document.addEventListener('DOMContentLoaded', function() {
    // Handle file selection for all app cards
    document.querySelectorAll('.app-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Find the hidden file input
            const fileInput = this.closest('.app-form').querySelector('input[type="file"]');

            // Trigger file input click
            fileInput.click();
        });
    });

    // Handle file selection changes
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                // Show loading state
                const card = this.closest('.app-form').querySelector('.app-card');
                card.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex justify-content-center align-items-center" style="height: 120px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <span class="badge bg-primary">Preparing scan...</span>
                    </div>
                `;

                // Submit the form
                this.closest('form').submit();
            }
        });
    });
});