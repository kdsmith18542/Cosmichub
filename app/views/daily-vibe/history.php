<?php $this->extend('layouts/main'); ?>

<?php $this->section('content'); ?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Your Vibe History</h1>
                <a href="/daily-vibe" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Today's Vibe
                </a>
            </div>
            
            <?php if (empty($vibeHistory)) : ?>
                <div class="card cosmic-card">
                    <div class="card-body text-center py-5">
                        <div class="display-1 text-muted mb-4">
                            <i class="far fa-moon"></i>
                        </div>
                        <h3 class="h4 mb-3">No Vibe History Yet</h3>
                        <p class="text-muted mb-4">
                            You haven't generated any daily vibes yet. Check back daily for new cosmic insights!
                        </p>
                        <a href="/daily-vibe" class="btn btn-primary">
                            Get Your Daily Vibe
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <div class="card cosmic-card">
                    <div class="list-group list-group-flush">
                        <?php 
                        $currentMonth = '';
                        foreach ($vibeHistory as $index => $vibe): 
                            $vibeDate = new DateTime($vibe->date);
                            $monthYear = $vibeDate->format('F Y');
                            
                            // Display month header if it's different from the previous one
                            if ($monthYear !== $currentMonth):
                                $currentMonth = $monthYear;
                        ?>
                            <div class="list-group-item bg-light">
                                <h5 class="mb-0 text-muted"><?= $currentMonth ?></h5>
                            </div>
                        <?php endif; ?>
                        
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="vibe-date text-center">
                                        <div class="fw-bold"><?= $vibeDate->format('d') ?></div>
                                        <div class="text-uppercase small text-muted"><?= $vibeDate->format('D') ?></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <p class="mb-1"><?= nl2br(htmlspecialchars($vibe->vibe_text)) ?></p>
                                </div>
                                <div class="col-auto">
                                    <span class="text-muted"><?= $this->getRandomEmoji() ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="/daily-vibe" class="btn btn-primary">
                            <i class="fas fa-sun me-1"></i> Today's Vibe
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('styles'); ?>
<style>
.vibe-date {
    width: 50px;
    height: 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-right: 15px;
}

.vibe-date .fw-bold {
    line-height: 1;
    font-size: 1.1rem;
}

.vibe-date .small {
    font-size: 0.65rem;
    line-height: 1;
    margin-top: 2px;
}

.list-group-item {
    border-left: 0;
    border-right: 0;
}

.list-group-item:first-child {
    border-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.list-group-item:last-child {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}
</style>
<?php $this->endSection(); ?>
