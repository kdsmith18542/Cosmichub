<?php
// SEO optimization for Celebrity Almanac
$title = 'Celebrity Almanac - Famous Birthdays & Zodiac Signs | CosmicHub';
$description = 'Explore cosmic blueprints of famous celebrities. Discover zodiac signs, birth charts, and astrological insights of your favorite stars. Updated daily with new celebrity reports.';
$keywords = 'celebrity zodiac signs, famous birthdays, celebrity astrology, star signs, celebrity birth charts, famous people astrology';
$structured_data = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Celebrity Almanac',
    'description' => $description,
    'url' => 'https://cosmichub.online/celebrity-reports',
    'mainEntity' => [
        '@type' => 'ItemList',
        'name' => 'Celebrity Astrology Reports',
        'description' => 'Comprehensive astrological profiles of famous celebrities'
    ]
]);
require_once '../app/views/layouts/header.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Celebrity Almanac</h1>
            <p class="lead text-muted">Discover the cosmic blueprints of famous celebrities and their astrological insights</p>
        </div>
        <?php if (is_admin()): ?>
            <a href="/celebrity-reports/create" class="btn btn-primary">Add Celebrity Report</a>
        <?php endif; ?>
    </div>

    <form action="/celebrity-reports/search" method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Search celebrities..." value="<?= htmlspecialchars($search_query ?? '') ?>">
            <button class="btn btn-outline-secondary" type="submit">Search</button>
        </div>
    </form>

    <?php if (empty($celebrities)): ?>
        <div class="alert alert-info" role="alert">
            <?php if (isset($search_query)): ?>
                No celebrity reports found matching your search "<?= htmlspecialchars($search_query) ?>".
            <?php else: ?>
                No celebrity reports available yet.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($celebrities as $celebrity): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="/celebrity-reports/<?= htmlspecialchars($celebrity->slug) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($celebrity->name) ?>
                                </a>
                            </h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Born: <?= format_date($celebrity->birth_date) ?>
                                </small>
                            </p>
                            <?php if (isset($celebrity->zodiac_sign)): ?>
                            <p class="card-text">
                                <span class="badge bg-primary"><?= htmlspecialchars($celebrity->zodiac_sign) ?></span>
                                <?php if (isset($celebrity->chinese_zodiac)): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($celebrity->chinese_zodiac) ?></span>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                            <a href="/celebrity-reports/<?= htmlspecialchars($celebrity->slug) ?>" 
                               class="btn btn-sm btn-outline-primary"
                               title="View <?= htmlspecialchars($celebrity->name) ?>'s cosmic blueprint">
                                <i class="fas fa-star me-1"></i>View Cosmic Report
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- SEO Content Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body">
                        <h2 class="h4 mb-3">About Celebrity Astrology</h2>
                        <p class="text-muted">
                            Explore the fascinating world of celebrity astrology with our comprehensive Celebrity Almanac. 
                            Discover how the stars aligned at the moment of birth for your favorite celebrities, influencing 
                            their personalities, career paths, and life journeys.
                        </p>
                        <p class="text-muted">
                            Our celebrity reports include detailed zodiac analysis, Chinese astrology insights, numerology, 
                            and cosmic significance scores. Learn what makes each celebrity's astrological profile unique 
                            and how their birth charts reflect their public personas and achievements.
                        </p>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Popular Zodiac Signs</h3>
                                <ul class="list-unstyled small">
                                    <li><a href="/celebrity-reports?zodiac=aries" class="text-decoration-none">Aries Celebrities</a></li>
                                    <li><a href="/celebrity-reports?zodiac=leo" class="text-decoration-none">Leo Celebrities</a></li>
                                    <li><a href="/celebrity-reports?zodiac=scorpio" class="text-decoration-none">Scorpio Celebrities</a></li>
                                    <li><a href="/celebrity-reports?zodiac=aquarius" class="text-decoration-none">Aquarius Celebrities</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h6 fw-bold">Celebrity Categories</h3>
                                <ul class="list-unstyled small">
                                    <li><a href="/celebrity-reports?category=actors" class="text-decoration-none">Actor Birth Charts</a></li>
                                    <li><a href="/celebrity-reports?category=musicians" class="text-decoration-none">Musician Astrology</a></li>
                                    <li><a href="/celebrity-reports?category=athletes" class="text-decoration-none">Athlete Zodiac Signs</a></li>
                                    <li><a href="/celebrity-reports?category=politicians" class="text-decoration-none">Political Figure Charts</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../app/views/layouts/footer.php'; ?>