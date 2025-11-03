<?php 
require_once 'config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('signin.php');
}

$conn = get_db_connection();

// Handle order actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    $valid_statuses = ['pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'];
    
    if (in_array($action, $valid_statuses)) {
        $update_sql = "UPDATE delivery_orders SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $action, $order_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success'] = "Order status updated to " . ucfirst(str_replace('_', ' ', $action));
        } else {
            $_SESSION['error'] = "Failed to update order status";
        }
    }
    
    redirect('admin_orders.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';

// Build query
$sql = "SELECT do.*, di.name as item_name, di.price, dc.name as category_name, 
               u.full_name as customer_name, u.email as customer_email 
        FROM delivery_orders do 
        JOIN delivery_items di ON do.item_id = di.id 
        JOIN delivery_categories dc ON di.category_id = dc.id 
        JOIN users u ON do.user_id = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (di.name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR do.delivery_address LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($status) && $status !== 'all') {
    $sql .= " AND do.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(do.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $sql .= " AND DATE(do.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY do.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_orders = count($orders);
$pending_orders = array_filter($orders, function($order) {
    return $order['status'] === 'pending';
});
$delivered_orders = array_filter($orders, function($order) {
    return $order['status'] === 'delivered';
});
$total_revenue = array_sum(array_column($orders, 'total_amount'));

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - ServeRight Admin</title>
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
                    <div class="hidden md:ml-6 md:flex md:space-x-8">
                        <a href="admin_dashboard.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Dashboard</a>
                        <a href="admin_users.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Users</a>
                        <a href="admin_services.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Services</a>
                        <a href="admin_orders.php" class="text-blue-600 border-b-2 border-blue-600 inline-flex items-center px-1 pt-1 text-sm font-medium">Orders</a>
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

    <!-- Page Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manage Delivery Orders</h1>
                    <p class="text-gray-600 mt-2">Track and manage all delivery orders</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        Revenue: $<?php echo number_format($total_revenue, 2); ?>
                    </span>
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        Total: <?php echo $total_orders; ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg mr-4">
                            <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Orders</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_orders; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-lg mr-4">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($pending_orders); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mr-4">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Delivered</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($delivered_orders); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-lg mr-4">
                            <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                            <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Search and Filter -->
    <section class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search orders by item, customer, or address..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="out_for_delivery" <?php echo $status === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <input type="date" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="From Date">
                    </div>
                    
                    <div>
                        <input type="date" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="To Date">
                    </div>
                    
                    <div class="md:col-span-5 flex space-x-2">
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300">
                            Apply Filters
                        </button>
                        <a href="admin_orders.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300 flex items-center">
                            <i class="fas fa-refresh mr-2"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Orders Table -->
    <section class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-shopping-bag text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900">No orders found</h3>
                        <p class="text-gray-600 mt-2">Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['item_name']); ?></div>
                                            <div class="text-sm text-gray-500">Qty: <?php echo $order['quantity']; ?></div>
                                            <div class="text-xs text-gray-400">Order #<?php echo $order['id']; ?></div>
                                            <div class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs">
                                                <?php echo htmlspecialchars(substr($order['delivery_address'], 0, 50)); ?><?php echo strlen($order['delivery_address']) > 50 ? '...' : ''; ?>
                                            </div>
                                            <?php if (!empty($order['delivery_instructions'])): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Note: <?php echo htmlspecialchars(substr($order['delivery_instructions'], 0, 30)); ?><?php echo strlen($order['delivery_instructions']) > 30 ? '...' : ''; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text-xs text-gray-400 mt-1">
                                                Time: <?php echo $order['delivery_time']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            $<?php echo $order['total_amount']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                                       ($order['status'] === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                                                       ($order['status'] === 'out_for_delivery' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($order['status'] === 'pending' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'))); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex flex-col space-y-1">
                                                <?php if ($order['status'] !== 'confirmed'): ?>
                                                    <a href="admin_orders.php?action=confirmed&id=<?php echo $order['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900 text-xs">
                                                        Confirm
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($order['status'] !== 'out_for_delivery' && $order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                                    <a href="admin_orders.php?action=out_for_delivery&id=<?php echo $order['id']; ?>" 
                                                       class="text-yellow-600 hover:text-yellow-900 text-xs">
                                                        Out for Delivery
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                                    <a href="admin_orders.php?action=delivered&id=<?php echo $order['id']; ?>" 
                                                       class="text-green-600 hover:text-green-900 text-xs">
                                                        Mark Delivered
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($order['status'] !== 'cancelled'): ?>
                                                    <a href="admin_orders.php?action=cancelled&id=<?php echo $order['id']; ?>" 
                                                       class="text-red-600 hover:text-red-900 text-xs"
                                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                                        Cancel
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>