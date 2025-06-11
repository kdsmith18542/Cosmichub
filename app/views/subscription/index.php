<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-5">
    <h2>My Subscription</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo e($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php echo e($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php // Display subscription details here ?>
    <?php 
    <?php if ($subscription && $subscription->isActive()): ?>
        <p><strong>Plan:</strong> <?php echo htmlspecialchars($subscription->plan()->name); ?></p>
        <p><strong>Status:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($subscription->getStatusName()); ?></span></p>
        <p><strong>Next Billing Date:</strong> <?php echo htmlspecialchars($subscription->getNextBillingDate() ? date('F j, Y', strtotime($subscription->getNextBillingDate())) : 'N/A'); ?></p>
        
        <?php if ($subscription->isCanceled() && $subscription->ends_at > date('Y-m-d H:i:s')): ?>
            <p class="text-warning">Your subscription is set to cancel on <?php echo htmlspecialchars(date('F j, Y', strtotime($subscription->ends_at))); ?>.</p>
            <form action="<?php echo url('/subscription/resume'); ?>" method="POST" class="d-inline">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-success">Resume Subscription</button>
            </form>
        <?php elseif (!$subscription->isCanceled()): ?>
            <form action="<?php echo url('/subscription/cancel'); ?>" method="POST" class="d-inline">
                <?php csrf_field(); ?>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel your subscription?');">Cancel Subscription</button>
            </form>
        <?php endif; ?>

    <?php elseif ($subscription && $subscription->isCanceled()): ?>
        <p>Your subscription to <strong><?php echo htmlspecialchars($subscription->plan()->name); ?></strong> was canceled on <?php echo htmlspecialchars(date('F j, Y', strtotime($subscription->canceled_at))); ?>.</p>
        <p><a href="<?php echo url('/payment/plans'); ?>" class="btn btn-primary">View Plans</a></p>
    <?php else: ?>
        <p>You do not have an active subscription.</p>
        <p><a href="<?php echo url('/payment/plans'); ?>" class="btn btn-primary">View Subscription Plans</a></p>
    <?php endif; ?>

    <?php if (!isset($subscription) || !$subscription): ?>
        <p><a href="<?php echo url('/payment/plans'); ?>" class="btn btn-primary">View Subscription Plans</a></p>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>