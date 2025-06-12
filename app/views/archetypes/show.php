<?php
// app/views/archetypes/show.php

// Set the title if not already set
if (!isset($title)) {
    $title = 'Archetype Hub - CosmicHub';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<!-- SEO Meta Tags -->
<?php if ($archetype): ?>
<meta name="description" content="Discover the <?= htmlspecialchars($archetype->name) ?> archetype - cosmic personality insights, strengths, challenges, and spiritual guidance. Explore your soul's archetype and unlock deeper self-understanding.">
<meta name="keywords" content="<?= htmlspecialchars($archetype->name) ?> archetype, personality type, cosmic archetype, soul archetype, spiritual personality, <?= htmlspecialchars($archetype->name) ?> traits">
<meta property="og:title" content="<?= htmlspecialchars($archetype->name) ?> Archetype - Cosmic Personality Hub | CosmicHub">
<meta property="og:description" content="Explore the <?= htmlspecialchars($archetype->name) ?> archetype with detailed personality insights, strengths, and cosmic wisdom.">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/archetypes/<?= htmlspecialchars($archetype->slug) ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= htmlspecialchars($archetype->name) ?> Archetype Hub">
<meta name="twitter:description" content="Discover the cosmic insights and personality traits of the <?= htmlspecialchars($archetype->name) ?> archetype.">

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "<?= htmlspecialchars($archetype->name) ?> Archetype",
  "description": "<?= htmlspecialchars(substr($archetype->description ?? '', 0, 160)) ?>",
  "url": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/archetypes/<?= htmlspecialchars($archetype->slug) ?>",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?= $_SERVER['REQUEST_SCHEME'] ?>://<?= $_SERVER['HTTP_HOST'] ?>/archetypes/<?= htmlspecialchars($archetype->slug) ?>"
  },
  "author": {
    "@type": "Organization",
    "name": "CosmicHub"
  },
  "publisher": {
    "@type": "Organization",
    "name": "CosmicHub"
  },
  "datePublished": "<?= date('c', strtotime($archetype->created_at ?? 'now')) ?>",
  "dateModified": "<?= date('c', strtotime($archetype->updated_at ?? 'now')) ?>",
  "about": {
    "@type": "Thing",
    "name": "<?= htmlspecialchars($archetype->name) ?> Archetype",
    "description": "Cosmic personality archetype representing specific traits and characteristics"
  }
}
</script>
<?php endif; ?>

<div class="container mx-auto px-4 py-8">
    <?php if ($archetype): ?>
        <div class="mb-12">
            <h1 class="text-4xl font-bold text-center text-indigo-600 mb-6"><?= htmlspecialchars($archetype->name) ?></h1>
            <?php if (!empty($archetype->description)): ?>
                <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                    <p class="text-gray-700 text-lg leading-relaxed"><?= nl2br(htmlspecialchars($archetype->description)) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- AI Generated Content -->
        <?php if (!empty(\$archetype->ai_generated_content)): ?>
            <?php \$aiContent = json_decode(\$archetype->ai_generated_content, true); ?>
            <div class="bg-purple-50 dark:bg-purple-900 shadow-lg rounded-lg p-8 mb-12">
                <h2 class="text-3xl font-semibold text-purple-700 dark:text-purple-300 mb-6 text-center">Cosmic Insights for <?= htmlspecialchars(\$archetype->name) ?></h2>
                
                <?php if (isset(\$aiContent['core_essence']) && !empty(\$aiContent['core_essence'])): ?>
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-purple-600 dark:text-purple-400 mb-2">Core Essence</h3>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed"><?= nl2br(htmlspecialchars(\$aiContent['core_essence'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset(\$aiContent['key_strengths']) && !empty(\$aiContent['key_strengths'])): ?>
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-purple-600 dark:text-purple-400 mb-2">Key Strengths</h3>
                        <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                            <?php foreach (\$aiContent['key_strengths'] as \$strength): ?>
                                <li><?= htmlspecialchars(\$strength) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset(\$aiContent['potential_challenges']) && !empty(\$aiContent['potential_challenges'])): ?>
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-purple-600 dark:text-purple-400 mb-2">Potential Challenges</h3>
                        <ul class="list-disc list-inside text-gray-700 dark:text-gray-300 space-y-1">
                            <?php foreach (\$aiContent['potential_challenges'] as \$challenge): ?>
                                <li><?= htmlspecialchars(\$challenge) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (isset(\$aiContent['symbolic_representation']) && !empty(\$aiContent['symbolic_representation'])): ?>
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold text-purple-600 dark:text-purple-400 mb-2">Symbolic Representation</h3>
                        <p class="text-gray-700 dark:text-gray-300 italic leading-relaxed"><?= nl2br(htmlspecialchars(\$aiContent['symbolic_representation'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty(\$aiContent['core_essence']) && isset(\$aiContent['raw_text']) && !empty(\$aiContent['raw_text'])): ?>
                    <div class="mt-6 pt-4 border-t border-purple-200 dark:border-purple-700">
                        <h3 class="text-lg font-semibold text-purple-600 dark:text-purple-400 mb-2">Raw AI Output</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 p-3 rounded-md whitespace-pre-wrap"><?= htmlspecialchars(\$aiContent['raw_text']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Note: The AI's response could not be fully structured. Displaying raw output.</p>
                    </div>
                <?php elseif (empty(\$aiContent['core_essence']) && empty(\$aiContent['raw_text'])):
                    // This case handles when ai_generated_content is present but json_decode results in empty or non-conforming array
                ?>
                     <p class="text-gray-600 dark:text-gray-400">AI-generated insights for this archetype are currently being processed or are not available in a structured format. Please check back later.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Premium Content Gating: Share to Unlock -->

        <!-- Premium Content Gating: Share to Unlock -->
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6 mb-10">
            <h3 class="font-bold text-gray-800 dark:text-white mb-2">Unlock Full Archetype Insights</h3>
            <?php if ($hasActiveSubscription): ?>
                <div class="text-green-600 dark:text-green-400 mb-2">
                    <i class="fas fa-check-circle mr-1"></i> Unlocked! You have premium access.
                </div>
                
                <?php if (!empty($premiumArchetypeContent)): ?>
                    <div class="card border-success mt-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-star me-2"></i>Premium Archetype Insights</h5>
                        </div>
                        <div class="card-body">
                            <div class="premium-content">
                                <?= nl2br(htmlspecialchars($premiumArchetypeContent)) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-spinner fa-spin me-2"></i>Generating your premium archetype insights...
                    </div>
                <?php endif; ?>
            <?php elseif ($hasEnoughReferrals): ?>
                <div class="text-green-600 dark:text-green-400 mb-2">
                    <i class="fas fa-check-circle mr-1"></i> Unlocked! Thank you for sharing.
                </div>
                
                <?php if (!empty($premiumArchetypeContent)): ?>
                    <div class="card border-success mt-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-star me-2"></i>Premium Archetype Insights</h5>
                        </div>
                        <div class="card-body">
                            <div class="premium-content">
                                <?= nl2br(htmlspecialchars($premiumArchetypeContent)) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-spinner fa-spin me-2"></i>Generating your premium archetype insights...
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-300 mb-2">
                    Share your unique link with friends. When <?= $remainingReferrals ?> more friends join CosmicHub, you'll unlock the full detailed insights for this archetype!
                </p>
                <div class="flex items-center mt-2 mb-2">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mr-2">
                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?= (3-$remainingReferrals)/3*100 ?>%"></div>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-200 ml-2">
                        <?= 3 - $remainingReferrals ?>/3 referrals
                    </span>
                </div>
                <div class="flex items-center mt-2">
                    <input type="text" id="referralLink" value="<?= htmlspecialchars($referralUrl) ?>" readonly class="w-full px-3 py-2 border rounded-l focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button onclick="copyReferralLink()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-r">Copy Link</button>
                </div>
                <button onclick="shareArchetype()" class="mt-3 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-share-alt mr-1"></i> Share Archetype
                </button>
                <div class="mt-2 text-sm text-gray-500">Or <a href="/subscription" class="text-indigo-600 hover:text-indigo-800">upgrade to premium</a> for instant access.</div>
            <?php endif; ?>
        </div>
        <script>
        function copyReferralLink() {
            var copyText = document.getElementById("referralLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand("copy");
            alert("Referral link copied to clipboard!");
        }
        function shareArchetype() {
            var url = document.getElementById("referralLink").value;
            var title = "Unlock this Archetype on CosmicHub!";
            var text = "Check out the " + title + " and join the community.";
            if (navigator.share) {
                navigator.share({ title: title, text: text, url: url });
            } else {
                copyReferralLink();
            }
        }
        </script>

        <?php if (!empty($famousPeople)): ?>
            <div class="bg-white shadow-lg rounded-lg p-8 mb-12">
                <h2 class="text-2xl font-semibold text-indigo-700 mb-6">Famous People with this Archetype</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($famousPeople as $person): ?>
                        <div class="p-4 border rounded-lg hover:shadow-md transition-shadow duration-300">
                            <h3 class="text-xl font-medium text-gray-800"><?= htmlspecialchars($person->name) ?></h3>
                            <?php if (!empty($person->description)): ?>
                                <p class="text-gray-600 mt-2"><?= htmlspecialchars($person->description) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Comments Section -->
        <div class="bg-white shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-semibold text-indigo-700 mb-6">Community Discussion</h2>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Comment Form -->
                <form action="/archetypes/<?= htmlspecialchars($archetype->slug) ?>/comment" method="POST" class="mb-8">
                    <div class="mb-4">
                        <label for="comment" class="block text-gray-700 text-sm font-bold mb-2">Share your thoughts:</label>
                        <textarea
                            name="comment"
                            id="comment"
                            rows="4"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required
                        ></textarea>
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-300">
                        Submit Comment
                    </button>
                    <p class="text-sm text-gray-500 mt-2">Comments are moderated and will appear once approved.</p>
                </form>
            <?php else: ?>
                <div class="bg-gray-100 rounded-lg p-4 mb-8">
                    <p class="text-gray-700">Please <a href="/login" class="text-indigo-600 hover:text-indigo-800">log in</a> to join the discussion.</p>
                </div>
            <?php endif; ?>

            <!-- Display Comments -->
            <?php if (!empty($comments)): ?>
                <div class="space-y-6">
                    <?php foreach ($comments as $comment): ?>
                        <div class="border-b pb-4">
                            <div class="flex items-center mb-2">
                                <span class="font-medium text-gray-800"><?= htmlspecialchars($comment->user->name) ?></span>
                                <span class="text-gray-500 text-sm ml-2"><?= date('M j, Y', strtotime($comment->created_at)) ?></span>
                            </div>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($comment->comment)) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No comments yet. Be the first to share your thoughts!</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Archetype Not Found</h1>
            <p class="text-gray-600 mb-8">The archetype you're looking for doesn't exist or may have been moved.</p>
            <a href="/archetypes" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-300">
                View All Archetypes
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>