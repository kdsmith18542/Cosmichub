<?php
// Extract data from controller
$title = htmlspecialchars($viewData['title'] ?? 'Cosmic Blueprint');
$description = htmlspecialchars($viewData['meta_description'] ?? 'Check out this amazing cosmic blueprint!');
$shareUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/shareable/' . $viewData['shareable']->id;
$imageUrl = htmlspecialchars($viewData['og_image'] ?? '/shareables/' . $viewData['shareable']->id . '/preview.png');
$shareableData = $viewData['data'];
$shareable = $viewData['shareable'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | Cosmic Hub</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $description ?>">
    <meta name="keywords" content="astrology, cosmic, <?= strtolower($shareable['type']) ?>, zodiac, birth chart">
    <meta name="author" content="Cosmic Hub">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $shareUrl ?>">
    <meta property="og:title" content="<?= $title ?>">
    <meta property="og:description" content="<?= $description ?>">
    <meta property="og:image" content="<?= $imageUrl ?>">
    <meta property="og:image:width" content="800">
    <meta property="og:image:height" content="800">
    <meta property="og:site_name" content="Cosmic Hub">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $shareUrl ?>">
    <meta property="twitter:title" content="<?= $title ?>">
    <meta property="twitter:description" content="<?= $description ?>">
    <meta property="twitter:image" content="<?= $imageUrl ?>">
    
    <!-- Additional Meta Tags -->
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $shareUrl ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        
        .shareable-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .shareable-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .animation-container {
            background: #000;
            border-radius: 15px;
            margin: 20px 0;
            overflow: hidden;
            position: relative;
        }
        
        .share-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .share-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }
        
        .share-btn.facebook { background: #1877f2; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.instagram { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .share-btn.whatsapp { background: #25d366; }
        .share-btn.download { background: #6c757d; }
        .share-btn.copy { background: #17a2b8; }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .cta-section {
            text-align: center;
            margin-top: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }
        
        .cta-btn {
            background: white;
            color: #667eea;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        
        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="shareable-container">
        <div class="shareable-card">
            <div class="text-center mb-4">
                <h1 class="h2 mb-2"><?= $title ?></h1>
                <p class="text-muted"><?= $description ?></p>
            </div>
            
            <div class="animation-container">
                <div id="shareable-animation" class="loading">
                    <div class="spinner"></div>
                    <p>Loading your cosmic animation...</p>
                </div>
            </div>
            
            <div class="share-buttons">
                <a href="#" class="share-btn facebook" onclick="shareOnFacebook()">
                    <i class="fab fa-facebook-f"></i> Share on Facebook
                </a>
                <a href="#" class="share-btn twitter" onclick="shareOnTwitter()">
                    <i class="fab fa-twitter"></i> Share on Twitter
                </a>
                <a href="#" class="share-btn instagram" onclick="shareOnInstagram()">
                    <i class="fab fa-instagram"></i> Share on Instagram
                </a>
                <a href="#" class="share-btn whatsapp" onclick="shareOnWhatsApp()">
                    <i class="fab fa-whatsapp"></i> Share on WhatsApp
                </a>
                <a href="#" class="share-btn download" onclick="downloadShareable()">
                    <i class="fas fa-download"></i> Download
                </a>
                <a href="#" class="share-btn copy" onclick="copyLink()">
                    <i class="fas fa-link"></i> Copy Link
                </a>
            </div>
            
            <div class="stats">
                <div class="stat-item">
                    <i class="fas fa-eye"></i>
                    <span><?= number_format($shareable->view_count ?? 0) ?> views</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-download"></i>
                    <span><?= number_format($shareable->download_count ?? 0) ?> downloads</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-calendar"></i>
                    <span><?= date('M j, Y', strtotime($shareable->created_at)) ?></span>
                </div>
            </div>
        </div>
        
        <div class="cta-section">
            <h3>Create Your Own Cosmic Shareable</h3>
            <p>Discover your unique cosmic blueprint and share it with the world!</p>
            <a href="/" class="cta-btn">
                <i class="fas fa-stars"></i> Get Your Cosmic Report
            </a>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/cosmic-shareables.js"></script>
    
    <script>
        // Shareable data from PHP
        const shareableData = <?= json_encode($shareableData) ?>;
        const shareableId = '<?= $shareable->id ?>';
        const shareUrl = '<?= $shareUrl ?>';
        const title = '<?= addslashes($title) ?>';
        const description = '<?= addslashes($description) ?>';
        
        let cosmicShareable;
        
        // Initialize the animation when page loads
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                cosmicShareable = new CosmicShareables();
                
                // Generate the animation based on type
                let result;
                switch(shareableData.type) {
                    case 'cosmic':
                        result = await generateCosmicShareable('shareable-animation', shareableData.data.birth_date || '1990-01-01');
                        break;
                    case 'compatibility':
                        result = await generateCompatibilityShareable('shareable-animation', shareableData.data);
                        break;
                    case 'rarity':
                        result = await generateRarityShareable('shareable-animation', shareableData.data);
                        break;
                    default:
                        throw new Error('Unknown shareable type');
                }
                
                console.log('Shareable generated successfully');
            } catch (error) {
                console.error('Error loading shareable:', error);
                document.getElementById('shareable-animation').innerHTML = 
                    '<div class="text-center p-4"><i class="fas fa-exclamation-triangle text-warning"></i><p class="mt-2">Unable to load animation. Please try refreshing the page.</p></div>';
            }
        });
        
        // Social sharing functions
        function shareOnFacebook() {
            const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`;
            window.open(url, '_blank', 'width=600,height=400');
        }
        
        function shareOnTwitter() {
            const text = `${title} - ${description}`;
            const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(shareUrl)}`;
            window.open(url, '_blank', 'width=600,height=400');
        }
        
        function shareOnInstagram() {
            // Instagram doesn't support direct URL sharing, so we copy the link
            copyLink();
            alert('Link copied! You can now paste it in your Instagram story or bio.');
        }
        
        function shareOnWhatsApp() {
            const text = `${title} - ${description} ${shareUrl}`;
            const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        }
        
        function downloadShareable() {
            if (cosmicShareable) {
                cosmicShareable.download(`${title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.png`);
                
                // Track download
                fetch(`/shareables/<?= $shareable['id'] ?>/download`, { method: 'POST' })
                    .catch(err => console.log('Download tracking failed:', err));
            }
        }
        
        function copyLink() {
            navigator.clipboard.writeText(shareUrl).then(() => {
                // Show success message
                const btn = event.target.closest('.share-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy link:', err);
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = shareUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('Link copied to clipboard!');
            });
        }
        
        // Track view
        fetch(`/shareables/<?= $shareable['id'] ?>`, { method: 'POST' })
            .catch(err => console.log('View tracking failed:', err));
    </script>
</body>
</html>