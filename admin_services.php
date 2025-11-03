<?php 
require_once 'config.php';

if (!is_logged_in() || !is_admin()) {
    redirect('signin.php');
}

$conn = get_db_connection();

// Handle service actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $service_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    switch ($action) {
        case 'delete':
            $delete_sql = "DELETE FROM services WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $service_id);
            if (mysqli_stmt_execute($delete_stmt)) {
                $_SESSION['success'] = "Service deleted successfully";
            } else {
                $_SESSION['error'] = "Failed to delete service";
            }
            break;
            
        case 'toggle_status':
            $service_sql = "SELECT is_active FROM services WHERE id = ?";
            $service_stmt = mysqli_prepare($conn, $service_sql);
            mysqli_stmt_bind_param($service_stmt, "i", $service_id);
            mysqli_stmt_execute($service_stmt);
            $service = mysqli_stmt_get_result($service_stmt)->fetch_assoc();
            
            if ($service) {
                $new_status = $service['is_active'] ? 0 : 1;
                $update_sql = "UPDATE services SET is_active = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ii", $new_status, $service_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success'] = "Service status updated successfully";
                } else {
                    $_SESSION['error'] = "Failed to update service status";
                }
            }
            break;
    }
    
    redirect('admin_services.php');
}

// Handle form submission for adding/editing services
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price = floatval($_POST['price']);
    $category = sanitize_input($_POST['category']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (isset($_POST['service_id'])) {
        // Update existing service
        $service_id = intval($_POST['service_id']);
        $sql = "UPDATE services SET name = ?, description = ?, price = ?, category = ?, is_active = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdsii", $name, $description, $price, $category, $is_active, $service_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Service updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update service";
        }
    } else {
        // Add new service
        $sql = "INSERT INTO services (name, description, price, category, is_active) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssdsi", $name, $description, $price, $category, $is_active);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Service added successfully";
        } else {
            $_SESSION['error'] = "Failed to add service";
        }
    }
    
    redirect('admin_services.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$sql = "SELECT * FROM services WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($category) && $category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($status) && $status !== 'all') {
    $is_active = $status === 'active' ? 1 : 0;
    $sql .= " AND is_active = ?";
    $params[] = $is_active;
    $types .= "i";
}

$sql .= " ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$services = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get unique categories
$categories_sql = "SELECT DISTINCT category FROM services";
$categories_result = mysqli_query($conn, $categories_sql);
$categories_list = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

$total_services = count($services);
$active_services = array_filter($services, function($service) {
    return $service['is_active'];
});

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - ServeRight Admin</title>
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
                        <a href="admin_services.php" class="text-blue-600 border-b-2 border-blue-600 inline-flex items-center px-1 pt-1 text-sm font-medium">Services</a>
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
                    <h1 class="text-2xl font-bold text-gray-900">Manage Services</h1>
                    <p class="text-gray-600 mt-2">Add, edit, and manage services</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                        Active: <?php echo count($active_services); ?>
                    </span>
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                        Total: <?php echo $total_services; ?>
                    </span>
                    <button onclick="openServiceModal()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300 flex items-center">
                        <i class="fas fa-plus mr-2"></i>Add Service
                    </button>
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
                            <input type="text" name="search" placeholder="Search services by name or description..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories_list as $cat): ?>
                                <option value="<?php echo $cat['category']; ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="all">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-4 flex space-x-2">
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300">
                            Apply Filters
                        </button>
                        <a href="admin_services.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition duration-300 flex items-center">
                            <i class="fas fa-refresh mr-2"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Services Table -->
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

                <?php if (empty($services)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-tools text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900">No services found</h3>
                        <p class="text-gray-600 mt-2">Try adjusting your search criteria or add a new service</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($services as $service): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500">ID: <?php echo $service['id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate"><?php echo htmlspecialchars($service['description']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                                <?php echo ucfirst($service['category']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            $<?php echo $service['price']; ?>/hour
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $service['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="editService(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>', '<?php echo addslashes($service['description']); ?>', <?php echo $service['price']; ?>, '<?php echo $service['category']; ?>', <?php echo $service['is_active']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit mr-1"></i>Edit
                                                </button>
                                                <a href="admin_services.php?action=toggle_status&id=<?php echo $service['id']; ?>" 
                                                   class="text-orange-600 hover:text-orange-900">
                                                    <i class="fas fa-power-off mr-1"></i><?php echo $service['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </a>
                                                <a href="admin_services.php?action=delete&id=<?php echo $service['id']; ?>" 
                                                   class="text-red-600 hover:text-red-900"
                                                   onclick="return confirm('Are you sure you want to delete this service? This action cannot be undone.')">
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

    <!-- Service Modal -->
    <div id="serviceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" id="modalTitle">Add New Service</h3>
                <button onclick="closeServiceModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="serviceForm" method="POST">
                <input type="hidden" name="service_id" id="service_id">
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price per Hour ($)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" id="category" name="category" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., home, pet, vehicle">
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Active Service
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeServiceModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Save Service
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function openServiceModal() {
            document.getElementById('modalTitle').textContent = 'Add New Service';
            document.getElementById('serviceForm').reset();
            document.getElementById('service_id').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('serviceModal').classList.remove('hidden');
        }
        
        function closeServiceModal() {
            document.getElementById('serviceModal').classList.add('hidden');
        }
        
        function editService(id, name, description, price, category, isActive) {
            document.getElementById('modalTitle').textContent = 'Edit Service';
            document.getElementById('service_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('description').value = description;
            document.getElementById('price').value = price;
            document.getElementById('category').value = category;
            document.getElementById('is_active').checked = isActive == 1;
            document.getElementById('serviceModal').classList.remove('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('serviceModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeServiceModal();
            }
        });
    </script>
</body>
</html>