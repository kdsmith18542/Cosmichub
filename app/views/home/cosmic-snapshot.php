<?php
// Set the title if not already set
if (!isset($title)) {
    $title = 'Your Cosmic Snapshot - ' . ($data['birth_date'] ?? 'Unknown Date');
}

// Extract data
$birthDate = $data['birth_date'] ?? '';
$westernZodiac = $data['western_zodiac'] ?? '';
$chineseZodiac = $data['chinese_zodiac'] ?? '';
$rarityScore = $data['rarity_score'] ?? 0;
$cosmicSignificance = $data['cosmic_significance'] ?? '';
$dayInHistory = $data['day_in_history'] ?? [];
$famousTwin = $data['famous_twin'] ?? null;
$slug = $data['slug'] ?? '';
?>

<div class="min-vh-100" style="background: linear-gradient(135deg, #581c87, #1e3a8a, #312e81);">
    <!-- Header -->
    <div class="border-bottom" style="background-color: rgba(0, 0, 0, 0.2); backdrop-filter: blur(8px); border-color: rgba(255, 255, 255, 0.1) !important;">
        <div class="container" style="max-width: 56rem;">
            <div class="d-flex align-items-center justify-content-between py-3">
                <h1 class="h4 fw-bold text-white mb-0">
                    <span style="background: linear-gradient(45deg, #fbbf24, #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        CosmicHub
                    </span>
                </h1>
                <a href="/" class="text-info text-decoration-none">
                    ← Try Another Date
                </a>
            </div>
        </div>
    </div>

    <div class="container py-4" style="max-width: 56rem;">
        <!-- Main Snapshot Card -->
        <div class="rounded-4 p-4 shadow-lg border mb-4" style="background-color: rgba(255, 255, 255, 0.1); backdrop-filter: blur(16px); border-color: rgba(255, 255, 255, 0.2) !important;">
            <div class="text-center mb-4">
                <h2 class="display-5 fw-bold text-white mb-2">
                    Your Cosmic Snapshot
                </h2>
                <p class="h5 text-light opacity-75">
                    Born on <?php echo htmlspecialchars($birthDate); ?>
                </p>
            </div>

            <!-- Free Content Grid -->
            <div class="row g-4 mb-4">
                <!-- Cosmic Identity -->
                <div class="col-md-6">
                    <div class="rounded-3 p-4 border" style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.3), rgba(37, 99, 235, 0.3)); border-color: rgba(255, 255, 255, 0.2) !important;">
                    <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 3rem; height: 3rem; background: linear-gradient(45deg, #fbbf24, #ec4899);">
                        <span class="fs-4">✨</span>
                    </div>
                    <h3 class="h5 fw-bold text-white">Cosmic Identity</h3>
                </div>
                <div class="vstack gap-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-light opacity-75">Western Zodiac:</span>
                        <span class="text-white fw-semibold"><?php echo htmlspecialchars($westernZodiac); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-light opacity-75">Chinese Zodiac:</span>
                        <span class="text-white fw-semibold"><?php echo htmlspecialchars($chineseZodiac); ?></span>
                    </div>
                </div>
                </div>

                <!-- Rarity Score -->
                <div class="rounded-3 p-4 border" style="background: linear-gradient(135deg, rgba(217, 119, 6, 0.3), rgba(234, 88, 12, 0.3)); border-color: rgba(255, 255, 255, 0.2) !important;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 3rem; height: 3rem; background: linear-gradient(45deg, #fbbf24, #f97316);">
                            <span class="fs-4">💎</span>
                        </div>
                        <h3 class="h5 fw-bold text-white">Rarity Score</h3>
                    </div>
                    <div class="text-center">
                        <div class="display-4 fw-bold text-white mb-2"><?php echo number_format($rarityScore, 1); ?>/10</div>
                        <div class="text-warning">
                            <?php 
                            if ($rarityScore >= 8) echo "Extremely Rare";
                            elseif ($rarityScore >= 6) echo "Very Rare";
                            elseif ($rarityScore >= 4) echo "Uncommon";
                            else echo "Common";
                            ?>
                        </div>
                        <p class="small text-light opacity-75 mt-2"><?php echo htmlspecialchars($cosmicSignificance); ?></p>
                    </div>
                </div>
            </div>

            <!-- Day in History -->
            <?php if (!empty($dayInHistory)): ?>
            <div class="rounded-3 p-4 border mb-4" style="background: linear-gradient(135deg, rgba(5, 150, 105, 0.3), rgba(20, 184, 166, 0.3)); border-color: rgba(255, 255, 255, 0.2) !important;">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 3rem; height: 3rem; background: linear-gradient(45deg, #34d399, #14b8a6);">
                        <span class="fs-4">📅</span>
                    </div>
                    <h3 class="h5 fw-bold text-white">Your Day in History</h3>
                </div>
                <div class="vstack gap-2">
                    <?php foreach (array_slice($dayInHistory, 0, 3) as $event): ?>
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle flex-shrink-0 mt-1 me-2" style="width: 0.5rem; height: 0.5rem; background-color: #34d399;"></div>
                        <div>
                            <span class="text-success fw-semibold"><?php echo htmlspecialchars($event['year']); ?>:</span>
                            <span class="text-white"><?php echo htmlspecialchars($event['event']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Famous Birthday Twin -->
            <?php if ($famousTwin): ?>
            <div class="rounded-3 p-4 border" style="background: linear-gradient(135deg, rgba(219, 39, 119, 0.3), rgba(147, 51, 234, 0.3)); border-color: rgba(255, 255, 255, 0.2) !important;">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 3rem; height: 3rem; background: linear-gradient(45deg, #f472b6, #a855f7);">
                        <span class="fs-4">🌟</span>
                    </div>
                    <h3 class="h5 fw-bold text-white">Your Famous Birthday Twin</h3>
                </div>
                <div class="d-flex align-items-center">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 fs-4" style="width: 4rem; height: 4rem; background: linear-gradient(45deg, #f472b6, #a855f7);">
                        👤
                    </div>
                    <div>
                        <div class="h5 fw-bold text-white"><?php echo htmlspecialchars($famousTwin['name']); ?></div>
                        <div class="text-light opacity-75"><?php echo htmlspecialchars($famousTwin['description'] ?? 'Celebrity'); ?></div>
                        <div class="small text-light opacity-50">Born: <?php echo htmlspecialchars($famousTwin['birth_date']); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Animated Shareable Section -->
        <div class="bg-gradient-to-r from-pink-500/20 to-purple-500/20 backdrop-blur-lg rounded-2xl p-8 shadow-2xl border border-pink-400/50 mb-8">
            <div class="text-center mb-4">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 4rem; height: 4rem; background: linear-gradient(45deg, #f472b6, #a855f7);">
                    <span class="fs-2">🎬</span>
                </div>
                <h3 class="h2 fw-bold text-white mb-2">
                    Create Your Animated Cosmic Shareable
                </h3>
                <p class="fs-5 text-light opacity-75">
                    Turn your cosmic snapshot into a stunning animated shareable for social media
                </p>
            </div>
            
            <div class="row g-4">
                <!-- Preview Area -->
                <div class="col-md-6">
                    <div class="rounded-3 p-3 border" style="background-color: rgba(0, 0, 0, 0.3); border-color: rgba(255, 255, 255, 0.2) !important;">
                        <div id="shareable-preview" class="text-center text-info py-5">
                            <i class="fas fa-magic display-4 mb-3"></i>
                            <p>Click "Generate" to create your animated shareable</p>
                        </div>
                    </div>
                </div>
                
                <!-- Controls -->
                <div class="col-md-6">
                    <div class="vstack gap-3">
                        <div class="rounded-3 p-3" style="background-color: rgba(255, 255, 255, 0.1);">
                            <h4 class="text-white fw-bold mb-2">✨ What you'll get:</h4>
                            <ul class="text-light opacity-75 small list-unstyled">
                                <li class="mb-1">• Animated zodiac symbols with cosmic glow</li>
                                <li class="mb-1">• Your rarity score with dynamic counter</li>
                                <li class="mb-1">• Beautiful cosmic background effects</li>
                                <li class="mb-1">• Perfect for Instagram, TikTok & Facebook</li>
                            </ul>
                        </div>
                    
                        <button id="generate-shareable-btn" onclick="generateCosmicShareablePreview()" 
                                class="btn btn-primary w-100 fw-bold py-3 px-4 rounded-3" style="background: linear-gradient(45deg, #ec4899, #9333ea); border: none;">
                            <i class="fas fa-sparkles me-2"></i>
                            Generate Animated Shareable
                        </button>
                        
                        <div id="shareable-actions" class="d-none vstack gap-2">
                            <button onclick="downloadPDF()" 
                                    class="btn btn-success w-100 fw-bold py-2 px-4 rounded-3" style="background: linear-gradient(45deg, #10b981, #0d9488); border: none;">
                                <i class="fas fa-download me-2"></i>
                                Download PDF (2 Credits)
                            </button>
                            
                            <button onclick="shareAnimatedContent()" 
                                    class="btn btn-success w-100 fw-bold py-2 px-4 rounded-3" style="background: linear-gradient(45deg, #10b981, #0d9488); border: none;">
                                <i class="fas fa-share me-2"></i>
                                Share on Social Media
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unlock Wall -->
        <div class="rounded-4 p-4 shadow-lg border-2 position-relative overflow-hidden" style="background: linear-gradient(45deg, rgba(234, 179, 8, 0.2), rgba(236, 72, 153, 0.2)); backdrop-filter: blur(16px); border-color: rgba(251, 191, 36, 0.5) !important;">
            <!-- Locked Content Preview -->
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.4), transparent); z-index: 10;"></div>
            
            <div class="position-relative" style="z-index: 20;">
                <div class="text-center mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 4rem; height: 4rem; background: linear-gradient(45deg, #fbbf24, #ec4899);">
                        <span class="fs-2">🔒</span>
                    </div>
                    <h3 class="h2 fw-bold text-white mb-2">
                        Unlock Your Full Cosmic Blueprint
                    </h3>
                    <p class="fs-5 text-warning opacity-75">
                        Discover the deeper mysteries of your cosmic identity
                    </p>
                </div>

                <!-- Preview of Locked Content -->
                <div class="row g-3 mb-4 opacity-50">
                    <div class="col-md-4">
                        <div class="rounded-3 p-3 border" style="background-color: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;">
                            <div class="text-center">
                                <span class="fs-4 mb-2 d-block">🎭</span>
                                <h4 class="fw-bold text-white mb-1 h6">Soul's Archetype</h4>
                                <p class="small text-light opacity-75">Your core personality blueprint</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-3 p-3 border" style="background-color: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;">
                            <div class="text-center">
                                <span class="fs-4 mb-2 d-block">🪐</span>
                                <h4 class="fw-bold text-white mb-1 h6">Planetary Influence</h4>
                                <p class="small text-light opacity-75">How celestial bodies shape you</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-3 p-3 border" style="background-color: rgba(255, 255, 255, 0.1); border-color: rgba(255, 255, 255, 0.2) !important;">
                            <div class="text-center">
                                <span class="fs-4 mb-2 d-block">🛤️</span>
                                <h4 class="fw-bold text-white mb-1 h6">Life Path Number</h4>
                                <p class="small text-light opacity-75">Your destined journey revealed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unlock Options -->
                <div class="row g-3">
                    <!-- Share to Unlock -->
                    <div class="col-md-6">
                        <div class="rounded-3 p-4 border" style="background: linear-gradient(45deg, rgba(37, 99, 235, 0.3), rgba(147, 51, 234, 0.3)); border-color: rgba(255, 255, 255, 0.2) !important;">
                            <h4 class="h5 fw-bold text-white mb-3 text-center">
                                🔓 Share to Unlock FREE
                            </h4>
                            <p class="text-light opacity-75 text-center mb-3">
                                Share your cosmic snapshot and unlock your full blueprint instantly
                            </p>
                            <button onclick="shareToUnlock()" class="btn btn-primary w-100 fw-bold py-2 px-4 rounded-3" style="background: linear-gradient(45deg, #3b82f6, #9333ea); border: none;">
                                Share & Unlock Now
                            </button>
                        </div>
                    </div>

                    <!-- Pay to Unlock -->
                    <div class="col-md-6">
                        <div class="rounded-3 p-4 border" style="background: linear-gradient(45deg, rgba(217, 119, 6, 0.3), rgba(234, 88, 12, 0.3)); border-color: rgba(255, 255, 255, 0.2) !important;">
                            <h4 class="h5 fw-bold text-white mb-3 text-center">
                                ⚡ Instant Unlock
                            </h4>
                            <p class="text-warning opacity-75 text-center mb-3">
                                Get immediate access to your full cosmic blueprint
                            </p>
                            <button onclick="payToUnlock()" class="btn btn-warning w-100 fw-bold py-2 px-4 rounded-3" style="background: linear-gradient(45deg, #eab308, #ea580c); border: none;">
                                Unlock for $2.99
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Social Proof -->
                <div class="text-center mt-4">
                    <p class="text-info small">
                        ✨ Over 50,000 cosmic blueprints unlocked this month
                    </p>
                </div>
            </div>
        </div>

        <!-- Share URL Display -->
        <div class="mt-4 text-center">
            <p class="text-info small mb-2">Share your cosmic snapshot:</p>
            <div class="rounded-3 p-2 border mx-auto" style="background-color: rgba(0, 0, 0, 0.3); border-color: rgba(255, 255, 255, 0.2) !important; max-width: 28rem;">
                <code class="text-warning small text-break">
                    <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/cosmic/' . $slug); ?>
                </code>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom animations */
@keyframes shimmer {
    0% {
        background-position: -200px 0;
    }
    100% {
        background-position: calc(200px + 100%) 0;
    }
}

.shimmer {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    background-size: 200px 100%;
    animation: shimmer 2s infinite;
}
</style>

<script src="/js/cosmic-shareables.js"></script>
<script>
let currentShareable = null;

async function generateCosmicShareablePreview() {
    const btn = document.getElementById('generate-shareable-btn');
    const preview = document.getElementById('shareable-preview');
    const actions = document.getElementById('shareable-actions');
    
    try {
        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
        preview.innerHTML = '<div class="text-center py-8"><div class="spinner-border text-pink-400 mb-3"></div><p class="text-purple-300">Creating your cosmic animation...</p></div>';
        
        // Generate the shareable
        currentShareable = await generateCosmicShareable('shareable-preview', '<?php echo $birthDate; ?>');
        
        // Show success state
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Generated!';
        actions.classList.remove('hidden');
        
        // Reset button after 2 seconds
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sparkles mr-2"></i>Generate New Animation';
        }, 2000);
        
    } catch (error) {
        console.error('Error generating shareable:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Try Again';
        preview.innerHTML = '<div class="text-center py-8 text-red-400"><i class="fas fa-exclamation-triangle text-4xl mb-4"></i><p>Failed to generate animation. Please try again.</p></div>';
    }
}

function downloadShareable() {
    if (currentShareable) {
        currentShareable.download('cosmic-snapshot-<?php echo date("Y-m-d", strtotime($birthDate)); ?>.png');
    } else {
        alert('Please generate the shareable first!');
    }
}

function shareAnimatedContent() {
    if (!currentShareable) {
        alert('Please generate the shareable first!');
        return;
    }
    
    const shareData = {
        title: 'Check out my Cosmic Snapshot!',
        text: 'I just discovered my cosmic identity with <?php echo $westernZodiac; ?> and <?php echo $chineseZodiac; ?> signs. My rarity score is <?php echo $rarityScore; ?>/10! Find out yours:',
        url: window.location.href
    };
    
    if (navigator.share) {
        navigator.share(shareData).catch(console.error);
    } else {
        // Fallback: copy to clipboard and show social share options
        navigator.clipboard.writeText(shareData.url).then(() => {
            const socialShareHtml = `
                <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onclick="this.remove()">
                    <div class="bg-white rounded-lg p-6 max-w-md mx-4" onclick="event.stopPropagation()">
                        <h3 class="text-lg font-bold mb-4">Share Your Cosmic Snapshot</h3>
                        <p class="text-gray-600 mb-4">Link copied to clipboard! Choose where to share:</p>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareData.url)}" target="_blank" class="bg-blue-600 text-white p-3 rounded text-center hover:bg-blue-700">
                                <i class="fab fa-facebook-f mr-2"></i>Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(shareData.text)}&url=${encodeURIComponent(shareData.url)}" target="_blank" class="bg-blue-400 text-white p-3 rounded text-center hover:bg-blue-500">
                                <i class="fab fa-twitter mr-2"></i>Twitter
                            </a>
                            <a href="https://wa.me/?text=${encodeURIComponent(shareData.text + ' ' + shareData.url)}" target="_blank" class="bg-green-600 text-white p-3 rounded text-center hover:bg-green-700">
                                <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                            </a>
                            <button onclick="this.closest('.fixed').remove()" class="bg-gray-600 text-white p-3 rounded hover:bg-gray-700">
                                <i class="fas fa-times mr-2"></i>Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', socialShareHtml);
        }).catch(() => {
            alert('Unable to copy link. Please copy the URL manually: ' + shareData.url);
        });
    }
}

function shareToUnlock() {
    if (navigator.share) {
        navigator.share({
            title: 'Check out my Cosmic Blueprint!',
            text: 'I just discovered my cosmic identity. Find out yours!',
            url: window.location.href
        }).then(() => {
            // Simulate unlock after sharing
            setTimeout(() => {
                window.location.href = '/cosmic-blueprint/<?php echo $slug; ?>';
            }, 1000);
        }).catch(console.error);
    } else {
        // Fallback: copy to clipboard and show social share options
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard! Share it on social media to unlock your full blueprint.');
            // In a real implementation, you'd show social share buttons here
            // For now, simulate unlock
            setTimeout(() => {
                window.location.href = '/cosmic-blueprint/<?php echo $slug; ?>';
            }, 2000);
        });
    }
}

function payToUnlock() {
    // In a real implementation, this would integrate with a payment processor
    alert('Payment integration coming soon! For now, try the share option.');
    // Simulate payment flow
    // window.location.href = '/payment/cosmic-blueprint/<?php echo $slug; ?>';
}

function downloadPDF() {
    // Check if user is logged in
    <?php if (!isset($_SESSION['user_id'])): ?>
        alert('Please log in to download PDF. PDF download costs 2 credits.');
        window.location.href = '/login';
        return;
    <?php endif; ?>
    
    // Redirect to PDF download
    window.location.href = '/cosmic-snapshot/<?php echo $slug; ?>/download-pdf';
}

// Add some visual effects
document.addEventListener('DOMContentLoaded', function() {
    // Add shimmer effect to locked content
    const lockedContent = document.querySelector('.opacity-60');
    if (lockedContent) {
        lockedContent.classList.add('shimmer');
    }
});
</script>