    </main>
    <footer class="bg-white mt-12">
        <div class="max-w-7xl mx-auto py-12 px-4 overflow-hidden sm:px-6 lg:px-8">
            <nav class="-mx-5 -my-2 flex flex-wrap justify-center" aria-label="Footer">
                <div class="px-5 py-2">
                    <a href="/about" class="text-base text-gray-500 hover:text-gray-900">About</a>
                </div>
                <div class="px-5 py-2">
                    <a href="/pricing" class="text-base text-gray-500 hover:text-gray-900">Pricing</a>
                </div>
                <div class="px-5 py-2">
                    <a href="/contact" class="text-base text-gray-500 hover:text-gray-900">Contact</a>
                </div>
                <div class="px-5 py-2">
                    <a href="/terms" class="text-base text-gray-500 hover:text-gray-900">Terms</a>
                </div>
                <div class="px-5 py-2">
                    <a href="/privacy" class="text-base text-gray-500 hover:text-gray-900">Privacy</a>
                </div>
            </nav>
            <p class="mt-8 text-center text-base text-gray-400">
                &copy; <?php echo date('Y'); ?> CosmicHub.Online. All rights reserved.
            </p>
        </div>
    </footer>
    
    <!-- JavaScript for dropdown menu -->
    <script>
        // Toggle dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuButton = document.getElementById('user-menu');
            if (userMenuButton) {
                const userMenu = userMenuButton.nextElementSibling;
                userMenuButton.addEventListener('click', function() {
                    userMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            
            // Flash message auto-hide
            const flashMessage = document.querySelector('.bg-green-50');
            if (flashMessage) {
                setTimeout(() => {
                    flashMessage.style.transition = 'opacity 1s';
                    flashMessage.style.opacity = '0';
                    setTimeout(() => flashMessage.remove(), 1000);
                }, 5000);
            }
        });
    </script>
</body>
</html>
