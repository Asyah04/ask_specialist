<?php
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Check if user ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: users.php");
    exit;
}

$user_id = $_GET['id'];

// First check if user exists
$check_sql = "SELECT id, username FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 0) {
        $_SESSION['error'] = "User not found.";
        header("location: users.php");
        exit;
    }
    
    $user = mysqli_fetch_assoc($result);
}

// Perform hard delete
$delete_sql = "DELETE FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $delete_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "User '" . htmlspecialchars($user['username']) . "' has been deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting user. Please try again.";
    }
    
    mysqli_stmt_close($stmt);
}

header("location: users.php");
exit; 