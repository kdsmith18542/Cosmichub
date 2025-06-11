<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <div class="bg-success bg-opacity-10 d-inline-flex p-3 rounded-circle mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h2 mb-3">Payment Successful!</h1>
                        <p class="lead"><?php echo e($message ?? 'Thank you for your purchase. Your credits have been added to your account.'); ?></p>
                    </div>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body text-start">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Order Number</span>
                                <span class="fw-bold">#<?php echo isset($_GET['order']) ? htmlspecialchars($_GET['order']) : 'N/A'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Date</span>
                                <span class="fw-bold"><?php echo date('F j, Y, g:i a'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Credits Added</span>
                                <span class="badge bg-primary rounded-pill">+<?php echo e($_GET['credits'] ?? '0'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/dashboard" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="/reports/new" class="btn btn-outline-primary btn-lg px-5">
                            <i class="fas fa-plus-circle me-2"></i>Create a Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-muted">
                    Need help? <a href="/contact">Contact our support team</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
