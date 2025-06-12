<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-4">
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h2 fw-bold mb-4">Birthday Rarity Score</h1>
        
            <?php if (!$hasBirthdate): ?>
                <div class="alert alert-warning border-start border-warning border-4" role="alert">
                    <p>You haven't set your birthdate yet. Please update your profile to see your Birthday Rarity Score.</p>
                    <div class="mt-3">
                        <a href="/profile" class="btn btn-primary">
                            Update Profile
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row align-items-center">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <p class="text-muted mb-4">
                            Your birthday rarity score indicates how unique your birth date is compared to others.
                            The score is calculated based on various factors including the month, day, proximity to holidays,
                            and whether you were born on a leap year.
                        </p>
                        
                        <div class="mb-4">
                            <p class="text-muted"><strong>Your Birthdate:</strong> <?php echo date('F j, Y', strtotime($user->birthdate)); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-muted"><strong>Rarity Level:</strong> <span style="color: <?php echo e($rarityColor); ?>"><?php echo e($rarityDescription); ?></span></p>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-muted">
                                <?php echo e($this->getRarityExplanation($rarityScore, $rarityDescription)); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <a href="#" class="btn btn-primary me-2" onclick="shareScore(); return false;">
                                <i class="fas fa-share-alt me-1"></i> Share My Score
                            </a>
                            <a href="/dashboard" class="btn btn-secondary">
                                Back to Dashboard
                            </a>
                        </div>
                        
                        <!-- Referral Status -->
                        <div class="mt-4 p-4 bg-light rounded">
                            <h3 class="fw-bold mb-2">Share to Unlock Full Report</h3>
                            <?php if ($hasEnoughReferrals): ?>
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle me-1"></i> Unlocked! Thank you for sharing.
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-2">
                                    Share your unique link with friends. When <?php echo e($remainingReferrals); ?> more friends generate their score, you'll unlock the full detailed report!
                                </p>
                                <div class="d-flex align-items-center mt-2">
                                    <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo min(100, ($referral->successful_referrals / 3) * 100); ?>%"></div>
                                    </div>
                                    <span class="small text-muted text-nowrap"><?php echo e($referral->successful_referrals); ?>/3</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <div class="input-group">
                                    <input type="text" id="referralLink" value="<?php echo e($referralUrl); ?>" class="form-control" readonly>
                                    <button onclick="copyReferralLink()" class="btn btn-primary">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                    </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 d-flex justify-content-center">
                        <div class="position-relative">
                            <!-- Circular score display -->
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 16rem; height: 16rem; background: conic-gradient(<?php echo e($rarityColor); ?> <?php echo e($rarityScore); ?>%, #f8f9fa 0);">
                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" style="width: 12rem; height: 12rem;">
                                    <div class="text-center">
                                        <div class="display-3 fw-bold" style="color: <?php echo e($rarityColor); ?>"><?php echo e($rarityScore); ?></div>
                                        <div class="text-muted small">RARITY SCORE</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5 border-top pt-4">
                    <h2 class="h4 fw-bold mb-4">What Makes Your Birthday Special?</h2>
                    <?php if (!empty($hasActiveSubscription)): ?>
                        <!-- Premium unlocked for subscribers -->
                        <div class="bg-success bg-opacity-10 p-4 rounded text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-star display-4 text-warning"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 dark:text-white mb-2">Premium Access Granted</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            As a premium subscriber, you have instant access to the full detailed analysis of what makes your birthday special!
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Birth Month Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo e($this->getMonthFactorDescription(date('n', strtotime($user->birthdate)))); ?>
                            </p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Day of Month Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo e($this->getDayFactorDescription(date('n', strtotime($user->birthdate)), date('j', strtotime($user->birthdate)))); ?>
                            </p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Special Date Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo e($this->getSpecialDateDescription(date('n', strtotime($user->birthdate)), date('j', strtotime($user->birthdate)))); ?>
                            </p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Leap Year Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo e($this->getLeapYearDescription(date('n', strtotime($user->birthdate)), date('j', strtotime($user->birthdate)), date('Y', strtotime($user->birthdate)))); ?>
                            </p>
                        </div>
                    </div>
                <?php elseif (!$hasEnoughReferrals): ?>
                    <!-- Locked content preview -->
                    <div class="bg-gray-100 dark:bg-gray-700 p-6 rounded-lg text-center mb-4">
                        <div class="mb-4">
                            <i class="fas fa-lock text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 dark:text-white mb-2">Detailed Analysis Locked</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            Share your rarity score with friends to unlock the full detailed analysis of what makes your birthday special, or <a href=\"/upgrade\" class=\"alert-link\">upgrade to premium</a> for instant access!
                        </p>
                        <button onclick="shareScore()" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-share-alt mr-1"></i> Share to Unlock
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Unlocked content via referrals -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Birth Month Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo $this->getMonthFactorDescription(date('n', strtotime($user->birthdate))); ?>
                            </p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Day of Month Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo $this->getDayFactorDescription(date('n', strtotime($user->birthdate)), date('j', strtotime($user->birthdate))); ?>
                            </p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Special Date Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo $this->getSpecialDateDescription(date('n', strtotime($user->birthdate)), date('j', strtotime($user->birthdate))); ?>
                            </p>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded">
                            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Leap Year Factor</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                <?php echo $this->getLeapYearDescription(date('n', strtotime($user->birthdate)), date('j', strtotime($user->birthdate)), date('Y', strtotime($user->birthdate))); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function shareScore() {
    // Get referral link from server
    fetch('/rarity-score/share-link')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check if Web Share API is supported
                if (navigator.share) {
                    navigator.share({
                        title: 'My Birthday Rarity Score',
                        text: 'My birthday rarity score is <?php echo e($rarityScore); ?> (<?php echo e($rarityDescription); ?>). Check yours on CosmicHub!',
                        url: data.referralUrl,
                    })
                    .then(() => {
                        console.log('Successful share');
                        // Optionally refresh the page to update referral count
                        // setTimeout(() => location.reload(), 1000);
                    })
                    .catch((error) => console.log('Error sharing', error));
                } else {
                    // Fallback for browsers that don't support the Web Share API
                    copyReferralLink();
                    alert('Share this link with your friends: ' + data.referralUrl);
                }
            }
        })
        .catch(error => {
            console.error('Error fetching referral link:', error);
            alert('There was an error generating your share link. Please try again.');
        });
}

function copyReferralLink() {
    const referralLinkInput = document.getElementById('referralLink');
    referralLinkInput.select();
    document.execCommand('copy');
    
    // Show a temporary copied message
    const originalText = document.querySelector('button[onclick="copyReferralLink()"]').innerHTML;
    document.querySelector('button[onclick="copyReferralLink()"]').innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => {
        document.querySelector('button[onclick="copyReferralLink()"]').innerHTML = originalText;
    }, 2000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>