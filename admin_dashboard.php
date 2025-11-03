<?php 
require_once 'config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('signin.php');
}

$conn = get_db_connection();

// Get statistics
$users_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$services_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM services WHERE is_active = TRUE")->fetch_assoc()['count'];
$bookings_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM service_bookings")->fetch_assoc()['count'];
$orders_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM delivery_orders")->fetch_assoc()['count'];

// Recent bookings
$recent_bookings_sql = "SELECT sb.*, s.name as service_name, u.full_name as user_name 
                        FROM service_bookings sb 
                        JOIN services s ON sb.service_id = s.id 
                        JOIN users u ON sb.user_id = u.id 
                        ORDER BY sb.created_at DESC 
                        LIMIT 5";
$recent_bookings = mysqli_query($conn, $recent_bookings_sql)->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recent_orders_sql = "SELECT do.*, di.name as item_name, u.full_name as user_name 
                      FROM delivery_orders do 
                      JOIN delivery_items di ON do.item_id = di.id 
                      JOIN users u ON do.user_id = u.id 
                      ORDER BY do.created_at DESC 
                      LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_sql)->fetch_all(MYSQLI_ASSOC);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ServeRight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-hands-helping text-blue-500 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-800">ServeRight Admin</span>
                    </a>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="admin_dashboard.php" class="text-blue-600 border-b-2 border-blue-600 inline-flex items-center px-1 pt-1 text-sm font-medium">Dashboard</a>
                        <a href="admin_users.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Users</a>
                        <a href="admin_services.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Services</a>
                        <a href="admin_orders.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Orders</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-4 md:flex md:items-center space-x-3">
                        <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 text-sm font-medium">User View</a>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Admin Dashboard Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="text-gray-600 mt-2">Welcome back, <?php echo $_SESSION['user_name']; ?>!</p>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $users_count; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mr-4">
                            <i class="fas fa-tools text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Services</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $services_count; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mr-4">
                            <i class="fas fa-calendar text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Service Bookings</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $bookings_count; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg mr-4">
                            <i class="fas fa-shopping-bag text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Delivery Orders</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $orders_count; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Activity -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Bookings -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Service Bookings</h2>
                    <?php if (empty($recent_bookings)): ?>
                        <p class="text-gray-600 text-center py-4">No recent bookings</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $booking['service_name']; ?></h3>
                                        <p class="text-sm text-gray-600">by <?php echo $booking['user_name']; ?></p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> at <?php echo $booking['booking_time']; ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                               ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($booking['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Delivery Orders</h2>
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-gray-600 text-center py-4">No recent orders</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $order['item_name']; ?></h3>
                                        <p class="text-sm text-gray-600">by <?php echo $order['user_name']; ?></p>
                                        <p class="text-xs text-gray-500">
                                            Qty: <?php echo $order['quantity']; ?> • $<?php echo $order['total_amount']; ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $order['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                               ($order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($order['status'] === 'delivered' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="admin_services.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-plus text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Add Service</h3>
                    <p class="text-sm text-gray-600 mt-1">Create new service</p>
                </a>
                
                <a href="admin_users.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-user-cog text-green-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Manage Users</h3>
                    <p class="text-sm text-gray-600 mt-1">View all users</p>
                </a>
                
                <a href="admin_orders.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-shopping-bag text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Manage Orders</h3>
                    <p class="text-sm text-gray-600 mt-1">View all orders</p>
                </a>
                
                <a href="admin_bookings.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-calendar-check text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Manage Bookings</h3>
                    <p class="text-sm text-gray-600 mt-1">View all bookings</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>