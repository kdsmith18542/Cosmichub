<?php 
// Set the content variable for the layout
$content = <<<HTML
<section class="hero">
    <div class="container">
        <h1>Welcome to CosmicHub</h1>
        <p class="lead">Your personal astrological report generator</p>
        
        <div class="cta-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/reports/create" class="btn btn-primary">Generate New Report</a>
                <a href="/reports" class="btn btn-secondary">View My Reports</a>
            <?php else: ?>
                <a href="/register" class="btn btn-primary">Get Started</a>
                <a href="/login" class="btn btn-secondary">Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <h2>Features</h2>
        <div class="feature-grid">
            <div class="feature">
                <h3>Personalized Reports</h3>
                <p>Get detailed astrological reports tailored specifically to your birth chart.</p>
            </div>
            <div class="feature">
                <h3>Multiple Formats</h3>
                <p>Download your reports in PDF or CSV format for easy sharing and reference.</p>
            </div>
            <div class="feature">
                <h3>Secure & Private</h3>
                <p>Your data is encrypted and stored securely. We respect your privacy.</p>
            </div>
        </div>
    </div>
</section>
HTML;

// Include the layout
extract(get_defined_vars());
?>
