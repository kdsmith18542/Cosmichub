<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0">Subscribe to <?php echo htmlspecialchars($plan->name); ?></h2>
                </div>
                <div class="card-body">
                    <p><strong>Plan:</strong> <?php echo htmlspecialchars($plan->name); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($plan->price, 2); ?> / <?php echo htmlspecialchars($plan->billing_cycle); ?></p>
                    <p><strong>Credits:</strong> <?php echo number_format($plan->credits); ?> (<?php echo $plan->billing_cycle === 'month' ? 'per month' : 'one-time allocation for subscription period'; ?>)</p>

                    <form id="subscription-form" action="<?php echo url('/subscription/subscribe'); ?>" method="post">
                        <?php csrf_input('payment_form'); // Assuming 'payment_form' is the CSRF token name for this form ?>
                        <input type="hidden" name="plan_id" value="<?php echo e($plan->id); ?>">

                        <div class="mb-3">
                            <label for="card-holder-name" class="form-label">Card Holder Name</label>
                            <input id="card-holder-name" type="text" class="form-control" placeholder="Name on card" required>
                        </div>

                        <!-- Stripe Elements Placeholder -->
                        <div class="mb-3">
                            <label for="card-element" class="form-label">Credit or debit card</label>
                            <div id="card-element" class="form-control"></div>
                            <!-- Used to display form errors. -->
                            <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                        </div>

                        <button id="card-button" class="btn btn-success w-100" data-secret="">
                            Subscribe Now
                        </button>
                    </form>
                    <div id="payment-message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var stripePublishableKey = '<?php echo e($stripe_publishable_key); ?>';
        if (!stripePublishableKey) {
            console.error('Stripe publishable key is not set.');
            document.getElementById('payment-message').textContent = 'Payment gateway is not configured. Please contact support.';
            return;
        }
        var stripe = Stripe(stripePublishableKey);
        var elements = stripe.elements();

        var style = {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        var cardElement = elements.create('card', {style: style});
        cardElement.mount('#card-element');

        var cardHolderName = document.getElementById('card-holder-name');
        var form = document.getElementById('subscription-form');
        var cardButton = document.getElementById('card-button');
        var paymentMessage = document.getElementById('payment-message');

        cardElement.on('change', function(event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            cardButton.disabled = true;
            paymentMessage.textContent = ''; // Clear previous messages

            const { setupIntent, error: setupIntentError } = await stripe.confirmCardSetup(
                cardButton.dataset.clientSecret, // This needs to be set up for new cards if not using a direct subscription creation with payment method
                {
                    payment_method: {
                        card: cardElement,
                        billing_details: { name: cardHolderName.value }
                    }
                }
            );

            // The above confirmCardSetup is more for saving cards. For direct subscription, we might need to create payment method and then subscribe.
            // Let's adjust to create payment method first.

            const { paymentMethod, error: paymentMethodError } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: cardHolderName.value,
                    email: '<?php echo e($user->email); ?>' // Assuming user email is available
                },
            });

            if (paymentMethodError) {
                paymentMessage.textContent = paymentMethodError.message;
                cardButton.disabled = false;
                return;
            }

            // Add the payment_method_id to the form and submit
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment_method_id');
            hiddenInput.setAttribute('value', paymentMethod.id);
            form.appendChild(hiddenInput);

            // Now submit the form to our server
            // The server will use this payment_method_id to create the subscription
            // form.submit(); // This would be a traditional form submission

            // Instead, let's use fetch to submit and handle response for SPA-like feel
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Important for server to know it's an AJAX request if needed
                },
                body: new URLSearchParams(new FormData(form))
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    paymentMessage.textContent = data.error;
                    cardButton.disabled = false;
                } else if (data.requires_action && data.payment_intent_client_secret) {
                    // Handle 3D Secure or other actions
                    paymentMessage.textContent = 'Payment requires further action. Redirecting...';
                    stripe.confirmCardPayment(data.payment_intent_client_secret).then(function(result) {
                        if (result.error) {
                            paymentMessage.textContent = result.error.message;
                            cardButton.disabled = false;
                        } else {
                            // Payment successful after action
                            paymentMessage.textContent = 'Subscription activated successfully after action!';
                            // Redirect to subscription page or success page
                            window.location.href = '<?php echo url('/subscription'); ?>'; 
                        }
                    });
                } else if (data.success) {
                    paymentMessage.textContent = data.message || 'Subscription activated successfully!';
                    // Redirect to subscription page or success page
                     window.location.href = '<?php echo url('/subscription'); ?>'; 
                } else {
                    paymentMessage.textContent = 'An unexpected error occurred. Please try again.';
                    cardButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                paymentMessage.textContent = 'An error occurred while processing your subscription. Please try again.';
                cardButton.disabled = false;
            });
        });
    });
</script>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>