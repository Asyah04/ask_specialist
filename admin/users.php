<?php
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Handle user role updates
if(isset($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    // Don't allow changing own role
    if($user_id == $_SESSION['id']) {
        $error_message = "You cannot change your own role";
    } else {
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $role, $user_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $success_message = "User role updated successfully";
        } else {
            $error_message = "Error updating user role";
        }
    }
}

// Handle user deletion
if(isset($_POST['delete_user_id'])) {
    $user_id = $_POST['delete_user_id'];
    
    // Don't allow deleting own account
    if($user_id == $_SESSION['id']) {
        $error_message = "You cannot delete your own account";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Delete user's questions
            $sql = "DELETE FROM questions WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Delete user's answers
            $sql = "DELETE FROM answers WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Delete user's specialist application if exists
            $sql = "DELETE FROM specialist_applications WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            // Delete user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            
            mysqli_commit($conn);
            $success_message = "User deleted successfully";
        } catch(Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Get all users except soft-deleted ones by default
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM questions WHERE user_id = u.id) as question_count,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.id) as answer_count
        FROM users u 
        ORDER BY u.created_at DESC";

$result = mysqli_query($conn, $sql);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

$page_title = "Manage Users";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Users</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if(count($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th class="text-center">Q</th>
                                        <th class="text-center">A</th>
                                        <th>Joined</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                        <tr>
                                            <td title="<?php echo htmlspecialchars($user['username']); ?>">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td class="email-cell" title="<?php echo htmlspecialchars($user['email']); ?>">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['role'] === 'admin' ? 'danger' : 
                                                        ($user['role'] === 'specialist' ? 'success' : 'primary'); 
                                                ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center"><?php echo $user['question_count']; ?></td>
                                            <td class="text-center"><?php echo $user['answer_count']; ?></td>
                                            <td title="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>">
                                                <?php echo date('M j', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Edit User">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            title="Delete User"
                                                            onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5>No Users Found</h5>
                            <p class="text-muted">There are no active users in the system at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(userId, username) {
    if(confirm('Are you sure you want to delete user "' + username + '"? Their questions and answers will be preserved.')) {
        window.location.href = 'delete_user.php?id=' + userId;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 