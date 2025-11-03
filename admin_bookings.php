<?php 
require_once 'config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('signin.php');
}

$conn = get_db_connection();

// Handle booking actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    
    if (in_array($action, $valid_statuses)) {
        $update_sql = "UPDATE service_bookings SET status = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "si", $action, $booking_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['success'] = "Booking status updated to " . ucfirst($action);
        } else {
            $_SESSION['error'] = "Failed to update booking status";
        }
    } elseif ($action === 'delete') {
        $delete_sql = "DELETE FROM service_bookings WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $booking_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $_SESSION['success'] = "Booking deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete booking";
        }
    }
    
    redirect('admin_bookings.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';

// Build query
$sql = "SELECT sb.*, s.name as service_name, s.price, 
               u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone 
        FROM service_bookings sb 
        JOIN services s ON sb.service_id = s.id 
        JOIN users u ON sb.user_id = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR sb.special_requests LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($status) && $status !== 'all') {
    $sql .= " AND sb.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($service_id > 0) {
    $sql .= " AND sb.service_id = ?";
    $params[] = $service_id;
    $types .= "i";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(sb.booking_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $sql .= " AND DATE(sb.booking_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY sb.booking_date DESC, sb.booking_time DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get services for filter
$services_sql = "SELECT id, name FROM services WHERE is_active = TRUE ORDER BY name";
$services_result = mysqli_query($conn, $services_sql);
$services_list = mysqli_fetch_all($services_result, MYSQLI_ASSOC);

// Get statistics
$total_bookings = count($bookings);
$pending_bookings = array_filter($bookings, function($booking) {
    return $booking['status'] === 'pending';
});
$confirmed_bookings = array_filter($bookings, function($booking) {
    return $booking['status'] === 'confirmed';
});
$completed_bookings = array_filter($bookings, function($booking) {
    return $booking['status'] === 'completed';
});
$total_revenue = array_sum(array_column($bookings, 'total_amount'));

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - ServeRight Admin</title>
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
                        <a href="admin_orders.php" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 text-sm font-medium">Orders</a>
                        <a href="admin_bookings.php" class="text-blue-600 border-b-2 border-blue-600 inline-flex items-center px-1 pt-1 text-sm font-medium">Bookings</a>
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
                    <h1 class="text-2xl font-bold text-gray-900">Manage Service Bookings</h1>
                    <p class="text-gray-600 mt-2">View and manage all service bookings</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        Revenue: $<?php echo number_format($total_revenue, 2); ?>
                    </span>
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        Total: <?php echo $total_bookings; ?>
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
                            <i class="fas fa-calendar text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_bookings; ?></p>
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
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($pending_bookings); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mr-4">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Confirmed</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo count($confirmed_bookings); ?></p>
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
                            <input type="text" name="search" placeholder="Search bookings by service, customer, or special requests..." 
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
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <select name="service_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="0">All Services</option>
                            <?php foreach ($services_list as $service): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo $service_id == $service['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="flex items-end space-x-2">
                            <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300">
                                Apply Filters
                            </button>
                            <a href="admin_bookings.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300 flex items-center">
                                <i class="fas fa-refresh"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Bookings Table -->
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

                <?php if (empty($bookings)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-calendar text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900">No bookings found</h3>
                        <p class="text-gray-600 mt-2">Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service & Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($bookings as $booking): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">Booking #<?php echo $booking['id']; ?></div>
                                            <?php if (!empty($booking['special_requests'])): ?>
                                                <div class="text-sm text-gray-600 mt-1 max-w-xs">
                                                    <strong>Requests:</strong> <?php echo htmlspecialchars(substr($booking['special_requests'], 0, 50)); ?><?php echo strlen($booking['special_requests']) > 50 ? '...' : ''; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Created: <?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                            <?php if (!empty($booking['customer_phone'])): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                                            <div class="text-sm text-gray-600">$<?php echo $booking['price']; ?>/hour</div>
                                            <div class="text-sm font-bold text-green-600">Total: $<?php echo $booking['total_amount']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></div>
                                            <div class="text-sm text-gray-600"><?php echo $booking['booking_time']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                                       ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($booking['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex flex-col space-y-1">
                                                <!-- Status Update Actions -->
                                                <?php if ($booking['status'] !== 'confirmed'): ?>
                                                    <a href="admin_bookings.php?action=confirmed&id=<?php echo $booking['id']; ?>" 
                                                       class="text-green-600 hover:text-green-900 text-xs">
                                                        <i class="fas fa-check mr-1"></i>Confirm
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['status'] !== 'completed' && $booking['status'] !== 'cancelled'): ?>
                                                    <a href="admin_bookings.php?action=completed&id=<?php echo $booking['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900 text-xs">
                                                        <i class="fas fa-flag-checkered mr-1"></i>Complete
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['status'] !== 'cancelled'): ?>
                                                    <a href="admin_bookings.php?action=cancelled&id=<?php echo $booking['id']; ?>" 
                                                       class="text-red-600 hover:text-red-900 text-xs"
                                                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                        <i class="fas fa-times mr-1"></i>Cancel
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <!-- Delete Action -->
                                                <a href="admin_bookings.php?action=delete&id=<?php echo $booking['id']; ?>" 
                                                   class="text-gray-600 hover:text-gray-900 text-xs"
                                                   onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.')">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </a>
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