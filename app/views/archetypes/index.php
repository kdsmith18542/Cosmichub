<?php
// app/views/archetypes/index.php

// Set the title if not already set
if (!isset($title)) {
    $title = 'Archetype Hubs - CosmicHub';
}

include_once __DIR__ . '/../layouts/header.php';
?>

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