<?php 
require_once 'config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('signin.php');
}

$conn = get_db_connection();

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'delete':
            // Don't allow deleting admin users
            $check_sql = "SELECT user_type FROM users WHERE id = ?";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "i", $user_id);
            mysqli_stmt_execute($check_stmt);
            $user = mysqli_stmt_get_result($check_stmt)->fetch_assoc();
            
            if ($user && $user['user_type'] !== 'admin') {
                $delete_sql = "DELETE FROM users WHERE id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_sql);
                mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                if (mysqli_stmt_execute($delete_stmt)) {
                    $_SESSION['success'] = "User deleted successfully";
                } else {
                    $_SESSION['error'] = "Failed to delete user";
                }
            } else {
                $_SESSION['error'] = "Cannot delete admin users";
            }
            break;
            
        case 'toggle_status':
            // In a real app, you might want to implement user status toggle
            $_SESSION['info'] = "User status toggle feature coming soon";
            break;
    }
    
    redirect('admin_users.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$user_type = isset($_GET['user_type']) ? sanitize_input($_GET['user_type']) : '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

if (!empty($user_type)) {
    $sql .= " AND user_type = ?";
    $params[] = $user_type;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

$total_users = count($users);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - ServeRight Admin</title>
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
                        <a href="admin_users.php" class="text-blue-600 border-b-2 border-blue-600 inline-flex items-center px-1 pt-1 text-sm font-medium">Users</a>
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

    <!-- Page Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
                    <p class="text-gray-600 mt-2">View and manage all system users</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        Total: <?php echo $total_users; ?> users
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Search and Filter -->
    <section class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search users by name, email, or phone..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <select name="user_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Types</option>
                            <option value="customer" <?php echo $user_type === 'customer' ? 'selected' : ''; ?>>Customers</option>
                            <option value="admin" <?php echo $user_type === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        </select>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300">
                            Apply Filters
                        </button>
                        <a href="admin_users.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300 flex items-center">
                            <i class="fas fa-refresh mr-2"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Users Table -->
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
                
                <?php if (isset($_SESSION['info'])): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3">
                        <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($users)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-users text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900">No users found</h3>
                        <p class="text-gray-600 mt-2">Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                        <i class="fas fa-user text-blue-600"></i>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?php echo $user['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="admin_user_details.php?id=<?php echo $user['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye mr-1"></i>View
                                                </a>
                                                <?php if ($user['user_type'] !== 'admin'): ?>
                                                    <a href="admin_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                       class="text-red-600 hover:text-red-900"
                                                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <i class="fas fa-trash mr-1"></i>Delete
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400 cursor-not-allowed">
                                                        <i class="fas fa-trash mr-1"></i>Delete
                                                    </span>
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