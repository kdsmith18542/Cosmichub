<?php $this->extend('emails/layouts/default') ?>

<?php $this->section('content') ?>
    <h2 style="color: #111111; margin-top: 0;">Verify Your Email Address</h2>
    
    <p>Hello <?= htmlspecialchars($user->name) ?>,</p>
    
    <p>Thank you for registering with CosmicHub! Please click the button below to verify your email address and activate your account.</p>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="<?= $verificationUrl ?>" class="btn" style="">
            Verify Email Address
        </a>
    </div>
    
    <p>Or copy and paste this link into your browser:</p>
    <p style="word-break: break-all; color: #4f46e5;"><?= htmlspecialchars($verificationUrl) ?></p>
    
    <p>This verification link will expire in 24 hours.</p>
    
    <p>If you did not create an account, no further action is required.</p>
    
    <p>Best regards,<br>The CosmicHub Team</p>
    
    <hr style="border: none; border-top: 1px solid #eaeaea; margin: 30px 0;">
    
    <p style="font-size: 12px; color: #666;">
        If you're having trouble clicking the "Verify Email Address" button, 
        copy and paste the URL below into your web browser:
        <br>
        <?= htmlspecialchars($verificationUrl) ?>
    </p>
<?php $this->endSection() ?>
