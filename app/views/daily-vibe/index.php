<?php $this->extend('layouts/main'); ?>

<!-- SEO Meta Tags -->
<meta name="description" content="Get your personalized daily cosmic vibe and astrological insights. Discover what the stars have in store for you today with our daily cosmic guidance and spiritual wisdom.">
<meta name="keywords" content="daily horoscope, cosmic vibe, daily astrology, spiritual guidance, daily insights, cosmic wisdom, astrology today, daily cosmic reading">
<meta property="og:title" content="Daily Cosmic Vibe - Your Personal Astrological Guidance | CosmicHub">
<meta property="og:description" content="Receive your personalized daily cosmic vibe and astrological insights. Start each day with cosmic wisdom and spiritual guidance.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/daily-vibe">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Daily Cosmic Vibe - Personal Astrology">
<meta name="twitter:description" content="Get your personalized daily cosmic vibe and astrological insights from the stars.">

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Daily Cosmic Vibe",
  "description": "Personalized daily cosmic insights and astrological guidance",
  "url": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/daily-vibe",
  "mainEntity": {
    "@type": "Service",
    "name": "Daily Cosmic Vibe",
    "description": "Personalized daily astrological insights and cosmic guidance",
    "provider": {
      "@type": "Organization",
      "name": "CosmicHub"
    },
    "serviceType": "Astrological Reading",
    "audience": {
      "@type": "Audience",
      "audienceType": "People interested in astrology and spiritual guidance"
    }
  },
  "datePublished": "<?= date('c') ?>",
  "dateModified": "<?= date('c') ?>"
}
</script>

<?php $this->section('content'); ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card cosmic-card">
                <div class="card-header bg-stellar text-white">
                    <h2 class="h4 mb-0">Your Daily Cosmic Vibe</h2>
                    <p class="mb-0 text-light"><?= date('l, F j, Y') ?></p>
                    <?php if (isset($streakCount) && $streakCount > 1): ?>
                        <div class="mt-2">
                            <span class="badge bg-success">🔥 <?= $streakCount ?>-day streak!</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body text-center py-5">
                    <?php if ($todaysVibe) : ?>
                        <div class="vibe-display">
                            <div class="vibe-emoji display-1 mb-4">
                                <?= $this->getRandomEmoji() ?>
                            </div>
                            <div class="vibe-text lead mb-4 px-3">
                                <?= nl2br(htmlspecialchars($todaysVibe->vibe_text)) ?>
                            </div>
                            <div class="text-muted small">
                                Check back tomorrow for a new vibe!
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="vibe-prompt">
                            <div class="vibe-emoji display-1 mb-4">
                                <?= $this->getRandomEmoji() ?>
                            </div>
                            <h3 class="h4 mb-4">Ready for your daily cosmic insight?</h3>
                            <p class="text-muted mb-4">
                                Get your personalized cosmic vibe for today. Each day brings a new message from the stars.
                            </p>
                            <button id="generateVibe" class="btn btn-primary btn-lg px-5">
                                Generate My Vibe
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($vibeHistory)) : ?>
                <div class="card cosmic-card mt-4">
                    <div class="card-header bg-stellar text-white">
                        <h3 class="h5 mb-0">Recent Vibes</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($vibeHistory as $vibe) : ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= date('M j, Y', strtotime($vibe->date)) ?></h6>
                                    <small class="text-muted"><?= $this->getRandomEmoji() ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($vibe->vibe_text) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="/daily-vibe/history" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Vibe Generated Modal -->
<div class="modal fade" id="vibeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cosmic-card">
            <div class="modal-header bg-stellar text-white">
                <h5 class="modal-title">Your Cosmic Vibe is Ready</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="vibe-emoji display-1 mb-4">
                    <span id="vibeEmoji">✨</span>
                </div>
                <div id="vibeContent" class="lead mb-4"></div>
                <div class="text-muted small mb-3">
                    Share your cosmic vibe with friends!
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="shareOnFacebook()">
                        <i class="fab fa-facebook-f me-1"></i> Share
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="copyVibeLink()">
                        <i class="fas fa-link me-1"></i> Copy Link
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
$(document).ready(function() {
    // Generate vibe button click handler
    $('#generateVibe').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating...');
        
        // Show loading state
        $('.vibe-prompt').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">Consulting the cosmos...</p>
            </div>
        `);
        
        // AJAX request to generate vibe
        $.ajax({
            url: '/daily-vibe/generate',
            method: 'POST',
            data: {
                csrf_token: '<?= $_SESSION['csrf_token'] ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the vibe display
                    $('.vibe-prompt').html(`
                        <div class="vibe-display">
                            <div class="vibe-emoji display-1 mb-4">
                                ${getRandomEmoji()}
                            </div>
                            <div class="vibe-text lead mb-4 px-3">
                                ${response.vibe.vibe_text.replace(/\n/g, '<br>')}
                            </div>
                            <div class="text-muted small">
                                Check back tomorrow for a new vibe!
                            </div>
                        </div>
                    `);
                    
                    // Show modal with share options
                    $('#vibeEmoji').text(getRandomEmoji());
                    $('#vibeContent').html(response.vibe.vibe_text.replace(/\n/g, '<br>'));
                    const vibeModal = new bootstrap.Modal(document.getElementById('vibeModal'));
                    vibeModal.show();
                    
                    // Reload the page after a short delay to show the new vibe in the history
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Show error
                    $('.vibe-prompt').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.error || 'Failed to generate your daily vibe. Please try again.'}
                        </div>
                        <button id="retryVibe" class="btn btn-primary mt-3">
                            Try Again
                        </button>
                    `);
                    
                    // Add retry handler
                    $('#retryVibe').on('click', function() {
                        window.location.reload();
                    });
                }
            },
            error: function() {
                $('.vibe-prompt').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        An error occurred. Please check your connection and try again.
                    </div>
                    <button id="retryVibe" class="btn btn-primary mt-3">
                        Try Again
                    </button>
                `);
                
                // Add retry handler
                $('#retryVibe').on('click', function() {
                    window.location.reload();
                });
            }
        });
    });
    
    // Share on Facebook
    window.shareOnFacebook = function() {
        const text = $('#vibeContent').text();
        const url = encodeURIComponent(window.location.href);
        const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${encodeURIComponent(text)}`;
        window.open(shareUrl, '_blank', 'width=600,height=400');
    };
    
    // Copy vibe link
    window.copyVibeLink = function() {
        const text = $('#vibeContent').text();
        navigator.clipboard.writeText(text).then(() => {
            alert('Vibe copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    };
    
    // Helper function to get a random emoji
    function getRandomEmoji() {
        const emojis = ['✨', '🌙', '⭐', '🌟', '💫', '🔮', '🌌', '🌠', '☄️', '🌕', '🌖', '🌗', '🌘', '🌑', '🌒', '🌓', '🌔'];
        return emojis[Math.floor(Math.random() * emojis.length)];
    }
});
</script>
<?php $this->endSection(); ?>
