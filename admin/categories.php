<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Handle category creation
if(isset($_POST['create_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if(empty($name)) {
        $_SESSION['error'] = "Category name is required";
    } else {
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Category created successfully";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Error creating category";
        }
    }
}

// Handle category update
if(isset($_POST['update_category'])) {
    $category_id = $_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if(empty($name)) {
        $_SESSION['error'] = "Category name is required";
    } else {
        $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $category_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Category updated successfully";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Error updating category";
        }
    }
}

// Handle category deletion
if(isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    
    // Check if there are other categories available
    $check_sql = "SELECT COUNT(*) as total FROM categories";
    $check_result = mysqli_query($conn, $check_sql);
    $total_categories = mysqli_fetch_assoc($check_result)['total'];
    
    if($total_categories <= 1) {
        $_SESSION['error'] = "Cannot delete the last category. At least one category must exist.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Find an alternative category to move questions to (not the one being deleted)
        $alt_sql = "SELECT id FROM categories WHERE id != ? ORDER BY id LIMIT 1";
        $alt_stmt = mysqli_prepare($conn, $alt_sql);
        mysqli_stmt_bind_param($alt_stmt, "i", $category_id);
        mysqli_stmt_execute($alt_stmt);
        $alt_result = mysqli_stmt_get_result($alt_stmt);
        $alternative_category = mysqli_fetch_assoc($alt_result);
        
        if(!$alternative_category) {
            throw new Exception("No alternative category found");
        }
        
        // Update questions to use alternative category
        $sql = "UPDATE questions SET category_id = ? WHERE category_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $alternative_category['id'], $category_id);
        mysqli_stmt_execute($stmt);
        
        // Update specialist applications to use alternative category
        $sql = "UPDATE specialist_applications SET category_id = ? WHERE category_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $alternative_category['id'], $category_id);
        mysqli_stmt_execute($stmt);
        
        // Delete category
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Category deleted successfully. Questions moved to alternative category.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error deleting category: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get all categories with question counts
$sql = "SELECT c.*, COUNT(q.id) as question_count 
        FROM categories c 
        LEFT JOIN questions q ON c.id = q.category_id 
        GROUP BY c.id 
        ORDER BY c.name";
$categories = mysqli_query($conn, $sql);

$page_title = "Manage Categories";
ob_start();
?>

<div class="container">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Create Category Form - Full Width Top -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Create New Category
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center mb-3">
                                <i class="fas fa-folder-plus fa-2x text-success mb-2"></i>
                                <p class="text-muted small">Add a new category to organize questions</p>
                            </div>
                            <form method="POST" onsubmit="return submitForm(this)">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Category Name
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           placeholder="Enter category name">
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Enter category description (optional)"></textarea>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="create_category" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i> Create Category
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 d-flex align-items-center justify-content-center">
                            <div class="text-center">
                                <i class="fas fa-tags fa-4x text-success opacity-50 mb-3"></i>
                                <h6 class="text-muted">Quick Category Creation</h6>
                                <p class="text-muted small">Categories help organize questions and make it easier for specialists to find relevant topics to answer.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories List - Full Width Below -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>All Categories
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($categories) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover categories-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th class="text-center">Q</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($category = mysqli_fetch_assoc($categories)): ?>
                                        <tr>
                                            <td title="<?php echo htmlspecialchars($category['name']); ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </td>
                                            <td class="description-cell" title="<?php echo htmlspecialchars($category['description']); ?>">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </td>
                                            <td class="text-center"><?php echo $category['question_count']; ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            onclick="openEditModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', '<?php echo htmlspecialchars($category['description']); ?>')"
                                                            title="Edit category">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <!-- Delete button for all categories -->
                                                    <form method="POST" class="d-inline" onsubmit="return confirmDelete('<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['question_count']; ?>)">
                                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                        <button type="submit" name="delete_category" class="btn btn-sm btn-danger" title="Delete category">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Individual edit modals removed for better performance -->

                                        <!-- Delete modals removed for simpler UX -->
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No categories found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Universal Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return submitForm(this)">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="editCategoryId">
                    <div class="text-center mb-4">
                        <i class="fas fa-folder-open fa-2x text-primary mb-2"></i>
                        <h6 class="text-muted">Editing Category Details</h6>
                    </div>
                    <div class="mb-3">
                        <label for="editName" class="form-label">
                            <i class="fas fa-tag me-1"></i>Category Name
                        </label>
                        <input type="text" class="form-control" id="editName" 
                               name="name" required placeholder="Enter category name">
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Description
                        </label>
                        <textarea class="form-control" id="editDescription" 
                                  name="description" rows="3" 
                                  placeholder="Enter category description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Smooth transitions for better UX */

/* Modal enhancements */
.modal-content {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.modal-header {
    border-bottom: none;
    padding: 20px 25px 15px;
}

.modal-body {
    padding: 20px 25px;
}

.modal-footer {
    border-top: none;
    padding: 15px 25px 20px;
}

/* Card enhancements */
.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
    padding: 20px 25px;
}

.card-body {
    padding: 25px;
}

/* Table enhancements */
.table-responsive {
    border-radius: 10px;
    overflow: hidden;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    padding: 15px 12px;
}

.table td {
    padding: 15px 12px;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

/* Button enhancements */
.btn {
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Alert enhancements */
.alert {
    border: none;
    border-radius: 10px;
    padding: 15px 20px;
}

/* Animation for smooth transitions */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card, .alert {
    animation: fadeIn 0.5s ease-out;
}
</style>

<!-- Loading overlay removed for smoother performance -->

<script>
// Simple and clean modal management
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss success alerts after 5 seconds
    setTimeout(() => {
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        });
    }, 5000);
});

// Simple form submission with loading state
function submitForm(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        submitBtn.disabled = true;
        
        // Re-enable after timeout to prevent permanent disable
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 10000);
    }
    return true;
}

// Enhanced delete confirmation with question count
function confirmDelete(categoryName, questionCount) {
    let message = `Are you sure you want to delete "${categoryName}"?`;
    
    if (questionCount > 0) {
        message += `\n\n⚠️ WARNING: This category has ${questionCount} question(s).`;
        message += `\nDeleting this category will move all questions to another available category.`;
        message += `\n\nThis action cannot be undone!`;
    } else {
        message += `\n\nThis category has no questions and can be safely deleted.`;
    }
    
    return confirm(message);
}

// Open edit modal with data
function openEditModal(categoryId, categoryName, categoryDescription) {
    // Populate modal fields
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editName').value = categoryName;
    document.getElementById('editDescription').value = categoryDescription;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

// Close modal on backdrop click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            const modalInstance = bootstrap.Modal.getInstance(this);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 