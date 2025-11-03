<?php 
require_once 'config.php';

// Search and filter functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

$conn = get_db_connection();

// Get delivery categories
$categories_sql = "SELECT * FROM delivery_categories";
$categories_result = mysqli_query($conn, $categories_sql);
$categories = mysqli_fetch_all($categories_result, MYSQLI_ASSOC);

// Build items query with filters
$items_sql = "SELECT di.*, dc.name as category_name 
              FROM delivery_items di 
              JOIN delivery_categories dc ON di.category_id = dc.id 
              WHERE di.is_available = TRUE";
$params = [];
$types = "";

if (!empty($search)) {
    $items_sql .= " AND (di.name LIKE ? OR di.description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if ($category_id > 0) {
    $items_sql .= " AND di.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$items_sql .= " ORDER BY di.category_id, di.name";

$stmt = mysqli_prepare($conn, $items_sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$items = mysqli_fetch_all($items_result, MYSQLI_ASSOC);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery - ServeRight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .category-card, .item-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .category-card:hover, .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .order-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Delivery Header -->
    <section class="bg-green-600 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold">Hyperlocal Delivery</h1>
                <p class="mt-4 text-xl">Get anything delivered from local stores in under an hour.</p>
            </div>
        </div>
    </section>

    <!-- Search and Filter -->
    <section class="py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search for items..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <button type="submit" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="md:w-64">
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="0" <?php echo $category_id == 0 ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo $cat['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="w-full md:w-auto bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold transition duration-300">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Delivery Content -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Categories Grid -->
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Delivery Categories</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                            <div class="p-6">
                                <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-lg mb-4">
                                    <i class="<?php echo $category['icon_class']; ?> text-green-600 text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-medium text-gray-900"><?php echo $category['name']; ?></h3>
                                <p class="mt-2 text-gray-600 text-sm"><?php echo $category['description']; ?></p>
                                <button onclick="filterByCategory(<?php echo $category['id']; ?>)" 
                                        class="mt-4 w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-md font-medium transition duration-300">
                                    Browse Items
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Items Grid -->
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-8">Available Items</h2>
                <?php if (empty($items)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900">No items found</h3>
                        <p class="text-gray-600 mt-2">Try adjusting your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php foreach ($items as $item): ?>
                            <div class="item-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                                <div class="h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-3xl"></i>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-medium text-gray-900"><?php echo $item['name']; ?></h3>
                                        <span class="bg-green-100 text-green-800 text-sm font-medium px-2 py-1 rounded">
                                            $<?php echo $item['price']; ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-4"><?php echo $item['description']; ?></p>
                                    <div class="flex items-center justify-between">
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded">
                                            <?php echo $item['category_name']; ?>
                                        </span>
                                        <?php if (is_logged_in()): ?>
                                            <button onclick="openOrderModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)" 
                                                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-medium transition duration-300">
                                                Order Now
                                            </button>
                                        <?php else: ?>
                                            <a href="signin.php" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-medium transition duration-300">
                                                Sign In to Order
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Order Modal -->
    <div id="orderModal" class="order-modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Place Order</h3>
                        <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="orderForm" method="POST" action="place_order.php">
                        <input type="hidden" name="item_id" id="item_id">
                        
                        <div id="itemDetails" class="bg-gray-50 p-4 rounded-lg mb-4">
                            <!-- Item details will be inserted here -->
                        </div>
                        
                        <div class="mb-4">
                            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="10" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
                            <textarea name="delivery_address" id="delivery_address" rows="3" required 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                      placeholder="Enter your complete delivery address"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="delivery_instructions" class="block text-sm font-medium text-gray-700 mb-1">Delivery Instructions (Optional)</label>
                            <textarea name="delivery_instructions" id="delivery_instructions" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                      placeholder="e.g., Leave at front door, call upon arrival, etc."></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="delivery_time" class="block text-sm font-medium text-gray-700 mb-1">Preferred Delivery Time</label>
                            <select name="delivery_time" id="delivery_time" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                                <option value="">Select delivery time</option>
                                <option value="asap">As soon as possible</option>
                                <option value="9-12">9:00 AM - 12:00 PM</option>
                                <option value="12-3">12:00 PM - 3:00 PM</option>
                                <option value="3-6">3:00 PM - 6:00 PM</option>
                                <option value="6-9">6:00 PM - 9:00 PM</option>
                            </select>
                        </div>
                        
                        <div class="flex justify-between items-center pt-4 border-t">
                            <div>
                                <span class="text-lg font-bold text-gray-900">Total: $<span id="orderTotal">0.00</span></span>
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" onclick="closeOrderModal()" 
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                                    Place Order
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function filterByCategory(categoryId) {
            const url = new URL(window.location);
            url.searchParams.set('category_id', categoryId);
            window.location.href = url.toString();
        }
        
        function openOrderModal(itemId, itemName, itemPrice) {
            document.getElementById('item_id').value = itemId;
            
            const quantity = document.getElementById('quantity').value;
            const total = (itemPrice * quantity).toFixed(2);
            
            document.getElementById('itemDetails').innerHTML = `
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg mr-4">
                        <i class="fas fa-shopping-bag text-green-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">${itemName}</h4>
                        <p class="text-green-600 font-bold">$${itemPrice.toFixed(2)} each</p>
                    </div>
                </div>
            `;
            
            document.getElementById('orderTotal').textContent = total;
            document.getElementById('orderModal').style.display = 'flex';
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }
        
        // Update total when quantity changes
        document.getElementById('quantity').addEventListener('change', function() {
            const itemId = document.getElementById('item_id').value;
            if (itemId) {
                // This would need to be enhanced to recalculate based on actual item price
                const quantity = this.value;
                const itemPrice = parseFloat(document.querySelector('#itemDetails .text-green-600').textContent.replace('$', ''));
                const total = (itemPrice * quantity).toFixed(2);
                document.getElementById('orderTotal').textContent = total;
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>