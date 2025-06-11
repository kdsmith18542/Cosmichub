<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <div class="bg-warning bg-opacity-10 d-inline-flex p-3 rounded-circle mb-4">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h2 mb-3">Payment Cancelled</h1>
                        <p class="lead"><?php echo e($message ?? 'Your payment was not completed. No charges were made to your account.'); ?></p>
                    </div>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <p class="mb-0">
                                If this was a mistake, you can return to our plans and try again.
                            </p>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center
                        <a href="/credits" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-arrow-left me-2"></i>Back to Plans
                        </a>
                        <a href="/contact" class="btn btn-outline-secondary btn-lg px-5">
                            <i class="fas fa-question-circle me-2"></i>Need Help?
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-muted">
                    Having trouble with your payment? <a href="/contact">Contact our support team</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
