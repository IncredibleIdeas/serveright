<?php
require_once 'config.php';

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $booking_id = intval($_POST['booking_id']);
    
    $conn = get_db_connection();
    
    // Check if booking belongs to user and can be cancelled
    $sql = "SELECT status FROM service_bookings WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    
    if ($booking) {
        if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed') {
            $update_sql = "UPDATE service_bookings SET status = 'cancelled' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $booking_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking cannot be cancelled in its current status']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
    }
    
    mysqli_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>