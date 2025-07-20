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

<div class="container py-5">
    <!-- Kisasa: Header ya Admin -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-0 bg-gradient-primary text-white" style="background: linear-gradient(90deg, rgb(255, 255, 255) 0%, rgb(255, 255, 255) 100%);">
                <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1 fw-bold">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h3>
                        <p class="card-text mb-0">You are logged in as <span class="fw-semibold">Admin</span></p>
                    </div>
                    <img src="https://img.icons8.com/color/96/000000/admin-settings-male.png" alt="Admin" class="ms-md-4 d-none d-md-block" style="height:60px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-people-fill fs-2 text-primary"></i></div>
                    <h6 class="card-title text-muted">Total Students</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_students']; ?></h2>
                    <p class="text-muted mb-0">Registered Students</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-person-badge-fill fs-2 text-success"></i></div>
                    <h6 class="card-title text-muted">Total Specialists</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_specialists']; ?></h2>
                    <p class="text-muted mb-0">Active Specialists</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-hourglass-split fs-2 text-warning"></i></div>
                    <h6 class="card-title text-muted">Pending Applications</h6>
                    <h2 class="fw-bold"><?php echo $stats['pending_applications']; ?></h2>
                    <p class="text-muted mb-0">Awaiting Review</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-question-circle-fill fs-2 text-info"></i></div>
                    <h6 class="card-title text-muted">Total Questions</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_questions']; ?></h2>
                    <p class="text-muted mb-0">Questions Asked</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Applications
        <div class="col-md-6 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-file-earmark-person me-2"></i>Recent Applications</h5>
                    <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_applications) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($app = mysqli_fetch_assoc($recent_applications)): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($app['username']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['email']); ?></small>
                                    </div>
                                    <a href="review_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> Review
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No pending applications</p>
                    <?php endif; ?>
                </div>
            </div>
        </div> -->

        <!-- Recent Questions -->
        <!-- <div class="col-md-6 mb-4">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-transparent border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold"><i class="bi bi-question-circle me-2"></i>Recent Questions</h5>
                    <a href="questions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_questions) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($question = mysqli_fetch_assoc($recent_questions)): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($question['title']); ?></h6>
                                        <small class="text-muted">
                                            By <?php echo htmlspecialchars($question['username']); ?> in 
                                            <?php echo htmlspecialchars($question['category_name']); ?>
                                        </small>
                                    </div>
                                    <a href="../view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">No questions yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div> -->
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