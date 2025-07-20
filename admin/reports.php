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
    <div class="row equal-height mb-4">
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #4DB6AC 0%, #80CBC4 100%);">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Total Users</h5>
                    <h2><?php echo $user_stats['total_users']; ?></h2>
                    <p>Students: <?php echo $user_stats['total_students']; ?></p>
                    <p>Specialists: <?php echo $user_stats['total_specialists']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #66BB6A 0%, #4DB6AC 100%);">
                <div class="stats-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Total Questions</h5>
                    <h2><?php echo $question_stats['total_questions']; ?></h2>
                    <p>Open: <?php echo $question_stats['open_questions']; ?></p>
                    <p>Closed: <?php echo $question_stats['closed_questions']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #26A69A 0%, #4DB6AC 100%);">
                <div class="stats-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Total Answers</h5>
                    <h2><?php echo $answer_stats['total_answers']; ?></h2>
                    <p>Responses Given</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #80CBC4 0%, #B2DFDB 100%);">
                <div class="stats-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Applications</h5>
                    <h2><?php echo $application_stats['total_applications']; ?></h2>
                    <p>Pending: <?php echo $application_stats['pending_applications']; ?></p>
                    <p>Approved: <?php echo $application_stats['approved_applications']; ?></p>
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

<style>
.stats-card {
    border-radius: 15px;
    padding: 25px;
    color: white;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    height: 180px; /* Fixed height for all cards */
    display: flex;
    align-items: center;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    z-index: 1;
}

.stats-icon {
    font-size: 2.5rem;
    margin-right: 20px;
    opacity: 0.8;
    z-index: 2;
    width: 60px; /* Fixed width for icons */
    text-align: center;
}

.stats-info {
    flex: 1;
    z-index: 2;
    min-width: 0; /* Prevents text overflow */
}

.stats-card h5 {
    font-size: 1rem;
    margin-bottom: 10px;
    opacity: 0.9;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stats-card h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    line-height: 1.2;
}

.stats-card p {
    font-size: 0.9rem;
    margin: 0;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Ensure equal width columns */
.row.equal-height {
    display: flex;
    flex-wrap: wrap;
}

.row.equal-height > [class*='col-'] {
    display: flex;
    flex-direction: column;
}

.row.equal-height > [class*='col-'] > .stats-card {
    flex: 1;
    width: 100%;
}

@media (max-width: 768px) {
    .stats-card {
        height: 160px; /* Slightly smaller height on mobile */
        padding: 20px;
    }
    
    .stats-icon {
        font-size: 2rem;
        width: 50px;
    }
    
    .stats-card h2 {
        font-size: 2rem;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 