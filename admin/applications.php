<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";


if(isset($_POST['application_id']) && isset($_POST['status'])) {
    $application_id = $_POST['application_id'];
    $status = $_POST['status'];
    $user_id = $_POST['user_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update application status
        $sql = "UPDATE specialist_applications SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $status, $application_id);
        mysqli_stmt_execute($stmt);
        
        // If approved, update user role to specialist
        if($status === 'approved') {
            $sql = "UPDATE users SET role = 'specialist' WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
        }
        
        mysqli_commit($conn);
        $success_message = "Application has been " . $status . " on " . date('M d, Y H:i:s');
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error updating application: " . $e->getMessage();
    }
}

// Handle delete action
if (isset($_POST['application_id']) && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $application_id = $_POST['application_id'];

    try {
        $stmt = mysqli_prepare($conn, "DELETE FROM specialist_applications WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        mysqli_stmt_execute($stmt);
        $success_message = "Application deleted successfully.";
    } catch(Exception $e) {
        $error_message = "Failed to delete application: " . $e->getMessage();
    }
}


// Get all applications with user details
$sql = "SELECT sa.*, u.username, u.email, u.created_at as user_created_at 
        FROM specialist_applications sa 
        JOIN users u ON sa.user_id = u.id 
        ORDER BY sa.created_at DESC";
$applications = mysqli_query($conn, $sql);

$page_title = "Manage Applications";
ob_start();
?>

<div class="container">
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row equal-height">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Specialist Applications</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($applications) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Applied On</th>
                                        <th>Certificate</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($app = mysqli_fetch_assoc($applications)): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($app['username']); ?></h6>
                                                    <small class="text-muted">Member since <?php echo date('M Y', strtotime($app['user_created_at'])); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($app['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                            <td>
                                                <?php if(!empty($app['certificate_image'])): ?>
                                                    <img src="../<?php echo htmlspecialchars($app['certificate_image']); ?>" 
                                                         alt="Certificate" 
                                                         class="img-thumbnail" 
                                                         style="max-width: 100px; cursor: pointer;"
                                                         onclick="window.open('../<?php echo htmlspecialchars($app['certificate_image']); ?>', '_blank')">
                                                <?php else: ?>
                                                    <span class="text-muted">No certificate uploaded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $app['status'] == 'pending' ? 'warning' : ($app['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            
                                                <?php if($app['status'] == 'pending'): ?>
                                                    <form id="approveForm<?php echo $app['id']; ?>" action="" method="post" style="display: inline;">
                                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $app['user_id']; ?>">
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="button" class="btn btn-sm btn-success" onclick="approveApplication(<?php echo $app['id']; ?>)">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <form id="rejectForm<?php echo $app['id']; ?>" action="" method="post" style="display: inline;">
                                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $app['user_id']; ?>">
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="rejectApplication(<?php echo $app['id']; ?>)">
                                                            <i class="fas fa-times"></i> Reject
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form id="deleteForm<?php echo $app['id']; ?>" action="" method="post" style="display: inline;">
                                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteApplication(<?php echo $app['id']; ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5>No Applications Found</h5>
                            <p class="text-muted">There are no specialist applications to review at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Applications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="get">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="input-group">
                                <input type="date" name="start_date" class="form-control">
                                <span class="input-group-text">to</span>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize all modals
document.addEventListener('DOMContentLoaded', function() {
    // Close any open modals when clicking outside
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                bootstrap.Modal.getInstance(this).hide();
            }
        });
    });
});

function approveApplication(id) {
    if(confirm('Are you sure you want to approve this application?')) {
        document.getElementById('approveForm' + id).submit();
    }
}

function rejectApplication(id) {
    if(confirm('Are you sure you want to reject this application?')) {
        document.getElementById('rejectForm' + id).submit();
    }
}

function deleteApplication(id) {
    if(confirm('Are you sure you want to permanently delete this application?')) {
        document.getElementById('deleteForm' + id).submit();
    }
}
</script>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 