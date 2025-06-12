<?php
// Ensure user is authenticated
if (!auth_check()) {
    redirect('/login');
    exit;
}

$user = auth_user();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Cosmic Connection - Compatibility Report') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .cosmic-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .compatibility-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .zodiac-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .compatibility-score {
            font-size: 4rem;
            font-weight: bold;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .heart-animation {
            animation: heartbeat 1.5s ease-in-out infinite;
        }
        @keyframes heartbeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .loading-spinner {
            display: none;
        }
        .result-section {
            display: none;
        }
        .credit-info {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .element-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            margin: 5px;
        }
        .element-fire { background: linear-gradient(45deg, #ff6b6b, #ff8e53); color: white; }
        .element-earth { background: linear-gradient(45deg, #8bc34a, #4caf50); color: white; }
        .element-air { background: linear-gradient(45deg, #03a9f4, #00bcd4); color: white; }
        .element-water { background: linear-gradient(45deg, #3f51b5, #2196f3); color: white; }
    </style>
</head>
<body class="cosmic-bg">
    <?php include __DIR__ . '/../layouts/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="compatibility-card p-5">
                    <div class="text-center mb-4">
                        <h1 class="display-4 mb-3">
                            <i class="fas fa-heart heart-animation text-danger"></i>
                            Cosmic Connection
                        </h1>
                        <p class="lead text-muted">Discover the cosmic compatibility between two souls</p>
                        
                        <div class="credit-info">
                            <i class="fas fa-coins"></i>
                            <strong>Cost: 2 Credits</strong> | 
                            Your Credits: <span class="badge bg-primary"><?= $user->credits ?></span>
                        </div>
                    </div>
                    
                    <!-- Compatibility Form -->
                    <form id="compatibilityForm" class="form-section">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary mb-4">
                                    <div class="card-header bg-primary text-white text-center">
                                        <h5><i class="fas fa-user"></i> Person 1</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="person1_name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="person1_name" name="person1_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="person1_birth_date" class="form-label">Birth Date</label>
                                            <input type="date" class="form-control" id="person1_birth_date" name="person1_birth_date" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card border-success mb-4">
                                    <div class="card-header bg-success text-white text-center">
                                        <h5><i class="fas fa-user"></i> Person 2</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="person2_name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="person2_name" name="person2_name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="person2_birth_date" class="form-label">Birth Date</label>
                                            <input type="date" class="form-control" id="person2_birth_date" name="person2_birth_date" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-lg btn-gradient px-5 py-3" style="background: linear-gradient(45deg, #667eea, #764ba2); border: none; color: white; border-radius: 50px;">
                                <i class="fas fa-heart"></i> Generate Compatibility Report
                            </button>
                        </div>
                    </form>
                    
                    <!-- Loading Spinner -->
                    <div class="loading-spinner text-center">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">The cosmos is aligning your destinies...</p>
                    </div>
                    
                    <!-- Results Section -->
                    <div class="result-section">
                        <div class="text-center mb-4">
                            <div class="compatibility-score" id="compatibilityScore">0%</div>
                            <h3 id="compatibilityLevel" class="text-muted">Compatibility Level</h3>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 text-center">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <div class="zodiac-icon" id="person1Zodiac">♈</div>
                                        <h5 id="person1Name">Person 1</h5>
                                        <span class="element-badge" id="person1Element">Element</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-center">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <div class="zodiac-icon" id="person2Zodiac">♈</div>
                                        <h5 id="person2Name">Person 2</h5>
                                        <span class="element-badge" id="person2Element">Element</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-star"></i> Cosmic Analysis</h5>
                                    </div>
                                    <div class="card-body">
                                        <p id="compatibilityAnalysis" class="lead"></p>
                                        
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-plus-circle text-success"></i> Strengths</h6>
                                                <ul id="strengthsList" class="list-unstyled"></ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-exclamation-triangle text-warning"></i> Challenges</h6>
                                                <ul id="challengesList" class="list-unstyled"></ul>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                            
                            <!-- Share to Unlock Section -->
                            <div class="card mt-4" id="referralSection">
                                <div class="card-header">
                                    <h5><i class="fas fa-lock"></i> Premium Analysis</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($hasActiveSubscription)): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-star"></i> As a premium subscriber, you have instant access to the full premium analysis!
                                        </div>
                                        <div id="premiumAnalysis">
                                            <?php if (!empty($premiumCompatibilityContent)): ?>
                                                <div class="card border-primary">
                                                    <div class="card-header bg-primary text-white">
                                                        <h5 class="mb-0"><i class="fas fa-heart me-2"></i>Premium Compatibility Insights</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="premium-content">
                                                            <?= nl2br(htmlspecialchars($premiumCompatibilityContent)) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center text-muted p-4">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>Generating your premium compatibility insights...
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($hasEnoughReferrals ?? false): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> Premium content unlocked!
                                        </div>
                                        <div id="premiumAnalysis">
                                            <!-- Premium content will be loaded here -->
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <h5><i class="fas fa-lock"></i> Share to Unlock Detailed Analysis</h5>
                                            <p>Share your compatibility report with 3 friends to unlock the full premium analysis, or <a href="/upgrade" class="alert-link">upgrade to premium</a> for instant access!</p>
                                            <div class="progress mb-3">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                    role="progressbar" 
                                                    style="width: <?= min(100, (($successfulReferrals ?? 0) / 3) * 100) ?>%" 
                                                    aria-valuenow="<?= $successfulReferrals ?? 0 ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="3">
                                                </div>
                                            </div>
                                            <p>Referrals completed: <strong><?= $successfulReferrals ?? 0 ?></strong> of 3</p>
                                            <div class="input-group mb-3">
                                                <input type="text" class="form-control" id="referralLink" 
                                                    value="<?= $referralUrl ?? '' ?>" readonly>
                                                <button class="btn btn-primary" type="button" onclick="copyReferralLink()">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                            <button class="btn btn-gradient btn-lg w-100" onclick="shareCompatibility()" style="background: linear-gradient(45deg, #667eea, #764ba2); border: none;">
                                                <i class="fas fa-share-alt"></i> Share My Compatibility
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- JavaScript for Sharing -->
                            <script>
                                function shareCompatibility() {
                                    fetch('/compatibility/share-link')
                                        .then(response => response.json())
                                        .then(data => {
                                            if (navigator.share) {
                                                navigator.share({
                                                    title: 'Check out our cosmic compatibility!',
                                                    text: 'Discover our cosmic connection score!',
                                                    url: data.url
                                                }).catch(console.error);
                                            } else {
                                                alert('Share this link: ' + data.url);
                                            }
                                        })
                                        .catch(console.error);
                                }
                                
                                function copyReferralLink() {
                                    const copyText = document.getElementById("referralLink");
                                    copyText.select();
                                    copyText.setSelectionRange(0, 99999);
                                    document.execCommand("copy");
                                    
                                    const copyBtn = document.querySelector("button[onclick='copyReferralLink()']");
                                    const originalHtml = copyBtn.innerHTML;
                                    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                                    
                                    setTimeout(() => {
                                        copyBtn.innerHTML = originalHtml;
                                    }, 2000);
                                }
                            </script>
                                            <h6><i class="fas fa-lightbulb text-info"></i> Cosmic Advice</h6>
                                            <p id="compatibilityAdvice" class="text-muted"></p>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <h6><i class="fas fa-atom text-primary"></i> Element Compatibility</h6>
                                            <p id="elementCompatibility" class="text-muted"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button class="btn btn-outline-primary" onclick="resetForm()">
                                <i class="fas fa-redo"></i> Generate Another Report
                            </button>
                            <button class="btn btn-success" onclick="shareReport()">
                                <i class="fas fa-share"></i> Share This Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../layouts/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Zodiac symbols mapping
        const zodiacSymbols = {
            'Aries': '♈', 'Taurus': '♉', 'Gemini': '♊', 'Cancer': '♋',
            'Leo': '♌', 'Virgo': '♍', 'Libra': '♎', 'Scorpio': '♏',
            'Sagittarius': '♐', 'Capricorn': '♑', 'Aquarius': '♒', 'Pisces': '♓'
        };
        
        document.getElementById('compatibilityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if user has enough credits
            const currentCredits = <?= $user->credits ?>;
            if (currentCredits < 2) {
                alert('You need 2 credits to generate a compatibility report. Please purchase more credits.');
                window.location.href = '/credits/purchase';
                return;
            }
            
            // Show loading
            document.querySelector('.form-section').style.display = 'none';
            document.querySelector('.loading-spinner').style.display = 'block';
            
            // Prepare form data
            const formData = new FormData(this);
            
            // Send request
            fetch('/compatibility/generate', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResults(data.compatibility);
                    updateCreditsDisplay(data.remaining_credits);
                } else {
                    alert(data.error || 'Failed to generate compatibility report');
                    resetForm();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                resetForm();
            });
        });
        
        function displayResults(compatibility) {
            // Hide loading
            document.querySelector('.loading-spinner').style.display = 'none';
            
            // Update score and level
            document.getElementById('compatibilityScore').textContent = compatibility.compatibility_score + '%';
            document.getElementById('compatibilityLevel').textContent = compatibility.compatibility_level + ' Compatibility';
            
            // Update person 1 info
            document.getElementById('person1Name').textContent = compatibility.person1.name;
            document.getElementById('person1Zodiac').textContent = zodiacSymbols[compatibility.person1.zodiac_sign] || '⭐';
            document.getElementById('person1Element').textContent = compatibility.person1.element;
            document.getElementById('person1Element').className = 'element-badge element-' + compatibility.person1.element.toLowerCase();
            
            // Update person 2 info
            document.getElementById('person2Name').textContent = compatibility.person2.name;
            document.getElementById('person2Zodiac').textContent = zodiacSymbols[compatibility.person2.zodiac_sign] || '⭐';
            document.getElementById('person2Element').textContent = compatibility.person2.element;
            document.getElementById('person2Element').className = 'element-badge element-' + compatibility.person2.element.toLowerCase();
            
            // Update analysis
            document.getElementById('compatibilityAnalysis').textContent = compatibility.analysis;
            document.getElementById('compatibilityAdvice').textContent = compatibility.advice;
            document.getElementById('elementCompatibility').textContent = compatibility.element_compatibility;
            
            // Update strengths
            const strengthsList = document.getElementById('strengthsList');
            strengthsList.innerHTML = '';
            compatibility.strengths.forEach(strength => {
                const li = document.createElement('li');
                li.innerHTML = '<i class="fas fa-check text-success me-2"></i>' + strength;
                strengthsList.appendChild(li);
            });
            
            // Update challenges
            const challengesList = document.getElementById('challengesList');
            challengesList.innerHTML = '';
            compatibility.challenges.forEach(challenge => {
                const li = document.createElement('li');
                li.innerHTML = '<i class="fas fa-minus text-warning me-2"></i>' + challenge;
                challengesList.appendChild(li);
            });
            
            // Show results
            document.querySelector('.result-section').style.display = 'block';
        }
        
        function resetForm() {
            document.querySelector('.form-section').style.display = 'block';
            document.querySelector('.loading-spinner').style.display = 'none';
            document.querySelector('.result-section').style.display = 'none';
            document.getElementById('compatibilityForm').reset();
        }
        
        function shareReport() {
            const person1 = document.getElementById('person1Name').textContent;
            const person2 = document.getElementById('person2Name').textContent;
            const score = document.getElementById('compatibilityScore').textContent;
            
            const shareText = `🌟 ${person1} and ${person2} have ${score} cosmic compatibility! Discover your cosmic connection at ${window.location.origin}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Cosmic Connection Compatibility Report',
                    text: shareText,
                    url: window.location.origin + '/compatibility'
                });
            } else {
                // Fallback to copying to clipboard
                navigator.clipboard.writeText(shareText).then(() => {
                    alert('Report details copied to clipboard!');
                });
            }
        }
        
        function updateCreditsDisplay(newCredits) {
            // Update credits display in the page
            const creditsBadge = document.querySelector('.badge.bg-primary');
            if (creditsBadge) {
                creditsBadge.textContent = newCredits;
            }
        }
    </script>
</body>
</html>