<?php 
require_once 'config.php';

if (!is_logged_in()) {
    redirect('signin.php');
}

$user_id = $_SESSION['user_id'];
$conn = get_db_connection();

// Get user's service bookings
$bookings_sql = "SELECT sb.*, s.name as service_name, s.price 
                 FROM service_bookings sb 
                 JOIN services s ON sb.service_id = s.id 
                 WHERE sb.user_id = ? 
                 ORDER BY sb.booking_date DESC, sb.booking_time DESC";
$stmt = mysqli_prepare($conn, $bookings_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

// Get user's delivery orders
$orders_sql = "SELECT do.*, di.name as item_name, di.price, dc.name as category_name 
               FROM delivery_orders do 
               JOIN delivery_items di ON do.item_id = di.id 
               JOIN delivery_categories dc ON di.category_id = dc.id 
               WHERE do.user_id = ? 
               ORDER BY do.created_at DESC";
$stmt = mysqli_prepare($conn, $orders_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ServeRight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .booking-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-delivered {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-out-for-delivery {
            background-color: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Bookings Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold text-gray-900">My Bookings & Orders</h1>
            <p class="text-gray-600 mt-2">Manage your service bookings and delivery orders</p>
        </div>
    </section>

    <!-- Bookings Content -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Service Bookings -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Service Bookings</h2>
                    <a href="services.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Book New Service
                    </a>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-600">No service bookings yet</p>
                        <a href="services.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium mt-2 inline-block">
                            Book your first service
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Service</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Date & Time</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Amount</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Status</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <div>
                                                <h3 class="font-medium text-gray-900"><?php echo $booking['service_name']; ?></h3>
                                                <?php if (!empty($booking['special_requests'])): ?>
                                                    <p class="text-sm text-gray-600 mt-1"><?php echo $booking['special_requests']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="text-gray-900"><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo $booking['booking_time']; ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="font-medium text-gray-900">$<?php echo $booking['total_amount']; ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="status-badge <?php 
                                                echo $booking['status'] === 'confirmed' ? 'status-confirmed' : 
                                                       ($booking['status'] === 'pending' ? 'status-pending' : 
                                                       ($booking['status'] === 'completed' ? 'status-completed' : 'status-cancelled')); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex space-x-2">
                                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                                    <button onclick="cancelBooking(<?php echo $booking['id']; ?>)" 
                                                            class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                        Cancel
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($booking['status'] === 'completed'): ?>
                                                    <button class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                                        Rebook
                                                    </button>
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

            <!-- Delivery Orders -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Delivery Orders</h2>
                    <a href="delivery.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-300">
                        <i class="fas fa-plus mr-2"></i>Place New Order
                    </a>
                </div>
                
                <?php if (empty($orders)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-shopping-bag text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-600">No delivery orders yet</p>
                        <a href="delivery.php" class="text-green-500 hover:text-green-700 text-sm font-medium mt-2 inline-block">
                            Place your first order
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Item</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Category</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Quantity</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Amount</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Status</th>
                                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-900">Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-4 px-4">
                                            <h3 class="font-medium text-gray-900"><?php echo $order['item_name']; ?></h3>
                                            <?php if (!empty($order['delivery_instructions'])): ?>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo $order['delivery_instructions']; ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-1 rounded">
                                                <?php echo $order['category_name']; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="text-gray-900"><?php echo $order['quantity']; ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="font-medium text-gray-900">$<?php echo $order['total_amount']; ?></p>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="status-badge <?php 
                                                echo $order['status'] === 'confirmed' ? 'status-confirmed' : 
                                                       ($order['status'] === 'pending' ? 'status-pending' : 
                                                       ($order['status'] === 'delivered' ? 'status-delivered' : 
                                                       ($order['status'] === 'out_for_delivery' ? 'status-out-for-delivery' : 'status-cancelled'))); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <p class="text-gray-900"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo date('g:i A', strtotime($order['created_at'])); ?></p>
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

    <script>
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                // In a real application, you would make an AJAX call to cancel the booking
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'booking_id=' + bookingId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully');
                        location.reload();
                    } else {
                        alert('Failed to cancel booking: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the booking');
                });
            }
        }
    </script>
</body>
</html>