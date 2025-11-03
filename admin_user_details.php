<?php 
require_once 'config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('signin.php');
}

if (!isset($_GET['id'])) {
    redirect('admin_users.php');
}

$user_id = intval($_GET['id']);
$conn = get_db_connection();

// Get user details
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_stmt_get_result($user_stmt)->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "User not found";
    redirect('admin_users.php');
}

// Get user's service bookings
$bookings_sql = "SELECT sb.*, s.name as service_name 
                 FROM service_bookings sb 
                 JOIN services s ON sb.service_id = s.id 
                 WHERE sb.user_id = ? 
                 ORDER BY sb.created_at DESC 
                 LIMIT 10";
$bookings_stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($bookings_stmt, "i", $user_id);
mysqli_stmt_execute($bookings_stmt);
$bookings = mysqli_stmt_get_result($bookings_stmt)->fetch_all(MYSQLI_ASSOC);

// Get user's delivery orders
$orders_sql = "SELECT do.*, di.name as item_name 
               FROM delivery_orders do 
               JOIN delivery_items di ON do.item_id = di.id 
               WHERE do.user_id = ? 
               ORDER BY do.created_at DESC 
               LIMIT 10";
$orders_stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($orders_stmt, "i", $user_id);
mysqli_stmt_execute($orders_stmt);
$orders = mysqli_stmt_get_result($orders_stmt)->fetch_all(MYSQLI_ASSOC);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - ServeRight Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Admin Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <i class="fas fa-hands-helping text-blue-500 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-800">ServeRight Admin</span>
                    </a>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-4 md:flex md:items-center space-x-3">
                        <a href="admin_users.php" class="text-gray-700 hover:text-blue-600 text-sm font-medium">Back to Users</a>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">User Details</h1>
                    <p class="text-gray-600 mt-2">Detailed information about <?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        User ID: <?php echo $user['id']; ?>
                    </span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?php echo $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                        <?php echo ucfirst($user['user_type']); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- User Information -->
    <section class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Profile -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Account Type</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo ucfirst($user['user_type']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Member Since</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Service Bookings -->
                <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Service Bookings</h2>
                    <?php if (empty($bookings)): ?>
                        <p class="text-gray-600 text-center py-4">No service bookings found</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Service</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo $booking['service_name']; ?></td>
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?><br>
                                                <span class="text-gray-500 text-xs"><?php echo $booking['booking_time']; ?></span>
                                            </td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                           ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                           ($booking['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900">$<?php echo $booking['total_amount']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Delivery Orders -->
                <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-3">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Delivery Orders</h2>
                    <?php if (empty($orders)): ?>
                        <p class="text-gray-600 text-center py-4">No delivery orders found</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Delivery Time</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo $order['item_name']; ?></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo $order['quantity']; ?></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo $order['delivery_time']; ?></td>
                                            <td class="px-4 py-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                                           ($order['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                                                           ($order['status'] === 'out_for_delivery' ? 'bg-yellow-100 text-yellow-800' : 
                                                           ($order['status'] === 'pending' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'))); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900">$<?php echo $order['total_amount']; ?></td>
                                            <td class="px-4 py-2 text-sm text-gray-900"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>