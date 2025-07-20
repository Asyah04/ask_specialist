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
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Update questions to use default category
        $sql = "UPDATE questions SET category_id = 1 WHERE category_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        
        // Delete category
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($conn);
        $_SESSION['success'] = "Category deleted successfully";
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

    <div class="row">
        <!-- Create Category Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Create New Category
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-folder-plus fa-2x text-success mb-2"></i>
                        <p class="text-muted small">Add a new category to organize questions</p>
                    </div>
                    <form method="POST" onsubmit="saveScrollPosition()">
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
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>All Categories
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($categories) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Questions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($category = mysqli_fetch_assoc($categories)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                                            <td><?php echo $category['question_count']; ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $category['id']; ?>"
                                                        title="Edit category">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                                <?php if($category['id'] != 1): // Don't allow deleting default category ?>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?php echo $category['id']; ?>"
                                                            title="Delete category">
                                                        <i class="fas fa-trash me-1"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $category['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-edit me-2"></i>Edit Category
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" onsubmit="saveScrollPosition()">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <div class="text-center mb-4">
                                                                <i class="fas fa-folder-open fa-2x text-primary mb-2"></i>
                                                                <h6 class="text-muted">Editing Category Details</h6>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $category['id']; ?>" class="form-label">
                                                                    <i class="fas fa-tag me-1"></i>Category Name
                                                                </label>
                                                                <input type="text" class="form-control" id="edit_name<?php echo $category['id']; ?>" 
                                                                       name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required
                                                                       placeholder="Enter category name">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_description<?php echo $category['id']; ?>" class="form-label">
                                                                    <i class="fas fa-align-left me-1"></i>Description
                                                                </label>
                                                                <textarea class="form-control" id="edit_description<?php echo $category['id']; ?>" 
                                                                          name="description" rows="3" 
                                                                          placeholder="Enter category description (optional)"><?php echo htmlspecialchars($category['description']); ?></textarea>
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

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $category['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" onsubmit="saveScrollPosition()">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <div class="text-center mb-3">
                                                                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                                                <h6>Are you sure you want to delete:</h6>
                                                                <strong class="text-primary">"<?php echo htmlspecialchars($category['name']); ?>"</strong>
                                                            </div>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <strong>Note:</strong> Questions in this category will be moved to the default category.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i> Cancel
                                                            </button>
                                                            <button type="submit" name="delete_category" class="btn btn-danger">
                                                                <i class="fas fa-trash me-1"></i> Delete Category
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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

<style>
/* Loading overlay styles */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.loading-overlay.show {
    opacity: 1;
    visibility: visible;
}

.loading-spinner {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.loading-spinner i {
    font-size: 2rem;
    color: #007bff;
    margin-bottom: 15px;
}

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

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <div class="mt-2">
            <strong>Processing...</strong>
            <div class="text-muted">Please wait while we process your request</div>
        </div>
    </div>
</div>

<script>
// Enhanced modal and scroll management
document.addEventListener('DOMContentLoaded', function() {
    // Restore scroll position after page load
    const scrollPosition = sessionStorage.getItem('scrollPosition');
    if (scrollPosition) {
        setTimeout(() => {
            window.scrollTo({
                top: parseInt(scrollPosition),
                behavior: 'smooth'
            });
            sessionStorage.removeItem('scrollPosition');
        }, 100);
    }
    
    // Initialize all modals properly
    initializeModals();
});

// Save scroll position before form submission
function saveScrollPosition() {
    sessionStorage.setItem('scrollPosition', window.pageYOffset);
}

// Initialize modal behavior
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        // Handle modal show event
        modal.addEventListener('show.bs.modal', function(e) {
            // Save current scroll position
            const currentScroll = window.pageYOffset;
            modal.setAttribute('data-scroll-position', currentScroll);
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '0px';
            
            // Add backdrop click handler
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.addEventListener('click', function() {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    });
                }
            }, 100);
        });
        
        // Handle modal hide event
        modal.addEventListener('hide.bs.modal', function(e) {
            // Restore body scroll
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
        
        // Handle modal hidden event (completely closed)
        modal.addEventListener('hidden.bs.modal', function(e) {
            // Restore scroll position
            const savedPosition = modal.getAttribute('data-scroll-position');
            if (savedPosition) {
                window.scrollTo({
                    top: parseInt(savedPosition),
                    behavior: 'smooth'
                });
            }
            
            // Clean up
            modal.removeAttribute('data-scroll-position');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    });
}

// Enhanced form submission with loading states
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn) {
            // Save original text
            const originalText = submitBtn.innerHTML;
            
            // Show loading state on button
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
            submitBtn.disabled = true;
            
            // Show loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                setTimeout(() => {
                    loadingOverlay.classList.add('show');
                }, 100);
            }
            
            // Save scroll position
            saveScrollPosition();
            
            // Close any open modals
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
            
            // Re-enable after a delay (in case of errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('show');
                }
            }, 10000);
        }
    });
});

// Smooth scrolling for better UX
document.documentElement.style.scrollBehavior = 'smooth';

// Handle alert auto-dismiss
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn && alert.classList.contains('alert-success')) {
            // Auto-dismiss success alerts after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }
    });
}, 1000);

// Prevent double-clicking and rapid modal opening
let isModalOpening = false;
document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
    button.addEventListener('click', function(e) {
        if (isModalOpening) {
            e.preventDefault();
            return false;
        }
        
        isModalOpening = true;
        setTimeout(() => {
            isModalOpening = false;
        }, 500);
    });
});

// Enhanced keyboard navigation
document.addEventListener('keydown', function(e) {
    // Close modals with Escape key
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }
});

// Prevent form submission on Enter if not in textarea
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit') {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.click();
            }
            e.preventDefault();
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 