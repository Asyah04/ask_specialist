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

// Recent applications
$sql = "SELECT sa.*, u.username, u.email 
        FROM specialist_applications sa 
        JOIN users u ON sa.user_id = u.id 
        WHERE sa.status = 'pending' 
        ORDER BY sa.created_at DESC 
        LIMIT 5";
$recent_applications = mysqli_query($conn, $sql);

// Recent questions
$sql = "SELECT q.*, u.username, c.name as category_name 
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        JOIN categories c ON q.category_id = c.id 
        ORDER BY q.created_at DESC 
        LIMIT 5";
$recent_questions = mysqli_query($conn, $sql);

$page_title = "Admin Dashboard";
ob_start();
?>

<div class="container">
    <!-- Statistics Cards -->
    <div class="row equal-height mb-4">
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg,rgb(175, 179, 250) 0%,rgb(170, 174, 248) 100%);">
                <div class="stats-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Total Students</h5>
                    <h2><?php echo $stats['total_students']; ?></h2>
                    <p>Registered Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg,rgb(249, 163, 163) 0%,rgb(251, 169, 173) 100%);">
                <div class="stats-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Total Specialists</h5>
                    <h2><?php echo $stats['total_specialists']; ?></h2>
                    <p>Active Specialists</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg,rgb(160, 207, 249) 0%,rgb(150, 242, 247) 100%);">
                <div class="stats-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Pending Applications</h5>
                    <h2><?php echo $stats['pending_applications']; ?></h2>
                    <p>Awaiting Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg,rgb(165, 247, 192) 0%,rgb(153, 250, 233) 100%);">
                <div class="stats-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stats-info">
                    <h5 class="card-title">Total Questions</h5>
                    <h2><?php echo $stats['total_questions']; ?></h2>
                    <p>Questions Asked</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row equal-height">
        <!-- Recent Applications -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applications</h5>
                    <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_applications) > 0): ?>
                        <div class="list-group">
                            <?php while($app = mysqli_fetch_assoc($recent_applications)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($app['username']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($app['email']); ?></small>
                                        </div>
                                        <a href="review_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> Review
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No pending applications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Questions -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Recent Questions</h5>
                    <a href="questions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_questions) > 0): ?>
                        <div class="list-group">
                            <?php while($question = mysqli_fetch_assoc($recent_questions)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($question['title']); ?></h6>
                                            <small class="text-muted">
                                                By <?php echo htmlspecialchars($question['username']); ?> in 
                                                <?php echo htmlspecialchars($question['category_name']); ?>
                                            </small>
                                        </div>
                                        <a href="../view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No questions yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row equal-height">
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