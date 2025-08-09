<?php
session_start();

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

require_once "config/database.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";


if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role);
                    if(mysqli_stmt_fetch($stmt)){
                        
                        if(password_verify($password, $hashed_password)){
                            session_start(); 
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role"] = $role;
                            
                            // Redirect based on role
                            if($role === "admin") {
                                header("location: admin/dashboard.php");
                            } elseif($role === "specialist") {
                                header("location: specialist/dashboard.php");
                            } else {
                                header("location: dashboard.php");
                            }
                            exit;
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ask Specialist Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #B2DFDB;
            --accent-color: rgb(65, 92, 123);
            --light-bg: #F0F8F7;
            --gradient-primary: linear-gradient(135deg, rgb(65, 92, 123), #4a90e2 100%);
            --gradient-secondary: linear-gradient(135deg, #4a90e2 0%, rgb(65, 92, 123),  100%);
        }
        
        body {
            background: linear-gradient(135deg, #4a90e2 0%, rgb(65, 92, 123));
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            margin: 100px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(77, 182, 172, 0.2);
            border-top: 4px solid rgb(58, 102, 151);
            transition: all 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(77, 182, 172, 0.25);
        }
        
        .login-container h2 {
            color: var(--accent-color);
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .login-container h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        
        .form-label {
            color: var(--accent-color);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #E0F2F1;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            background-color: #FAFAFA;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(77, 182, 172, 0.2);
            background-color: #fff;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(77, 182, 172, 0.3);
        }
        
        .btn-primary:hover {
            background: var(--gradient-secondary);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(77, 182, 172, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(-1px);
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #FF5252 0%, #FF8A80 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 500;
        }
        
        .invalid-feedback {
            color: #FF5252;
            font-weight: 500;
        }
        
        .form-control.is-invalid {
            border-color: #FF5252;
            box-shadow: 0 0 0 0.25rem rgba(255, 82, 82, 0.2);
        }
        
        /* Password toggle styles */
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--accent-color);
        }
        
        .password-toggle:focus {
            outline: none;
        }
        
        /* Add some nice animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .form-control, .btn {
            animation: fadeInUp 0.8s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2>Login</h2>
            
            <?php 
            if(!empty($login_err)){
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>    
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <button type="button" class="password-toggle" onclick="togglePassword()" style="background:none; border:none;">
                            <i id="toggleIcon" class="fa-solid fa-eye-slash"></i>
                        </button>
                    </div>

                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="mb-3">
                    <input type="submit" class="btn btn-primary w-100" value="Login">
                </div>
                <p class="text-center">Don't have an account? <a href="register.php">Sign up now</a>.</p>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye'); 
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash'); 
            }
        }
    </script>

</body>
</html> 