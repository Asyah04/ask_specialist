<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if user is a student
if($_SESSION["role"] !== "student"){
    header("location: dashboard.php");
    exit;
}

require_once "config/database.php";

$certificate_err = $success_message = "";
$category_id = "";
$category_err = "";

// Get all categories
$categories = [];
$sql = "SELECT id, name FROM categories ORDER BY name";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $categories[] = $row;
}

// Check if user already has a pending application
$sql = "SELECT status FROM specialist_applications WHERE user_id = ? AND status = 'pending'";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt) > 0){
            $success_message = "You already have a pending application. Please wait for admin review.";
        }
    }
    mysqli_stmt_close($stmt);
}

if($_SERVER["REQUEST_METHOD"] == "POST" && empty($success_message)){
    // Validate category
    if(empty(trim($_POST["category_id"]))){
        $category_err = "Please select a category.";
    } else{
        $category_id = trim($_POST["category_id"]);
    }

    // Handle certificate upload
    $certificate_path = null;
    if(isset($_FILES["certificate"]) && $_FILES["certificate"]["error"] == 0){
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if(!in_array($_FILES["certificate"]["type"], $allowed_types)){
            $certificate_err = "Only JPG, PNG and PDF files are allowed.";
        } elseif($_FILES["certificate"]["size"] > $max_size){
            $certificate_err = "File size should not exceed 5MB.";
        } else{
            $upload_dir = "uploads/certificates/";
            if(!file_exists($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES["certificate"]["name"], PATHINFO_EXTENSION);
            $file_name = uniqid() . "." . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if(move_uploaded_file($_FILES["certificate"]["tmp_name"], $target_path)){
                $certificate_path = $target_path;
            } else{
                $certificate_err = "Failed to upload certificate. Please try again.";
            }
        }
    } else {
        $certificate_err = "Please upload your certificate.";
    }
    
    // Check input errors before inserting in database
    if(empty($certificate_err) && empty($category_err)){
        $sql = "INSERT INTO specialist_applications (user_id, category_id, certificate_image, status) VALUES (?, ?, ?, 'pending')";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "iis", $param_user_id, $param_category_id, $param_certificate_image);
            
            $param_user_id = $_SESSION["id"];
            $param_category_id = $category_id;
            $param_certificate_image = $certificate_path;
            
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Your application has been submitted successfully! Please wait for admin review.";
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}

$page_title = "Apply to be a Specialist";
ob_start();
?>

<div class="container">
    <div class="question-container">
        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="mb-4">
            <h4>Application Requirements</h4>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item">Upload a valid certificate or proof of expertise</li>
                <li class="list-group-item">Select your area of expertise</li>
            </ul>
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Select Category</label>
                <select name="category_id" class="form-select <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                    <option value="">Choose a category...</option>
                    <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="invalid-feedback"><?php echo $category_err; ?></span>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload Certificate/Proof of Expertise</label>
                <input type="file" name="certificate" class="form-control <?php echo (!empty($certificate_err)) ? 'is-invalid' : ''; ?>" accept=".jpg,.jpeg,.png,.pdf">
                <span class="invalid-feedback"><?php echo $certificate_err; ?></span>
                <small class="text-muted">Supported formats: JPG, PNG, PDF. Max size: 5MB</small>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Submit Application</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 