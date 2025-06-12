<?php
// app/views/archetypes/create.php

if (!isset(\$title)) {
    \$title = 'Create New Archetype - CosmicHub';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center text-indigo-600 mb-10">Create New Archetype</h1>

    <?php if (isset(\$_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded-md <?php echo \$_SESSION['flash_message']['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
            <?php echo htmlspecialchars(\$_SESSION['flash_message']['message']); ?>
            <?php unset(\$_SESSION['flash_message']); ?>
        </div>
    <?php endif; ?>

    <form action="/archetypes/store" method="POST" class="max-w-lg mx-auto bg-white shadow-lg rounded-lg p-8">
        <div class="mb-6">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Archetype Name:</label>
            <input type="text" name="name" id="name" required 
                   class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description:</label>
            <textarea name="description" id="description" rows="6" required
                      class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
        </div>

        <div class="flex items-center justify-center">
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-300 ease-in-out transform hover:scale-105">
                Create Archetype
            </button>
        </div>
    </form>

</div>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>