<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="flex-shrink-0 flex items-center">
                    <i class="fas fa-hands-helping text-blue-500 text-2xl mr-2"></i>
                    <span class="font-bold text-xl text-gray-800">ServeRight</span>
                </a>
                <div class="hidden md:ml-6 md:flex md:space-x-8">
                    <a href="index.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Home</a>
                    <a href="services.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Services</a>
                    <a href="delivery.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Delivery</a>
                    <a href="bookings.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">My Bookings</a>
                </div>
            </div>
            <div class="flex items-center">
                <div class="hidden md:ml-4 md:flex md:items-center space-x-3">
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 text-sm font-medium">Dashboard</a>
                        <a href="profile.php" class="text-gray-700 hover:text-blue-600 text-sm font-medium">Profile</a>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Logout</a>
                    <?php else: ?>
                        <a href="signin.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">Sign In</a>
                        <a href="signup.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium">Sign Up</a>
                    <?php endif; ?>
                </div>
                <div class="-mr-2 flex items-center md:hidden">
                    <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                        <span class="sr-only">Open main menu</span>
                        <i class="fas fa-bars h-6 w-6"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu -->
    <div id="mobile-menu" class="md:hidden hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t">
            <a href="index.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium">Home</a>
            <a href="services.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium">Services</a>
            <a href="delivery.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium">Delivery</a>
            <a href="bookings.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium">My Bookings</a>
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="flex flex-col space-y-2 px-5">
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 text-base font-medium">Dashboard</a>
                        <a href="profile.php" class="text-gray-600 hover:text-blue-600 text-base font-medium">Profile</a>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium text-center">Logout</a>
                    <?php else: ?>
                        <a href="signin.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium text-center">Sign In</a>
                        <a href="signup.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium text-center">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });
</script>