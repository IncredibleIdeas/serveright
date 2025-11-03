<?php 
require_once 'config.php';

if (!is_logged_in()) {
    redirect('signin.php');
}

$user_id = $_SESSION['user_id'];
$conn = get_db_connection();

// Get user's recent bookings
$bookings_sql = "SELECT sb.*, s.name as service_name, s.price 
                 FROM service_bookings sb 
                 JOIN services s ON sb.service_id = s.id 
                 WHERE sb.user_id = ? 
                 ORDER BY sb.created_at DESC 
                 LIMIT 5";
$stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_bookings = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get user's recent delivery orders
$orders_sql = "SELECT do.*, di.name as item_name, di.price 
               FROM delivery_orders do 
               JOIN delivery_items di ON do.item_id = di.id 
               WHERE do.user_id = ? 
               ORDER BY do.created_at DESC 
               LIMIT 5";
$stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$recent_orders = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ServeRight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Dashboard Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo $_SESSION['user_name']; ?>!</h1>
                    <p class="text-gray-600">Here's what's happening with your services and deliveries.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="services.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Book Service
                    </a>
                    <a href="delivery.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                        <i class="fas fa-shopping-cart mr-2"></i>Order Delivery
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Content -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Service Bookings -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Service Bookings</h2>
                        <a href="bookings.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">View All</a>
                    </div>
                    
                    <?php if (empty($recent_bookings)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-600">No service bookings yet</p>
                            <a href="services.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium mt-2 inline-block">
                                Book your first service
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $booking['service_name']; ?></h3>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> at <?php echo $booking['booking_time']; ?>
                                        </p>
                                        <span class="inline-block mt-1 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                   ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($booking['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">$<?php echo $booking['price']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Delivery Orders -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Delivery Orders</h2>
                        <a href="orders.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">View All</a>
                    </div>
                    
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-shopping-bag text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-600">No delivery orders yet</p>
                            <a href="delivery.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium mt-2 inline-block">
                                Place your first order
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h3 class="font-medium text-gray-900"><?php echo $order['item_name']; ?></h3>
                                        <p class="text-sm text-gray-600">
                                            Qty: <?php echo $order['quantity']; ?> • 
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        </p>
                                        <span class="inline-block mt-1 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $order['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                   ($order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($order['status'] === 'delivered' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-gray-900">$<?php echo $order['total_amount']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
                <a href="services.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-tools text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Book a Service</h3>
                    <p class="text-sm text-gray-600 mt-1">Schedule home services</p>
                </a>
                
                <a href="delivery.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Order Delivery</h3>
                    <p class="text-sm text-gray-600 mt-1">Get items delivered</p>
                </a>
                
                <a href="bookings.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-calendar text-purple-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">My Bookings</h3>
                    <p class="text-sm text-gray-600 mt-1">View service bookings</p>
                </a>
                
                <a href="profile.php" class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition duration-300 text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-lg mx-auto mb-4">
                        <i class="fas fa-user text-orange-600 text-xl"></i>
                    </div>
                    <h3 class="font-medium text-gray-900">Profile</h3>
                    <p class="text-sm text-gray-600 mt-1">Manage account</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>