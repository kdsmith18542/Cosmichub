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

                    <form action="<?php echo url('/password/update'); ?>" method="POST" id="password_reset_form">
                        <?php echo csrf_field('password_reset_form'); ?>
                        <input type="hidden" name="token" value="<?php echo e($token); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                            <?php if (isset($errors['password'])) : ?>
                                <div class="invalid-feedback">
                                    <?php echo e($errors['password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])) : ?>
                                <div class="invalid-feedback">
                                    <?php echo e($errors['confirm_password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/layouts/footer.php'; ?>