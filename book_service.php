<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('signin.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $service_id = sanitize_input($_POST['service_id']);
    $booking_date = sanitize_input($_POST['booking_date']);
    $booking_time = sanitize_input($_POST['booking_time']);
    $special_requests = sanitize_input($_POST['special_requests']);
    
    $conn = get_db_connection();
    
    // Get service price
    $sql = "SELECT price FROM services WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $service_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $service = mysqli_fetch_assoc($result);
    
    if ($service) {
        $total_amount = $service['price'];
        
        $sql = "INSERT INTO service_bookings (user_id, service_id, booking_date, booking_time, special_requests, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissid", $user_id, $service_id, $booking_date, $booking_time, $special_requests, $total_amount);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Service booked successfully!";
            redirect('bookings.php');
        } else {
            $_SESSION['error'] = "Failed to book service. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Service not found.";
    }
    
    mysqli_close($conn);
    redirect('services.php');
}
?>