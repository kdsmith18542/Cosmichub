<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'CosmicHub.Online'); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-xl font-bold text-indigo-600">CosmicHub</a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900">Dashboard</a>
                        <a href="/reports" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900">My Reports</a>
                        <a href="/celebrity-reports" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900">Celebrity Almanac</a>
                        <a href="/archetypes" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900">Archetype Hubs</a>
                        <a href="/credits" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900">Credits</a>
                        <a href="/daily-vibe" class="px-3 py-2 rounded-md text-sm font-medium text-indigo-600 hover:text-indigo-800 font-semibold">
                            <i class="fas fa-moon-stars mr-1"></i> Daily Vibe
                        </a>
                        <a href="/compatibility" class="px-3 py-2 rounded-md text-sm font-medium text-pink-600 hover:text-pink-800 font-semibold">
                            <i class="fas fa-heart mr-1"></i> Compatibility
                        </a>
                        <a href="/rarity-score" class="px-3 py-2 rounded-md text-sm font-medium text-purple-600 hover:text-purple-800 font-semibold">
                            <i class="fas fa-star mr-1"></i> Rarity Score
                        </a>
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" class="bg-white rounded-full flex text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <span class="text-indigo-600 font-medium">
                                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                                        </span>
                                    </div>
                                </button>
                            </div>
                            <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu">
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                                <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Settings</a>
                                <a href="/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Sign out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900">Sign in</a>
                        <a href="/register" class="ml-4 px-3 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Sign up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main>
        <!-- Page content will be inserted here -->
        <?php if (isset($flash)): ?>
            <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                <div class="p-4 rounded-md bg-green-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                <?php echo htmlspecialchars($flash); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
