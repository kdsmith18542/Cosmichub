<?php require_once '../app/views/layouts/header.php'; ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Unlock Your Premium Cosmic Report</h3>
                    <a href="/reports" class="btn btn-light btn-sm"><i class="fas fa-arrow-left me-2"></i>Back to Reports</a>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-4" role="alert">
                        <h5 class="alert-heading"><i class="fas fa-lock me-2"></i>This report is locked!</h5>
                        <p class="mb-0">Share your unique referral link with friends. Once <strong>3 friends sign up</strong> using your link, your report will be unlocked for free! Or, <a href="/payment/plans" class="alert-link">upgrade to premium</a> for instant access.</p>
                    </div>
                    <div class="mb-4">
                        <label for="referralLink" class="form-label"><strong>Your Referral Link:</strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="referralLink" value="<?= htmlspecialchars($data['referralUrl']) ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('referralLink').value)"><i class="fas fa-copy"></i> Copy</button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <p><strong>Progress:</strong> <?= $data['successfulReferrals'] ?> / 3 successful referrals</p>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= min(100, ($data['successfulReferrals']/3)*100) ?>%" aria-valuenow="<?= $data['successfulReferrals'] ?>" aria-valuemin="0" aria-valuemax="3"></div>
                        </div>
                        <?php if ($data['successfulReferrals'] < 3): ?>
                            <p class="mt-2 text-muted">Invite <?= 3 - $data['successfulReferrals'] ?> more friend(s) to unlock your report.</p>
                        <?php else: ?>
                            <form method="post" action="/reports/unlock/<?= $data['reportId'] ?>">
                                <button type="submit" class="btn btn-success mt-3"><i class="fas fa-unlock"></i> Unlock Now</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="/payment/plans" class="btn btn-primary"><i class="fas fa-star me-2"></i>Upgrade to Premium</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../app/views/layouts/footer.php'; ?>