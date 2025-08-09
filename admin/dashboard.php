<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Get statistics
$stats = [];

// Total users
$sql = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$result = mysqli_query($conn, $sql);
$stats['total_students'] = mysqli_fetch_assoc($result)['total'];

// Total specialists
$sql = "SELECT COUNT(*) as total FROM users WHERE role = 'specialist'";
$result = mysqli_query($conn, $sql);
$stats['total_specialists'] = mysqli_fetch_assoc($result)['total'];

// Pending applications
$sql = "SELECT COUNT(*) as total FROM specialist_applications WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);
$stats['pending_applications'] = mysqli_fetch_assoc($result)['total'];

// Total questions
$sql = "SELECT COUNT(*) as total FROM questions";
$result = mysqli_query($conn, $sql);
$stats['total_questions'] = mysqli_fetch_assoc($result)['total'];




$sql = "SELECT sa.*, u.username, u.email, c.name as category_name 
        FROM specialist_applications sa 
        JOIN users u ON sa.user_id = u.id 
        JOIN categories c ON sa.category_id = c.id 
        WHERE sa.status = 'pending' 
        ORDER BY sa.created_at DESC 
        LIMIT 5";
$recent_applications = mysqli_query($conn, $sql);

// Recent questions
$sql = "SELECT q.*, u.username, c.name as category_name, COUNT(a.id) as answer_count
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        JOIN categories c ON q.category_id = c.id 
        LEFT JOIN answers a ON q.id = a.question_id
        GROUP BY q.id
        ORDER BY q.created_at DESC 
        LIMIT 5";
$recent_questions = mysqli_query($conn, $sql);

$page_title = "Admin Dashboard";
ob_start();
?>

<div class="container">
    <!-- DEBUG: Modern UI loaded at <?php echo date('H:i:s'); ?> -->
    <!-- Modern Statistics Cards -->
    <div class="row equal-height mb-4">
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: var(--gradient-primary); animation-delay: 0.1s;">
                <div class="stats-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Asker</div>
                    <div class="stats-number"><?php echo $stats['total_students']; ?></div>
                    <div class="stats-label">Registered Asker</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: var(--gradient-success); animation-delay: 0.2s;">
                <div class="stats-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Specialists</div>
                    <div class="stats-number"><?php echo $stats['total_specialists']; ?></div>
                    <div class="stats-label">Active Specialists</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); animation-delay: 0.3s;">
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Pending Applications</div>
                    <div class="stats-number"><?php echo $stats['pending_applications']; ?></div>
                    <div class="stats-label">Awaiting Review</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%); animation-delay: 0.4s;">
                <div class="stats-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Questions</div>
                    <div class="stats-number"><?php echo $stats['total_questions']; ?></div>
                    <div class="stats-label">Questions Asked</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Applications - Full Width -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applications</h5>
                    <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_applications) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-applications-table">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Email</th>
                                        <th>Category</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($app = mysqli_fetch_assoc($recent_applications)): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($app['username']); ?></strong>
                                            </td>
                                            <td class="email-cell" title="<?php echo htmlspecialchars($app['email']); ?>">
                                                <?php echo htmlspecialchars($app['email']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($app['category_name'] ?? 'General'); ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">Pending</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="applications.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary" title="Review Application">
                                                    <i class="fas fa-eye me-1"></i> Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h6>No Pending Applications</h6>
                            <p class="text-muted mb-0">New specialist applications will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Questions - Full Width -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Recent Questions</h5>
                    <a href="questions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_questions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover admin-questions-table">
                                <thead>
                                    <tr>
                                        <th>Question Title</th>
                                        <th>Asked By</th>
                                        <th>Category</th>
                                        <th>Answers</th>
                                        <th>Asked Date</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($question = mysqli_fetch_assoc($recent_questions)): ?>
                                        <tr>
                                            <td class="question-title-cell" title="<?php echo htmlspecialchars($question['title']); ?>">
                                                <strong><?php echo htmlspecialchars(substr($question['title'], 0, 60) . (strlen($question['title']) > 60 ? '...' : '')); ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($question['username']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?php echo htmlspecialchars($question['category_name']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success">
                                                    <?php echo $question['answer_count'] ?? 0; ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M d, Y', strtotime($question['created_at'])); ?>
                                            </td>
                                            <td>
                                                <?php if(($question['answer_count'] ?? 0) > 0): ?>
                                                    <span class="badge bg-success">Answered</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="../view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-primary" title="View Question">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <h6>No Recent Questions</h6>
                            <p class="text-muted mb-0">Questions from users will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <!-- <div class="row equal-height">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row equal-height">
                        <div class="col-md-3 mb-3">
                            <a href="users.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users me-2"></i> Manage Users
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="applications.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-file-alt me-2"></i> Review Applications
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="categories.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-tags me-2"></i> Manage Categories
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-chart-bar me-2"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
</div>

<!-- Custom CSS removed - now using modern layout.php styles -->
<!-- Updated: <?php echo date('Y-m-d H:i:s'); ?> - Modern UI applied -->

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 