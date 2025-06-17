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
        $error_message = "Category name is required";
    } else {
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $name, $description);
        
        if(mysqli_stmt_execute($stmt)) {
            $success_message = "Category created successfully";
        } else {
            $error_message = "Error creating category";
        }
    }
}

// Handle category update
if(isset($_POST['update_category'])) {
    $category_id = $_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if(empty($name)) {
        $error_message = "Category name is required";
    } else {
        $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $category_id);
        
        if(mysqli_stmt_execute($stmt)) {
            $success_message = "Category updated successfully";
        } else {
            $error_message = "Error updating category";
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
        $success_message = "Category deleted successfully";
    } catch(Exception $e) {
        mysqli_rollback($conn);
        $error_message = "Error deleting category: " . $e->getMessage();
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

    <div class="row">
        <!-- Create Category Form -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Create New Category</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" name="create_category" class="btn btn-primary">Create Category</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Categories</h5>
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
                                                        data-bs-target="#editModal<?php echo $category['id']; ?>">
                                                    Edit
                                                </button>
                                                <?php if($category['id'] != 1): // Don't allow deleting default category ?>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?php echo $category['id']; ?>">
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $category['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Category</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $category['id']; ?>" class="form-label">Category Name</label>
                                                                <input type="text" class="form-control" id="edit_name<?php echo $category['id']; ?>" 
                                                                       name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_description<?php echo $category['id']; ?>" class="form-label">Description</label>
                                                                <textarea class="form-control" id="edit_description<?php echo $category['id']; ?>" 
                                                                          name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_category" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $category['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete category "<?php echo htmlspecialchars($category['name']); ?>"?</p>
                                                        <p class="text-warning">Questions in this category will be moved to the default category.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                            <button type="submit" name="delete_category" class="btn btn-danger">Delete Category</button>
                                                        </form>
                                                    </div>
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

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 