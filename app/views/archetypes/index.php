<?php
// app/views/archetypes/index.php

// Set the title if not already set
if (!isset($title)) {
    $title = 'Archetype Hubs - CosmicHub';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<!-- SEO Meta Tags -->
<meta name="description" content="Explore cosmic archetypes and personality insights. Discover your soul's archetype through our comprehensive archetype hub featuring detailed personality analysis and cosmic wisdom.">
<meta name="keywords" content="archetypes, personality types, cosmic archetypes, soul archetype, personality analysis, astrology archetypes, spiritual personality">
<meta property="og:title" content="Archetype Hubs - Discover Your Cosmic Personality | CosmicHub">
<meta property="og:description" content="Explore cosmic archetypes and discover your soul's personality type. Comprehensive archetype analysis and spiritual insights.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/archetypes">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="Archetype Hubs - Cosmic Personality Types">
<meta name="twitter:description" content="Discover your cosmic archetype and explore personality insights through our comprehensive archetype hub.">

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "Archetype Hubs",
  "description": "Explore cosmic archetypes and personality insights through our comprehensive archetype collection.",
  "url": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/archetypes",
  "mainEntity": {
    "@type": "ItemList",
    "numberOfItems": "<?= isset($archetypes) ? count($archetypes) : 0 ?>",
    "itemListElement": [
      <?php if (isset($archetypes) && !empty($archetypes)): ?>
        <?php foreach ($archetypes as $index => $archetype): ?>
        {
          "@type": "ListItem",
          "position": <?= $index + 1 ?>,
          "item": {
            "@type": "Thing",
            "name": "<?= htmlspecialchars($archetype->name) ?>",
            "description": "<?= htmlspecialchars(substr($archetype->description ?? '', 0, 150)) ?>",
            "url": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/archetypes/<?= htmlspecialchars($archetype->slug) ?>"
          }
        }<?= $index < count($archetypes) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      <?php endif; ?>
    ]
  }
}
</script>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center text-indigo-600 mb-10">Explore the Archetype Hubs</h1>

    <?php if (isset($archetypes) && !empty($archetypes)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($archetypes as $archetype): ?>
                <div class="bg-white shadow-lg rounded-lg p-6 hover:shadow-xl transition-shadow duration-300">
                    <h2 class="text-2xl font-semibold text-indigo-700 mb-3">
                        <a href="/archetypes/<?= htmlspecialchars($archetype->slug) ?>" class="hover:underline">
                            <?= htmlspecialchars($archetype->name) ?>
                        </a>
                    </h2>
                    <?php if (!empty($archetype->description)):\ ?>
                        <p class="text-gray-600 mb-4"><?= nl2br(htmlspecialchars(substr($archetype->description, 0, 150))) ?>...</p>
                    <?php endif; ?>
                    <a href="/archetypes/<?= htmlspecialchars($archetype->slug) ?>" class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-300">Explore <?= htmlspecialchars($archetype->name) ?> Hub &rarr;</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-center text-gray-500 text-xl">No archetypes are currently available. Please check back later.</p>
    <?php endif; ?>

</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>