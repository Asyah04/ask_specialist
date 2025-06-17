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
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>" max="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>" min="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <h2 class="card-text"><?php echo $user_stats['total_users']; ?></h2>
                    <p class="card-text">
                        Students: <?php echo $user_stats['total_students']; ?><br>
                        Specialists: <?php echo $user_stats['total_specialists']; ?><br>
                        Admins: <?php echo $user_stats['total_admins']; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Questions</h5>
                    <h2 class="card-text"><?php echo $question_stats['total_questions']; ?></h2>
                    <p class="card-text">
                        Open: <?php echo $question_stats['open_questions']; ?><br>
                        Closed: <?php echo $question_stats['closed_questions']; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Answers</h5>
                    <h2 class="card-text"><?php echo $answer_stats['total_answers']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Applications</h5>
                    <h2 class="card-text"><?php echo $application_stats['total_applications']; ?></h2>
                    <p class="card-text">
                        Pending: <?php echo $application_stats['pending_applications']; ?><br>
                        Approved: <?php echo $application_stats['approved_applications']; ?><br>
                        Rejected: <?php echo $application_stats['rejected_applications']; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Questions by Category -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Questions by Category</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($category_stats) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Questions</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($category = mysqli_fetch_assoc($category_stats)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo $category['question_count']; ?></td>
                                            <td>
                                                <?php 
                                                    $percentage = $question_stats['total_questions'] > 0 
                                                        ? round(($category['question_count'] / $question_stats['total_questions']) * 100, 1) 
                                                        : 0;
                                                    echo $percentage . '%';
                                                ?>
                                            </td>
                                        </tr>
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

        <!-- Recent Activity -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_activity) > 0): ?>
                        <div class="list-group">
                            <?php while($activity = mysqli_fetch_assoc($recent_activity)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php if($activity['type'] === 'question'): ?>
                                                    <i class="fas fa-question-circle text-primary me-2"></i>
                                                    New Question
                                                <?php else: ?>
                                                    <i class="fas fa-comment text-success me-2"></i>
                                                    New Answer
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($activity['content']); ?></p>
                                            <small class="text-muted">
                                                By <?php echo htmlspecialchars($activity['username']); ?> on 
                                                <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No recent activity</p>
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