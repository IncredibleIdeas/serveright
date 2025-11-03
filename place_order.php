<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('signin.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    $delivery_address = sanitize_input($_POST['delivery_address']);
    $delivery_instructions = sanitize_input($_POST['delivery_instructions']);
    $delivery_time = sanitize_input($_POST['delivery_time']);
    
    $conn = get_db_connection();
    
    // Get item price
    $sql = "SELECT price FROM delivery_items WHERE id = ? AND is_available = TRUE";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $item_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($result);
    
    if ($item) {
        $total_amount = $item['price'] * $quantity;
        
        $sql = "INSERT INTO delivery_orders (user_id, item_id, quantity, delivery_address, delivery_instructions, delivery_time, total_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiisssd", $user_id, $item_id, $quantity, $delivery_address, $delivery_instructions, $delivery_time, $total_amount);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Order placed successfully!";
            redirect('bookings.php');
        } else {
            $_SESSION['error'] = "Failed to place order. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Item not available.";
    }
    
    mysqli_close($conn);
    redirect('delivery.php');
}
?>