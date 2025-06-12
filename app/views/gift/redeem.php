<?php
$title = $title ?? 'Redeem Gift';
$gift = $gift ?? null;
$error = $error ?? null;
$gift_code = $gift_code ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - CosmicHub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .redeem-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .gift-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .gift-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.1) 10px,
                rgba(255,255,255,0.1) 20px
            );
            animation: shimmer 3s linear infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .gift-details {
            position: relative;
            z-index: 2;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-redeem {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-redeem:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-redeem:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .error-card {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border-radius: 15px;
        }
        
        .success-card {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
            border-radius: 15px;
        }
        
        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .floating-icon {
            position: absolute;
            font-size: 20px;
            color: rgba(255,255,255,0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-icon:nth-child(1) { top: 20%; left: 10%; animation-delay: 0s; }
        .floating-icon:nth-child(2) { top: 60%; left: 80%; animation-delay: 1s; }
        .floating-icon:nth-child(3) { top: 80%; left: 20%; animation-delay: 2s; }
        .floating-icon:nth-child(4) { top: 30%; left: 70%; animation-delay: 3s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="floating-icons">
        <i class="fas fa-star floating-icon"></i>
        <i class="fas fa-gift floating-icon"></i>
        <i class="fas fa-heart floating-icon"></i>
        <i class="fas fa-sparkles floating-icon"></i>
    </div>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="redeem-container p-5">
                    <!-- Header -->
                    <div class="text-center mb-5">
                        <h1 class="display-4 mb-3">
                            <i class="fas fa-gift text-primary"></i>
                            Redeem Your Gift
                        </h1>
                        <p class="lead text-muted">
                            Enter your gift code to claim your cosmic credits
                        </p>
                    </div>
                    
                    <?php if ($error): ?>
                        <!-- Error State -->
                        <div class="error-card p-4 text-center mb-4">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h4>Oops! Something's not right</h4>
                            <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                        </div>
                        
                        <div class="text-center">
                            <a href="/gift/redeem" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </a>
                            <a href="/" class="btn btn-link">Go to Homepage</a>
                        </div>
                        
                    <?php elseif ($gift): ?>
                        <!-- Valid Gift Display -->
                        <div class="gift-card p-4 mb-4">
                            <div class="gift-details">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h3 class="mb-2">
                                            <i class="fas fa-star me-2"></i>
                                            <?= $gift['credits_amount'] ?> Cosmic Credits
                                        </h3>
                                        <p class="mb-2">
                                            <strong>From:</strong> <?= htmlspecialchars($gift['sender_name']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <strong>To:</strong> <?= htmlspecialchars($gift['recipient_name']) ?>
                                        </p>
                                        <?php if ($gift['gift_message']): ?>
                                            <div class="mt-3 p-3" style="background: rgba(255,255,255,0.2); border-radius: 10px;">
                                                <p class="mb-0 fst-italic">
                                                    <i class="fas fa-quote-left me-2"></i>
                                                    <?= htmlspecialchars($gift['gift_message']) ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <i class="fas fa-gift fa-4x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Redemption Form -->
                        <form id="redeemForm">
                            <input type="hidden" name="gift_code" value="<?= htmlspecialchars($gift['gift_code']) ?>">
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-redeem btn-lg" id="redeemButton">
                                    <span class="button-text">
                                        <i class="fas fa-gift me-2"></i>Claim My Gift
                                    </span>
                                    <span class="loading-spinner d-none">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Processing...
                                    </span>
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Expires: <?= date('F j, Y', strtotime($gift['expires_at'])) ?>
                                </small>
                            </div>
                        </form>
                        
                    <?php else: ?>
                        <!-- Gift Code Entry Form -->
                        <form id="giftCodeForm">
                            <div class="mb-4">
                                <label for="giftCode" class="form-label">
                                    <i class="fas fa-ticket-alt me-2"></i>Gift Code
                                </label>
                                <input type="text" class="form-control form-control-lg text-center" 
                                       id="giftCode" name="gift_code" 
                                       value="<?= htmlspecialchars($gift_code) ?>"
                                       placeholder="COSMIC-XXXXXXXX" 
                                       style="letter-spacing: 2px; font-family: monospace;"
                                       required>
                                <div class="form-text text-center">
                                    Enter the gift code you received via email
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-redeem btn-lg" id="checkCodeButton">
                                    <span class="button-text">
                                        <i class="fas fa-search me-2"></i>Check Gift Code
                                    </span>
                                    <span class="loading-spinner d-none">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Checking...
                                    </span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Help Section -->
                        <div class="mt-5 pt-4 border-top">
                            <h5 class="text-center mb-3">Need Help?</h5>
                            <div class="row g-3 text-center">
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                                        <h6>Check Your Email</h6>
                                        <small class="text-muted">Look for an email with your gift code</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <i class="fas fa-question-circle fa-2x text-primary mb-2"></i>
                                        <h6>Contact Support</h6>
                                        <small class="text-muted">We're here to help if you have issues</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="success-card p-4 mb-4">
                        <i class="fas fa-check-circle fa-4x mb-3"></i>
                        <h3 class="mb-3">Gift Redeemed Successfully! 🎉</h3>
                        <p class="mb-0" id="successMessage"></p>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="/dashboard" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket me-2"></i>Start Your Cosmic Journey
                        </a>
                        <a href="/" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle gift code form submission
            const giftCodeForm = document.getElementById('giftCodeForm');
            if (giftCodeForm) {
                giftCodeForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const giftCode = document.getElementById('giftCode').value.trim();
                    if (giftCode) {
                        window.location.href = `/gift/redeem?code=${encodeURIComponent(giftCode)}`;
                    }
                });
                
                // Auto-format gift code input
                const giftCodeInput = document.getElementById('giftCode');
                giftCodeInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
            
            // Handle redemption form submission
            const redeemForm = document.getElementById('redeemForm');
            if (redeemForm) {
                redeemForm.addEventListener('submit', handleRedemption);
            }
        });
        
        async function handleRedemption(event) {
            event.preventDefault();
            
            const button = document.getElementById('redeemButton');
            const buttonText = button.querySelector('.button-text');
            const loadingSpinner = button.querySelector('.loading-spinner');
            
            // Show loading state
            button.disabled = true;
            buttonText.classList.add('d-none');
            loadingSpinner.classList.remove('d-none');
            
            try {
                const formData = new FormData(event.target);
                
                const response = await fetch('/gift/process-redemption', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success modal
                    document.getElementById('successMessage').textContent = result.message;
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else if (result.redirect) {
                    // Redirect to registration/login
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.error || 'Redemption failed');
                }
                
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                // Reset button state
                button.disabled = false;
                buttonText.classList.remove('d-none');
                loadingSpinner.classList.add('d-none');
            }
        }
        
        // Auto-focus gift code input if present
        const giftCodeInput = document.getElementById('giftCode');
        if (giftCodeInput && !giftCodeInput.value) {
            giftCodeInput.focus();
        }
    </script>
</body>
</html>