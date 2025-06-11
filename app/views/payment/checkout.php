<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Complete Your Purchase</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Order Summary -->
                        <div class="col-md-6 mb-4">
                            <h3 class="h5 mb-4">Order Summary</h3>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Plan:</span>
                                        <span><?php echo htmlspecialchars($plan->name); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Credits:</span>
                                        <span class="badge bg-primary rounded-pill"><?php echo number_format($plan->credits); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-bold">Billing Cycle:</span>
                                        <span class="text-capitalize"><?php echo htmlspecialchars($plan->billing_cycle); ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h4 mb-0">Total:</span>
                                        <span class="h4 mb-0">$<?php echo number_format($plan->price, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h4 class="h6">What's included:</h4>
                                <ul class="list-unstyled">
                                    <?php if (!empty($plan->features)): ?>
                                        <?php foreach ($plan->features as $feature): ?>
                                            <li class="mb-2">
                                                <i class="fas fa-check text-success me-2"></i>
                                                <?php echo htmlspecialchars($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Payment Form -->
                        <div class="col-md-6">
                            <h3 class="h5 mb-4">Payment Information</h3>
                            <div id="payment-errors" class="alert alert-danger d-none"></div>
                            
                            <form id="payment-form" action="/process-payment" method="post">
                                <?php csrf_field('payment_form'); ?>
                                <input type="hidden" name="plan_id" value="<?php echo $plan->id; ?>">
                                
                                <div class="mb-3">
                                    <label for="card-element" class="form-label">Credit or Debit Card</label>
                                    <div id="card-element" class="form-control p-3" style="height: 45px;">
                                        <!-- Stripe Elements will be inserted here -->
                                    </div>
                                    <div id="card-errors" role="alert" class="invalid-feedback"></div>
                                    <div class="form-text">We accept all major credit cards. Your payment information is processed securely.</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" id="submit-button" class="btn btn-primary btn-lg">
                                        <span id="button-text">Pay $<?php echo number_format($plan->price, 2); ?></span>
                                        <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <p class="small text-muted">
                                        <i class="fas fa-lock me-1"></i> Your payment is secure and encrypted.
                                    </p>
                                    <div class="d-flex justify-content-center gap-3 mt-2">
                                        <i class="fab fa-cc-visa fa-2x text-muted"></i>
                                        <i class="fab fa-cc-mastercard fa-2x text-muted"></i>
                                        <i class="fab fa-cc-amex fa-2x text-muted"></i>
                                        <i class="fab fa-cc-discover fa-2x text-muted"></i>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Stripe.js and payment processing script -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Initialize Stripe with your public key
    const stripe = Stripe('<?php echo getenv('STRIPE_PUBLIC_KEY') ?: 'pk_test_your_publishable_key'; ?>');
    const elements = stripe.elements();
    const card = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
            },
        },
    });
    
    // Add an instance of the card UI component into the `card-element` <div>
    card.mount('#card-element');
    
    // Handle real-time validation errors from the card Element
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
            displayError.style.display = 'block';
        } else {
            displayError.textContent = '';
            displayError.style.display = 'none';
        }
    });
    
    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // Disable the submit button to prevent repeated clicks
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');
        
        submitButton.disabled = true;
        buttonText.textContent = 'Processing...';
        spinner.classList.remove('d-none');
        
        // Create payment method and process payment
        try {
            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: {
                    email: '<?php echo isset($user->email) ? addslashes($user->email) : ''; ?>',
                    name: '<?php echo isset($user->name) ? addslashes($user->name) : ''; ?>'
                }
            });
            
            if (error) {
                throw error;
            }
            
            // Add the payment method to the form
            const paymentMethodInput = document.createElement('input');
            paymentMethodInput.type = 'hidden';
            paymentMethodInput.name = 'payment_method';
            paymentMethodInput.value = paymentMethod.id;
            form.appendChild(paymentMethodInput);
            
            // Submit the form
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(new FormData(form))
            });
            
            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            // Redirect to success page
            window.location.href = result.redirect || '/payment/success';
            
        } catch (error) {
            // Show error to customer
            const errorElement = document.getElementById('payment-errors');
            errorElement.textContent = error.message || 'An error occurred while processing your payment. Please try again.';
            errorElement.classList.remove('d-none');
            
            // Re-enable the submit button
            submitButton.disabled = false;
            buttonText.textContent = 'Pay $<?php echo number_format($plan->price, 2); ?>';
            spinner.classList.add('d-none');
            
            // Scroll to error message
            errorElement.scrollIntoView({ behavior: 'smooth' });
        }
    });
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
