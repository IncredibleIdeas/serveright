<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServeRight - Home Services & Delivery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #10b981;
            --accent: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .service-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-hands-helping text-blue-500 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-800">ServeRight</span>
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="index.php" class="text-blue-600 border-b-2 border-blue-600 inline-flex items-center px-1 pt-1 text-sm font-medium">Home</a>
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
                <a href="index.php" class="bg-blue-50 text-blue-600 block px-3 py-2 rounded-md text-base font-medium">Home</a>
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

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-16 md:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="md:w-1/2">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight">Professional Home Services & Fast Delivery</h1>
                    <p class="mt-4 text-xl text-blue-100">Book trusted professionals for home services and get anything delivered right to your doorstep.</p>
                    <div class="mt-8 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                        <a href="services.php" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition duration-300 text-center">Book a Service</a>
                        <a href="delivery.php" class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300 text-center">Request Delivery</a>
                    </div>
                </div>
                <div class="md:w-1/2 mt-10 md:mt-0">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=400&q=80" alt="Service Professional" class="rounded-lg shadow-xl w-full">
                </div>
            </div>
        </div>
    </section>

    <!-- Services Preview -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Our Services</h2>
                <p class="mt-4 text-lg text-gray-600">Professional services for your home, scheduled at your convenience.</p>
            </div>
            
            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Service Cards -->
                <?php
                $conn = get_db_connection();
                $sql = "SELECT * FROM services WHERE is_active = TRUE LIMIT 4";
                $result = mysqli_query($conn, $sql);
                
                while ($service = mysqli_fetch_assoc($result)):
                ?>
                <div class="service-card bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                            <i class="fas fa-tools text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900"><?php echo $service['name']; ?></h3>
                        <p class="mt-2 text-gray-600"><?php echo $service['description']; ?></p>
                        <a href="services.php" class="mt-4 text-blue-600 font-medium flex items-center">
                            Learn more <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
                <?php endwhile; mysqli_close($conn); ?>
            </div>
            
            <div class="text-center mt-12">
                <a href="services.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition duration-300">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Delivery Preview -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">Hyperlocal Delivery</h2>
                <p class="mt-4 text-lg text-gray-600">Get anything delivered from local stores in under an hour.</p>
            </div>
            
            <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2">
                <div>
                    <img src="https://images.unsplash.com/photo-1581497396206-ee5e29a6d7d3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=400&q=80" alt="Delivery Service" class="rounded-lg shadow-md w-full">
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">What We Deliver</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-blue-100 text-blue-600">
                                    <i class="fas fa-pills"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">Pharmacy Items</h4>
                                <p class="mt-1 text-gray-600">Prescriptions and over-the-counter medications delivered safely.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-green-100 text-green-600">
                                    <i class="fas fa-gift"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">Gifts & Flowers</h4>
                                <p class="mt-1 text-gray-600">Surprise your loved ones with thoughtful gifts delivered same-day.</p>
                            </div>
                        </div>
                    </div>
                    
                    <a href="delivery.php" class="mt-6 w-full bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-md font-medium transition duration-300 block text-center">
                        Order Delivery Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 gradient-bg text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold">Ready to Get Started?</h2>
            <p class="mt-4 text-xl text-blue-100">Join thousands of satisfied customers who trust ServeRight.</p>
            <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="signup.php" class="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition duration-300 text-center">Sign Up Now</a>
                <a href="services.php" class="bg-green-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition duration-300 text-center">Book a Service</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center">
                        <i class="fas fa-hands-helping text-blue-400 text-2xl mr-2"></i>
                        <span class="font-bold text-xl">ServeRight</span>
                    </div>
                    <p class="mt-4 text-gray-400">Professional home services and hyperlocal delivery.</p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold">Services</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="services.php" class="text-gray-400 hover:text-white">Handyman</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white">Cleaning</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white">Pet Care</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white">Car Wash</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold">Delivery</h3>
                    <ul class="mt-4 space-y-2">
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Pharmacy</a></li>
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Groceries</a></li>
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Gifts & Flowers</a></li>
                        <li><a href="delivery.php" class="text-gray-400 hover:text-white">Food</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold">Contact</h3>
                    <ul class="mt-4 space-y-2">
                        <li class="text-gray-400">support@serveright.com</li>
                        <li class="text-gray-400">1-800-SERVE-RIGHT</li>
                        <li class="text-gray-400">Mon-Sun: 7am-10pm</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-700 text-center text-gray-400">
                <p>&copy; 2025 ServeRight. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>