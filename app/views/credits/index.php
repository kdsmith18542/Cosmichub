<?php extend_view('layouts/default', ['title' => $title ?? 'Purchase Credits']); ?>

<div class="container mt-5">
    <h2><?php echo htmlspecialchars($title ?? 'Purchase Credits'); ?></h2>

    <?php display_flash_messages(); ?>

    <?php if (!empty($creditPacks)): ?>
        <div class="row mt-4">
            <?php foreach ($creditPacks as $pack): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($pack->name); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($pack->description ?? ''); ?></p>
                            <p class="card-text"><strong>Credits:</strong> <?php echo htmlspecialchars($pack->credits); ?></p>
                            <p class="card-text"><strong>Price:</strong> <?php echo htmlspecialchars($pack->getFormattedPrice()); ?></p>
                            
                            <form id="purchase-form-<?php echo e($pack->id); ?>" class="purchase-form">
                                <?php csrf_token_field('credit_purchase_form'); ?>
                                <input type="hidden" name="pack_id" value="<?php echo e($pack->id); ?>">
                                <button type="button" class="btn btn-primary purchase-button" data-pack-id="<?php echo e($pack->id); ?>">
                                    Purchase Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Stripe.js and client-side logic -->
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const stripe = Stripe('<?php echo htmlspecialchars($stripe_publishable_key ?? ''); ?>');
                const purchaseButtons = document.querySelectorAll('.purchase-button');

                purchaseButtons.forEach(button => {
                    button.addEventListener('click', async function () {
                        const packId = this.dataset.packId;
                        const form = document.getElementById('purchase-form-' + packId);
                        const formData = new FormData(form);

                        // Disable button to prevent multiple clicks
                        this.disabled = true;
                        this.textContent = 'Processing...';

                        try {
                            const response = await fetch('<?php echo base_url('/credits/purchase'); ?>', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest' // Important for server to know it's an AJAX request
                                }
                            });

                            const result = await response.json();

                            if (result.error) {
                                alert('Error: ' + result.error);
                                this.disabled = false;
                                this.textContent = 'Purchase Now';
                            } else if (result.sessionId) {
                                // Redirect to Stripe Checkout
                                const { error } = await stripe.redirectToCheckout({
                                    sessionId: result.sessionId
                                });
                                if (error) {
                                    alert('Stripe Checkout Error: ' + error.message);
                                    this.disabled = false;
                                    this.textContent = 'Purchase Now';
                                }
                            } else {
                                alert('An unexpected error occurred. Please try again.');
                                this.disabled = false;
                                this.textContent = 'Purchase Now';
                            }
                        } catch (error) {
                            console.error('Purchase Error:', error);
                            alert('An error occurred while trying to process your purchase. Please check the console for details.');
                            this.disabled = false;
                            this.textContent = 'Purchase Now';
                        }
                    });
                });
            });
        </script>

    <?php else: ?>
        <p class="mt-4">There are currently no credit packs available for purchase. Please check back later.</p>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?php echo base_url('/dashboard'); ?>" class="btn btn-secondary">Back to Dashboard</a>
        <a href="<?php echo base_url('/credits/history'); ?>" class="btn btn-info">View Credit History</a>
    </div>
</div>

<?php end_view(); ?>