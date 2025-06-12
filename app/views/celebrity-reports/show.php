<?php require_once '../app/views/layouts/header.php'; ?>

<!-- SEO Meta Tags -->
<meta name="description" content="Explore <?= htmlspecialchars($celebrity->name) ?>'s complete cosmic blueprint including zodiac signs, birth chart analysis, rarity score, and astrological insights. Born <?= format_date($celebrity->birth_date) ?>.">
<meta name="keywords" content="<?= htmlspecialchars($celebrity->name) ?>, astrology, birth chart, zodiac sign, celebrity horoscope, cosmic blueprint, <?= format_date($celebrity->birth_date, 'F j Y') ?>">
<meta property="og:title" content="<?= htmlspecialchars($celebrity->name) ?> - Cosmic Blueprint & Astrology Report">
<meta property="og:description" content="Discover the astrological profile and cosmic significance of <?= htmlspecialchars($celebrity->name) ?>. Complete birth chart analysis and zodiac insights.">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/celebrity-reports/<?= htmlspecialchars($celebrity->slug) ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= htmlspecialchars($celebrity->name) ?> - Cosmic Blueprint">
<meta name="twitter:description" content="Explore <?= htmlspecialchars($celebrity->name) ?>'s astrological profile and cosmic significance.">

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "<?= htmlspecialchars($celebrity->name) ?>",
  "birthDate": "<?= date('Y-m-d', strtotime($celebrity->birth_date)) ?>",
  "url": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/celebrity-reports/<?= htmlspecialchars($celebrity->slug) ?>",
  "description": "Celebrity astrology and cosmic blueprint for <?= htmlspecialchars($celebrity->name) ?>",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/celebrity-reports/<?= htmlspecialchars($celebrity->slug) ?>"
  }
}
</script>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h1 class="mb-0"><?= htmlspecialchars($celebrity->name) ?> - Cosmic Blueprint</h1>
        </div>
        <div class="card-body">
            <p class="lead"><strong>Birth Date:</strong> <?= format_date($celebrity->birth_date) ?></p>
            
            <hr>

            <?php 
            $reportData = null;
            if (is_string($celebrity->report_content)) {
                $reportData = json_decode($celebrity->report_content, true);
            } elseif (is_array($celebrity->report_content)) {
                $reportData = $celebrity->report_content; // Already an array
            }
            ?>

            <?php if (!empty($reportData) && is_array($reportData)):
                // Helper function to safely access array keys
                $get_val = function($arr, $key, $default = null) {
                    return isset($arr[$key]) ? $arr[$key] : $default;
                };
            ?>
                
                <!-- Cosmic Significance Blurb -->
                <?php if ($blurb = $get_val($reportData, 'cosmic_significance_blurb')): ?>
                <div class="mb-4 p-3 bg-light border rounded shadow-sm">
                    <h4 class="text-purple"><i class="fas fa-meteor me-2"></i>Cosmic Significance</h4>
                    <p class="lead"><em><?php echo nl2br(htmlspecialchars($blurb)); ?></em></p>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Left Column: Astrological Profile & Rarity -->
                    <div class="col-md-6">
                        <!-- Astrological Profile -->
                        <?php if ($astro = $get_val($reportData, 'astrological_profile')): ?>
                        <div class="mb-4 card shadow-sm">
                            <div class="card-header bg-info text-white"><h5 class="mb-0"><i class="fas fa-star-of-life me-2"></i>Astrological Profile</h5></div>
                            <ul class="list-group list-group-flush">
                                <?php if ($val = $get_val($astro, 'western_zodiac')): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Western Zodiac:
                                        <span class="badge bg-info rounded-pill"><?php echo htmlspecialchars($val); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($val = $get_val($astro, 'chinese_zodiac')): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Chinese Zodiac:
                                        <span class="badge bg-success rounded-pill"><?php echo htmlspecialchars($val); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($val = $get_val($astro, 'birthstone')): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Birthstone:
                                        <span class="badge bg-warning text-dark rounded-pill"><?php echo htmlspecialchars($val); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($val = $get_val($astro, 'birth_flower')): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Birth Flower:
                                        <span class="badge bg-danger rounded-pill"><?php echo htmlspecialchars($val); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($lp = $get_val($astro, 'life_path_number')):\ ?>
                                    <li class="list-group-item">
                                        Life Path Number: <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($get_val($lp, 'number')); ?></span>
                                        <?php if ($desc = $get_val($lp, 'description')): ?>
                                            <p class="mt-2 mb-0 text-muted"><small><?php echo htmlspecialchars($desc); ?></small></p>
                                        <?php endif; ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Rarity Score Details -->
                        <?php if ($rarity = $get_val($reportData, 'rarity_score_details')):
                            $rarityColor = htmlspecialchars($get_val($rarity, 'color', '#6c757d'));
                        ?>
                        <div class="mb-4 card shadow-sm">
                             <div class="card-header text-white" style="background-color: <?php echo $rarityColor; ?>;"><h5 class="mb-0"><i class="fas fa-gem me-2"></i>Birthday Rarity</h5></div>
                            <div class="card-body" >
                                <p class="display-6" style="color: <?php echo $rarityColor; ?>;">
                                    <?php echo htmlspecialchars($get_val($rarity, 'score', 'N/A')); ?> 
                                    <small class="text-muted fs-5">(<?php echo htmlspecialchars($get_val($rarity, 'description', 'No description')); ?>)</small>
                                </p>
                                <?php if ($explanation = $get_val($rarity, 'explanation')): ?>
                                    <p><em><?php echo htmlspecialchars($explanation); ?></em></p>
                                <?php endif; ?>
                                <?php if ($factors = $get_val($rarity, 'factors')):\ ?>
                                    <h6 class="mt-3">Contributing Factors:</h6>
                                    <ul class="list-unstyled small">
                                        <?php foreach ($factors as $factor => $desc): ?>
                                            <?php if(!empty($desc)): ?>
                                            <li><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $factor))); ?>:</strong> <?php echo htmlspecialchars($desc); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column: Archetype & Historical Snapshot -->
                    <div class="col-md-6">
                        <!-- Archetype Information -->
                        <?php 
                        $archetypeToDisplay = $get_val($reportData, 'archetype');
                        // Fallback to fetching from relation if not in report_content or if more details needed
                        if (!$archetypeToDisplay && $celebrity->archetypes()->exists()) {
                            $firstArchetype = $celebrity->archetypes()->first();
                            if ($firstArchetype) {
                                $archetypeToDisplay = [
                                    'name' => $firstArchetype->name,
                                    'slug' => $firstArchetype->slug,
                                    'description' => $firstArchetype->description
                                ];
                            }
                        }
                        ?>
                        <?php if (!empty($archetypeToDisplay) && $get_val($archetypeToDisplay, 'name')):\ ?>
                        <div class="mb-4 card shadow-sm">
                            <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-theater-masks me-2"></i>Primary Archetype</h5></div>
                            <div class="card-body">
                                <h4><a href="/archetypes/<?php echo htmlspecialchars($get_val($archetypeToDisplay, 'slug')); ?>" class="text-decoration-none"><?php echo htmlspecialchars($get_val($archetypeToDisplay, 'name')); ?></a></h4>
                                <?php if ($desc = $get_val($archetypeToDisplay, 'description')): ?>
                                    <p class="small text-muted"><?php echo nl2br(htmlspecialchars($desc)); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Historical Snapshot -->
                        <?php if ($historical = $get_val($reportData, 'historical_snapshot')):\ ?>
                        <div class="mb-4 card shadow-sm">
                            <div class="card-header bg-secondary text-white"><h5 class="mb-0"><i class="fas fa-landmark me-2"></i>On This Day in History (<?php echo format_date($celebrity->birth_date, 'F jS'); ?>)</h5></div>
                            <div class="card-body">
                            <?php if ($events = $get_val($historical, 'events')):\ ?>
                                <h6><i class="fas fa-calendar-alt me-1"></i>Significant Events</h6>
                                <ul class="list-group list-group-flush small mb-2">
                                    <?php foreach (array_slice($events, 0, 3) as $event): // Limit to 3 for brevity ?>
                                        <li class="list-group-item px-0 py-1"><?php echo htmlspecialchars($get_val($event, 'year')) ?>: <?php echo htmlspecialchars($get_val($event, 'description')); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="small text-muted">No significant events data available for this day.</p>
                            <?php endif; ?>

                            <?php if ($births = $get_val($historical, 'births')):\ ?>
                                <h6 class="mt-2"><i class="fas fa-birthday-cake me-1"></i>Notable Births</h6>
                                <ul class="list-group list-group-flush small mb-2">
                                    <?php foreach (array_slice($births, 0, 3) as $birth): // Limit to 3 ?>
                                        <li class="list-group-item px-0 py-1"><?php echo htmlspecialchars($get_val($birth, 'year')) ?>: <?php echo htmlspecialchars($get_val($birth, 'description')); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="small text-muted">No notable births data available for this day.</p>
                            <?php endif; ?>

                            <?php if ($deaths = $get_val($historical, 'deaths')):\ ?>
                                <h6 class="mt-2"><i class="fas fa-skull-crossbones me-1"></i>Notable Deaths</h6>
                                <ul class="list-group list-group-flush small">
                                    <?php foreach (array_slice($deaths, 0, 3) as $death): // Limit to 3 ?>
                                        <li class="list-group-item px-0 py-1"><?php echo htmlspecialchars($get_val($death, 'year')) ?>: <?php echo htmlspecialchars($get_val($death, 'description')); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="small text-muted">No notable deaths data available for this day.</p>
                            <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <p class="text-center text-muted">No detailed report content available for this celebrity.</p>
            <?php endif; ?>


            <div class="mt-4">
                <a href="/celebrity-reports" class="btn btn-secondary">Back to Almanac</a>
            </div>
        </div>
        <div class="card-footer text-muted">
            Report generated on <?= format_datetime($celebrity->created_at) ?>
        </div>
    </div>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>