<?php 
require_once 'config.php';

// Search functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';

$conn = get_db_connection();

// Build query with filters
$sql = "SELECT * FROM services WHERE is_active = TRUE";
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

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$services = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get unique categories for filter
$category_result = mysqli_query($conn, "SELECT DISTINCT category FROM services WHERE is_active = TRUE");
$categories = mysqli_fetch_all($category_result, MYSQLI_ASSOC);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - ServeRight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Services Header -->
    <section class="bg-blue-600 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold">Our Services</h1>
            <p class="mt-2 text-blue-100">Professional services for your home, scheduled at your convenience</p>
        </div>
    </section>

    <!-- Search and Filter -->
    <section class="py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search services..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="submit" class="absolute right-3 top-2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="md:w-64">
                    <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" <?php echo $category === 'all' || empty($category) ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category']; ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <button type="submit" class="w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold transition duration-300">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (empty($services)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-search text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900">No services found</h3>
                    <p class="text-gray-600 mt-2">Try adjusting your search criteria</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($services as $service): ?>
                        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition duration-300">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                                            <i class="fas fa-tools text-blue-600 text-xl"></i>
                                        </div>
                                        <h3 class="ml-4 text-lg font-medium text-gray-900"><?php echo $service['name']; ?></h3>
                                    </div>
                                    <span class="bg-green-100 text-green-800 text-sm font-medium px-2 py-1 rounded">$<?php echo $service['price']; ?>/hr</span>
                                </div>
                                
                                <p class="mt-4 text-gray-600"><?php echo $service['description']; ?></p>
                                
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="bg-gray-100 text-gray-800 text-sm font-medium px-3 py-1 rounded-full">
                                        <?php echo ucfirst($service['category']); ?>
                                    </span>
                                    
                                    <?php if (is_logged_in()): ?>
                                        <button onclick="openBookingModal(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>', <?php echo $service['price']; ?>)" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                                            Book Now
                                        </button>
                                    <?php else: ?>
                                        <a href="signin.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                                            Sign In to Book
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Book Service</h3>
                <button onclick="closeBookingModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="bookingForm" method="POST" action="book_service.php">
                <input type="hidden" name="service_id" id="service_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                    <input type="text" id="service_name" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                    <input type="text" id="service_price" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="booking_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="booking_date" id="booking_date" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="booking_time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                    <select name="booking_time" id="booking_time" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Time</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="16:00">4:00 PM</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                    <textarea name="special_requests" id="special_requests" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Any specific requirements..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeBookingModal()" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        function openBookingModal(serviceId, serviceName, servicePrice) {
            document.getElementById('service_id').value = serviceId;
            document.getElementById('service_name').value = serviceName;
            document.getElementById('service_price').value = '$' + servicePrice + '/hour';
            document.getElementById('bookingModal').classList.remove('hidden');
        }
        
        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }
        
        // Close modal when clicking outside
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBookingModal();
            }
        });
    </script>
</body>
</html>