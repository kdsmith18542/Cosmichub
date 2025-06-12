<?php
$title = $title ?? 'Gift a Cosmic Report';
$creditPacks = $creditPacks ?? [];
$stripe_publishable_key = $stripe_publishable_key ?? '';
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
    <!-- Stripe JS -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .gift-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .credit-pack-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #fff;
        }
        
        .credit-pack-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }
        
        .credit-pack-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .credit-pack-card.selected .text-muted {
            color: rgba(255,255,255,0.8) !important;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-gift {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-gift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-gift:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        .gift-preview {
            background: linear-gradient(135deg, #f8f9ff 0%, #e9ecff 100%);
            border-radius: 15px;
            border: 2px dashed #667eea;
        }
        
        .loading-spinner {
            display: none;
        }
        
        .loading-spinner.show {
            display: block;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #6c757d;
            position: relative;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: #e9ecef;
            transform: translateY(-50%);
        }
        
        .step.completed:not(:last-child)::after {
            background: #28a745;
        }
    </style>
</head>
<body>
    <?php include '../app/views/layouts/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="gift-container p-5">
                    <!-- Header -->
                    <div class="text-center mb-5">
                        <h1 class="display-4 mb-3">
                            <i class="fas fa-gift text-primary"></i>
                            Gift a Cosmic Report
                        </h1>
                        <p class="lead text-muted">
                            Share the magic of cosmic discovery with someone special
                        </p>
                    </div>
                    
                    <!-- Step Indicator -->
                    <div class="step-indicator">
                        <div class="step active" id="step1">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="step" id="step2">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="step" id="step3">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                    
                    <form id="giftForm">
                        <!-- Step 1: Select Credit Pack -->
                        <div class="step-content" id="stepContent1">
                            <h3 class="mb-4 text-center">Choose a Credit Pack</h3>
                            
                            <div class="row g-3">
                                <?php foreach ($creditPacks as $pack): ?>
                                <div class="col-md-4">
                                    <div class="credit-pack-card p-4 text-center h-100" 
                                         data-pack-id="<?= $pack['id'] ?>"
                                         data-credits="<?= $pack['credits'] ?>"
                                         data-price="<?= $pack['price'] ?>">
                                        <div class="mb-3">
                                            <i class="fas fa-star fa-2x text-warning"></i>
                                        </div>
                                        <h4 class="mb-2"><?= $pack['credits'] ?> Credits</h4>
                                        <p class="text-muted mb-3"><?= htmlspecialchars($pack['description'] ?? '') ?></p>
                                        <div class="h3 mb-0">$<?= number_format($pack['price'], 2) ?></div>
                                        <small class="text-muted">Perfect for exploring cosmic blueprints</small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-gift" id="nextToRecipient" disabled>
                                    Continue to Recipient Details
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Recipient Details -->
                        <div class="step-content d-none" id="stepContent2">
                            <h3 class="mb-4 text-center">Recipient Details</h3>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="recipientName" class="form-label">
                                        <i class="fas fa-user me-2"></i>Recipient's Name *
                                    </label>
                                    <input type="text" class="form-control" id="recipientName" 
                                           name="recipient_name" required 
                                           placeholder="Enter their full name">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="recipientEmail" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Recipient's Email *
                                    </label>
                                    <input type="email" class="form-control" id="recipientEmail" 
                                           name="recipient_email" required 
                                           placeholder="their.email@example.com">
                                </div>
                                
                                <div class="col-12">
                                    <label for="senderName" class="form-label">
                                        <i class="fas fa-signature me-2"></i>Your Name (as it appears on the gift)
                                    </label>
                                    <input type="text" class="form-control" id="senderName" 
                                           name="sender_name" 
                                           placeholder="Leave blank to use your account name">
                                </div>
                                
                                <div class="col-12">
                                    <label for="giftMessage" class="form-label">
                                        <i class="fas fa-heart me-2"></i>Personal Message (Optional)
                                    </label>
                                    <textarea class="form-control" id="giftMessage" name="gift_message" 
                                              rows="3" maxlength="500"
                                              placeholder="Add a personal touch to your gift..."></textarea>
                                    <div class="form-text">Maximum 500 characters</div>
                                </div>
                            </div>
                            
                            <!-- Gift Preview -->
                            <div class="gift-preview p-4 mt-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-eye me-2"></i>Gift Preview
                                </h5>
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <p class="mb-1"><strong>To:</strong> <span id="previewRecipientName">-</span></p>
                                        <p class="mb-1"><strong>From:</strong> <span id="previewSenderName">-</span></p>
                                        <p class="mb-1"><strong>Gift:</strong> <span id="previewCredits">-</span> credits</p>
                                        <p class="mb-0"><strong>Total:</strong> $<span id="previewPrice">-</span></p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <i class="fas fa-gift fa-3x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-outline-secondary me-3" id="backToPackSelection">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <button type="button" class="btn btn-gift" id="nextToPayment">
                                    Continue to Payment
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Payment -->
                        <div class="step-content d-none" id="stepContent3">
                            <h3 class="mb-4 text-center">Complete Your Gift</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-credit-card me-2"></i>Payment Details
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="card-element" class="form-control" style="height: 40px; padding: 10px;">
                                                <!-- Stripe Elements will create form elements here -->
                                            </div>
                                            <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">
                                                <i class="fas fa-receipt me-2"></i>Order Summary
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Credits:</span>
                                                <span id="summaryCredits">-</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Recipient:</span>
                                                <span id="summaryRecipient">-</span>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between h5">
                                                <span>Total:</span>
                                                <span>$<span id="summaryTotal">-</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-outline-secondary me-3" id="backToRecipient">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <button type="submit" class="btn btn-gift" id="completeGift">
                                    <span class="button-text">
                                        <i class="fas fa-gift me-2"></i>Send Gift
                                    </span>
                                    <span class="loading-spinner">
                                        <i class="fas fa-spinner fa-spin me-2"></i>Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>
                    <h3 class="mb-3">Gift Sent Successfully! 🎉</h3>
                    <p class="mb-4">Your cosmic gift has been sent to <strong id="successRecipientName"></strong>. They'll receive an email with instructions on how to redeem their gift.</p>
                    <div class="mb-4">
                        <strong>Gift Code: <span id="successGiftCode" class="text-primary"></span></strong>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="/gift/my-gifts" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>View My Gifts
                        </a>
                        <a href="/gift" class="btn btn-outline-secondary">
                            <i class="fas fa-plus me-2"></i>Send Another Gift
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize Stripe
        const stripe = Stripe('<?= $stripe_publishable_key ?>');
        const elements = stripe.elements();
        
        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
            },
        });
        
        let selectedPack = null;
        let currentStep = 1;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Mount card element
            cardElement.mount('#card-element');
            
            // Handle card errors
            cardElement.on('change', function(event) {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
            
            // Credit pack selection
            document.querySelectorAll('.credit-pack-card').forEach(card => {
                card.addEventListener('click', function() {
                    // Remove previous selection
                    document.querySelectorAll('.credit-pack-card').forEach(c => c.classList.remove('selected'));
                    
                    // Select this card
                    this.classList.add('selected');
                    
                    selectedPack = {
                        id: this.dataset.packId,
                        credits: this.dataset.credits,
                        price: this.dataset.price
                    };
                    
                    document.getElementById('nextToRecipient').disabled = false;
                });
            });
            
            // Step navigation
            document.getElementById('nextToRecipient').addEventListener('click', () => goToStep(2));
            document.getElementById('backToPackSelection').addEventListener('click', () => goToStep(1));
            document.getElementById('nextToPayment').addEventListener('click', () => {
                if (validateRecipientForm()) {
                    updateSummary();
                    goToStep(3);
                }
            });
            document.getElementById('backToRecipient').addEventListener('click', () => goToStep(2));
            
            // Form submission
            document.getElementById('giftForm').addEventListener('submit', handleFormSubmit);
            
            // Real-time preview updates
            document.getElementById('recipientName').addEventListener('input', updatePreview);
            document.getElementById('senderName').addEventListener('input', updatePreview);
        });
        
        function goToStep(step) {
            // Hide all step contents
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.add('d-none');
            });
            
            // Show target step content
            document.getElementById(`stepContent${step}`).classList.remove('d-none');
            
            // Update step indicators
            document.querySelectorAll('.step').forEach((stepEl, index) => {
                stepEl.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    stepEl.classList.add('completed');
                } else if (index + 1 === step) {
                    stepEl.classList.add('active');
                }
            });
            
            currentStep = step;
        }
        
        function validateRecipientForm() {
            const recipientName = document.getElementById('recipientName').value.trim();
            const recipientEmail = document.getElementById('recipientEmail').value.trim();
            
            if (!recipientName) {
                alert('Please enter the recipient\'s name');
                document.getElementById('recipientName').focus();
                return false;
            }
            
            if (!recipientEmail || !isValidEmail(recipientEmail)) {
                alert('Please enter a valid email address');
                document.getElementById('recipientEmail').focus();
                return false;
            }
            
            return true;
        }
        
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
        
        function updatePreview() {
            const recipientName = document.getElementById('recipientName').value.trim() || '-';
            const senderName = document.getElementById('senderName').value.trim() || '<?= htmlspecialchars(auth()['name'] ?? 'You') ?>';
            
            document.getElementById('previewRecipientName').textContent = recipientName;
            document.getElementById('previewSenderName').textContent = senderName;
            
            if (selectedPack) {
                document.getElementById('previewCredits').textContent = selectedPack.credits;
                document.getElementById('previewPrice').textContent = selectedPack.price;
            }
        }
        
        function updateSummary() {
            const recipientName = document.getElementById('recipientName').value.trim();
            
            document.getElementById('summaryCredits').textContent = selectedPack.credits;
            document.getElementById('summaryRecipient').textContent = recipientName;
            document.getElementById('summaryTotal').textContent = selectedPack.price;
        }
        
        async function handleFormSubmit(event) {
            event.preventDefault();
            
            const submitButton = document.getElementById('completeGift');
            const buttonText = submitButton.querySelector('.button-text');
            const loadingSpinner = submitButton.querySelector('.loading-spinner');
            
            // Show loading state
            submitButton.disabled = true;
            buttonText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
            
            try {
                // Create payment method
                const {error, paymentMethod} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                });
                
                if (error) {
                    throw new Error(error.message);
                }
                
                // Submit form data
                const formData = new FormData();
                formData.append('plan_id', selectedPack.id);
                formData.append('recipient_name', document.getElementById('recipientName').value.trim());
                formData.append('recipient_email', document.getElementById('recipientEmail').value.trim());
                formData.append('sender_name', document.getElementById('senderName').value.trim());
                formData.append('gift_message', document.getElementById('giftMessage').value.trim());
                formData.append('payment_method_id', paymentMethod.id);
                
                const response = await fetch('/gift/purchase', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success modal
                    document.getElementById('successRecipientName').textContent = document.getElementById('recipientName').value.trim();
                    document.getElementById('successGiftCode').textContent = result.gift_code;
                    
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else {
                    throw new Error(result.error || 'Payment failed');
                }
                
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                // Reset button state
                submitButton.disabled = false;
                buttonText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
            }
        }
    </script>
</body>
</html>