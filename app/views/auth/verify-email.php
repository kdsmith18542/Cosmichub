<?php $this->extend('layouts/main'); ?>

<?php $this->section('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><?= $title ?? 'Verify Your Email Address' ?></div>

                <div class="card-body">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?= $success ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <p>
                        A verification link has been sent to <strong><?= htmlspecialchars($email ?? '') ?></strong>.
                        Please check your email and click on the verification link to verify your email address.
                    </p>
                    
                    <p>
                        If you did not receive the email,
                        <form class="d-inline" method="POST" action="<?= $resendUrl ?? '/email/verification-notification' ?>">
                            <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                                click here to request another
                            </button>.
                        </form>
                    </p>
                    
                    <div class="mt-4">
                        <p>Having trouble receiving the email? Try these steps:</p>
                        <ol>
                            <li>Check your spam or junk folder.</li>
                            <li>Make sure the email address is correct: <strong><?= htmlspecialchars($email ?? '') ?></strong></li>
                            <li>Add <?= $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@cosmichub.online' ?> to your contacts.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>
