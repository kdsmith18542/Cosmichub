<?php require_once APPROOT . '/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center"><?php echo e($title); ?></h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])) : ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo e($_SESSION['error']); ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])) : ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo e($_SESSION['success']); ?>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <p class="text-muted">Enter your email address and we will send you a link to reset your password.</p>

                    <form action="<?php echo url('/password/email'); ?>" method="POST" id="password_email_form">
                        <?php echo csrf_field('password_email_form'); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo e(old('email')); ?>" required>
                            <?php if (isset($errors['email'])) : ?>
                                <div class="invalid-feedback">
                                    <?php echo e($errors['email']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Password Reset Link</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo url('/login'); ?>">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>