<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h1 class="mb-4">Buy Credits</h1>
            <p class="lead mb-5">Choose a credit package that works for you. More credits mean more reports and features!</p>
            
            <?php if (!empty($plans)): ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($plans as $plan): ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white text-center py-3">
                                    <h3 class="h4 mb-0"><?php echo htmlspecialchars($plan->name); ?></h3>
                                </div>
                                <div class="card-body text-center">
                                    <h4 class="card-title pricing-card-title mb-4">
                                        $<?php echo number_format($plan->price, 2); ?>
                                        <small class="text-muted">/ <?php echo e($plan->billing_cycle === 'month' ? 'month' : 'one-time'); ?></small>
                                    </h4>
                                    <p class="h2 mb-4"><?php echo number_format($plan->credits); ?> Credits</p>
                                    
                                    <?php if (!empty($plan->features)): ?>
                                        <ul class="list-unstyled text-start mb-4">
                                            <?php foreach ($plan->features as $feature): ?>
                                                <li class="mb-2">
                                                    <i class="fas fa-check text-success me-2"></i>
                                                    <?php echo htmlspecialchars($feature); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <?php if ($plan->billing_cycle === 'month' || $plan->billing_cycle === 'year'): // Assuming these are subscription cycles ?>
                                        <a href="<?php echo url('/subscription/checkout/' . $plan->id); ?>" class="btn btn-success btn-lg w-100">
                                            Subscribe
                                        </a>
                                    <?php else: // Assuming one-time purchase ?>
                                        <a href="<?php echo url('/checkout/' . $plan->id); ?>" class="btn btn-primary btn-lg w-100">
                                            Buy Credits
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No credit plans are currently available. Please check back later.
                </div>
            <?php endif; ?>
            
            <div class="mt-5">
                <h3 class="h4 mb-3">Frequently Asked Questions</h3>
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                What can I do with my credits?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Credits can be used to generate detailed cosmic reports, access premium features, and unlock exclusive content. Each action consumes a specific number of credits as indicated before proceeding.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Do credits expire?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                No, your credits never expire. They remain in your account until you use them.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We accept all major credit cards, PayPal, and other secure payment methods. All transactions are processed securely through our payment processor.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
