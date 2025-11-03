<?php 
require_once 'config.php';

if (!is_logged_in()) {
    redirect('signin.php');
}

$user_id = $_SESSION['user_id'];
$conn = get_db_connection();

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if email already exists (excluding current user)
    $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $error = "Email already exists";
    } else {
        // Update basic info
        $update_sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssi", $full_name, $email, $phone, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $success = "Profile updated successfully";
        } else {
            $error = "Failed to update profile";
        }
        
        // Update password if provided
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $error = "Current password is required to set new password";
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = "Current password is incorrect";
            } elseif ($new_password !== $confirm_password) {
                $error = "New passwords do not match";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_sql = "UPDATE users SET password = ? WHERE id = ?";
                $password_stmt = mysqli_prepare($conn, $password_sql);
                mysqli_stmt_bind_param($password_stmt, "si", $hashed_password, $user_id);
                
                if (!mysqli_stmt_execute($password_stmt)) {
                    $error = "Failed to update password";
                } else {
                    $success = $success ? $success . " and password updated" : "Password updated successfully";
                }
            }
        }
    }
    
    // Refresh user data
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_stmt_get_result($stmt)->fetch_assoc();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ServeRight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php include 'navigation.php'; ?>

    <!-- Profile Header -->
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold text-gray-900">Profile Settings</h1>
            <p class="text-gray-600 mt-2">Manage your account information and preferences</p>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <!-- Personal Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="full_name" name="full_name" required
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($user['phone']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                                <input type="text" value="<?php echo ucfirst($user['user_type']); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Change -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" id="current_password" name="current_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter current password">
                            </div>
                            
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-500 mb-2">Leave password fields blank if you don't want to change your password</p>
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter new password">
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Confirm new password">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Account Statistics -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Account Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg mr-3">
                                        <i class="fas fa-calendar text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-blue-900">Service Bookings</p>
                                        <p class="text-lg font-bold text-blue-600">
                                            <?php 
                                            $conn = get_db_connection();
                                            $count_sql = "SELECT COUNT(*) as count FROM service_bookings WHERE user_id = ?";
                                            $count_stmt = mysqli_prepare($conn, $count_sql);
                                            mysqli_stmt_bind_param($count_stmt, "i", $user_id);
                                            mysqli_stmt_execute($count_stmt);
                                            $count_result = mysqli_stmt_get_result($count_stmt)->fetch_assoc();
                                            echo $count_result['count'];
                                            mysqli_close($conn);
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-lg mr-3">
                                        <i class="fas fa-shopping-bag text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-green-900">Delivery Orders</p>
                                        <p class="text-lg font-bold text-green-600">
                                            <?php 
                                            $conn = get_db_connection();
                                            $count_sql = "SELECT COUNT(*) as count FROM delivery_orders WHERE user_id = ?";
                                            $count_stmt = mysqli_prepare($conn, $count_sql);
                                            mysqli_stmt_bind_param($count_stmt, "i", $user_id);
                                            mysqli_stmt_execute($count_stmt);
                                            $count_result = mysqli_stmt_get_result($count_stmt)->fetch_assoc();
                                            echo $count_result['count'];
                                            mysqli_close($conn);
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg mr-3">
                                        <i class="fas fa-user text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-purple-900">Member Since</p>
                                        <p class="text-lg font-bold text-purple-600">
                                            <?php echo date('M Y', strtotime($user['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end pt-6 border-t">
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-medium transition duration-300">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>