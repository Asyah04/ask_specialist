<?php
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Check if user ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])){
    $_SESSION['error'] = "User ID not provided.";
    header("location: users.php");
    exit;
}

$user_id = $_GET['id'];

// Initialize variables
$username = $email = $phone = $address = $role = "";
$username_err = $email_err = $phone_err = $address_err = $role_err = "";

// Get user data
$sql = "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $user = mysqli_fetch_assoc($result);
            $username = $user['username'];
            $email = $user['email'];
            $phone = $user['phone'];
            $address = $user['address'];
            $role = $user['role'];
        } else {
            $_SESSION['error'] = "User not found.";
            header("location: users.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Error retrieving user data.";
        header("location: users.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))){
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else{
        // Check if username is taken by another user
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_username, $user_id);
            
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "This username is already taken.";
                } else{
                    $username = trim($_POST["username"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Please enter a valid email address.";
    } else{
        // Check if email is taken by another user
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_email, $user_id);
            
            $param_email = trim($_POST["email"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate phone
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Please enter a phone number.";
    } else{
        // Check if phone is taken by another user
        $sql = "SELECT id FROM users WHERE phone = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_phone, $user_id);
            
            $param_phone = trim($_POST["phone"]);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $phone_err = "This phone number is already registered.";
                } else{
                    $phone = trim($_POST["phone"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate address
    if(empty(trim($_POST["address"]))){
        $address_err = "Please enter an address.";
    } else{
        $address = trim($_POST["address"]);
    }
    
    // Validate role
    if(empty($_POST["role"])){
        $role_err = "Please select a role.";
    } elseif(!in_array($_POST["role"], ['admin', 'student', 'specialist'])){
        $role_err = "Please select a valid role.";
    } else{
        // Don't allow changing own role
        if($user_id == $_SESSION['id'] && $_POST["role"] !== $role){
            $role_err = "You cannot change your own role.";
        } else {
            $role = $_POST["role"];
        }
    }
    
    // Check input errors before updating database
    if(empty($username_err) && empty($email_err) && empty($phone_err) && empty($address_err) && empty($role_err)){
        
        // Prepare an update statement
        $sql = "UPDATE users SET username = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssi", $param_username, $param_email, $param_phone, $param_address, $param_role, $user_id);
            
            $param_username = $username;
            $param_email = $email;
            $param_phone = $phone;
            $param_address = $address;
            $param_role = $role;
            
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success'] = "User updated successfully!";
                header("location: users.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

$page_title = "Edit User";
ob_start();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Users
                    </a>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $user_id; ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="username">Username</label>
                                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                                    <div class="invalid-feedback"><?php echo $username_err; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($phone); ?>" required>
                                    <div class="invalid-feedback"><?php echo $phone_err; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="role">Role</label>
                                    <select name="role" class="form-control <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>" required>
                                        <option value="">Select Role</option>
                                        <option value="student" <?php echo ($role == 'student') ? 'selected' : ''; ?>>Student</option>
                                        <option value="specialist" <?php echo ($role == 'specialist') ? 'selected' : ''; ?>>Specialist</option>
                                        <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <div class="invalid-feedback"><?php echo $role_err; ?></div>
                                    <?php if($user_id == $_SESSION['id']): ?>
                                        <small class="form-text text-muted">Note: You cannot change your own role.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea name="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                            <div class="invalid-feedback"><?php echo $address_err; ?></div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Note:</strong> To change the password, the user should login and change it from their profile settings.
                        </div>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update User
                            </button>
                            <a href="users.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 