<?php
require_once "config/database.php";

// Admin credentials
$admin_username = "admin";
$admin_password = "admin123";

// Generate password hash
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Update admin password in database
$sql = "UPDATE users SET password = ? WHERE username = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $admin_username);
    
    if(mysqli_stmt_execute($stmt)) {
        echo "Admin password has been updated successfully!<br>";
        echo "Username: " . $admin_username . "<br>";
        echo "Password: " . $admin_password . "<br>";
        echo "Hashed Password: " . $hashed_password;
    } else {
        echo "Error updating password: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 