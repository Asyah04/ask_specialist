<?php
session_start();

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Get date range from request or default to last 30 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Get user statistics
$sql = "SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as total_students,
            SUM(CASE WHEN role = 'specialist' THEN 1 ELSE 0 END) as total_specialists,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins
        FROM users";
$user_stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Get question statistics
$sql = "SELECT 
            COUNT(*) as total_questions,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_questions,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_questions
        FROM questions";
$question_stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Get answer statistics
$sql = "SELECT COUNT(*) as total_answers FROM answers";
$answer_stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Get application statistics
$sql = "SELECT 
            COUNT(*) as total_applications,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_applications,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_applications
        FROM specialist_applications";
$application_stats = mysqli_fetch_assoc(mysqli_query($conn, $sql));

// Get questions by category
$sql = "SELECT c.name, COUNT(q.id) as question_count 
        FROM categories c 
        LEFT JOIN questions q ON c.id = q.category_id 
        GROUP BY c.id 
        ORDER BY question_count DESC";
$category_stats = mysqli_query($conn, $sql);

// Get recent activity
$sql = "SELECT 'question' as type, q.title as content, u.username, q.created_at 
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        WHERE q.created_at BETWEEN ? AND ?
        UNION ALL
        SELECT 'answer' as type, a.content, u.username, a.created_at 
        FROM answers a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.created_at BETWEEN ? AND ?
        ORDER BY created_at DESC 
        LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$recent_activity = mysqli_stmt_get_result($stmt);

$page_title = "Reports & Analytics";
ob_start();
?>

<div class="container">
    <!-- Date Range Filter - Compact -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label small fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>Start Date
                            </label>
                            <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>" max="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label small fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>End Date
                            </label>
                            <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>" min="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter me-1"></i>Apply Filter
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Filter reports by date range
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards - Compact Admin Style -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); animation-delay: 0.1s;">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Users</div>
                    <div class="stats-number"><?php echo $user_stats['total_users']; ?></div>
                    <div class="stats-label">S: <?php echo $user_stats['total_students']; ?> | Sp: <?php echo $user_stats['total_specialists']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); animation-delay: 0.2s;">
                <div class="stats-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Questions</div>
                    <div class="stats-number"><?php echo $question_stats['total_questions']; ?></div>
                    <div class="stats-label">Open: <?php echo $question_stats['open_questions']; ?> | Closed: <?php echo $question_stats['closed_questions']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%); animation-delay: 0.3s;">
                <div class="stats-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Answers</div>
                    <div class="stats-number"><?php echo $answer_stats['total_answers']; ?></div>
                    <div class="stats-label">Responses Given</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); animation-delay: 0.4s;">
                <div class="stats-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Applications</div>
                    <div class="stats-number"><?php echo $application_stats['total_applications']; ?></div>
                    <div class="stats-label">P: <?php echo $application_stats['pending_applications']; ?> | A: <?php echo $application_stats['approved_applications']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Questions by Category - Full Width -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Questions by Category
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($category_stats) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover reports-category-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-center">Questions</th>
                                        <th class="text-center">Percentage</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($category = mysqli_fetch_assoc($category_stats)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?php echo $category['question_count']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                    $percentage = $question_stats['total_questions'] > 0 
                                                        ? round(($category['question_count'] / $question_stats['total_questions']) * 100, 1) 
                                                        : 0;
                                                    echo '<strong>' . $percentage . '%</strong>';
                                                ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%"
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                            <h6>No Categories Found</h6>
                            <p class="text-muted mb-0">Add some categories to see statistics</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity - Full Width with Horizontal Scroll -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_activity) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover reports-activity-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Content</th>
                                        <th>User</th>
                                        <th>Date & Time</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($activity = mysqli_fetch_assoc($recent_activity)): ?>
                                        <tr>
                                            <td>
                                                <?php if($activity['type'] === 'question'): ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-question-circle me-1"></i>Question
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-comment me-1"></i>Answer
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="content-cell" title="<?php echo htmlspecialchars($activity['content']); ?>">
                                                <?php echo htmlspecialchars(substr($activity['content'], 0, 80) . (strlen($activity['content']) > 80 ? '...' : '')); ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M d, Y', strtotime($activity['created_at'])); ?><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($activity['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">General</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Active</span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h6>No Recent Activity</h6>
                            <p class="text-muted mb-0">Activity will appear here as users interact with the platform</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern compact admin-style stats cards now loaded from layout.php -->

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 