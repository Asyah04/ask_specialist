<?php
session_start();

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "specialist"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Get specialist's category and email
$sql = "SELECT c.id, c.name as category_name, u.email 
        FROM specialist_applications sa 
        JOIN categories c ON sa.category_id = c.id 
        JOIN users u ON sa.user_id = u.id 
        WHERE sa.user_id = ? AND sa.status = 'approved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);

// Get vote statistics
$sql = "SELECT 
            COUNT(DISTINCT a.id) as total_answers,
            SUM(CASE WHEN av.vote_type = 'like' THEN 1 ELSE 0 END) as total_likes,
            SUM(CASE WHEN av.vote_type = 'dislike' THEN 1 ELSE 0 END) as total_dislikes,
            COUNT(DISTINCT CASE WHEN av.vote_type = 'like' THEN a.id END) as liked_answers,
            COUNT(DISTINCT CASE WHEN av.vote_type = 'dislike' THEN a.id END) as disliked_answers
        FROM answers a 
        LEFT JOIN answer_votes av ON a.id = av.answer_id 
        WHERE a.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$stats = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Get recent answers with vote counts
$sql = "SELECT a.*, q.title as question_title, q.id as question_id,
        (SELECT COUNT(*) FROM answer_votes WHERE answer_id = a.id AND vote_type = 'like') as likes,
        (SELECT COUNT(*) FROM answer_votes WHERE answer_id = a.id AND vote_type = 'dislike') as dislikes
        FROM answers a 
        JOIN questions q ON a.question_id = q.id 
        WHERE a.user_id = ? 
        ORDER BY a.created_at DESC 
        LIMIT 10";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$recent_answers = mysqli_stmt_get_result($stmt);

$page_title = "My Profile";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card profile-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="profile-info">
                            <h4 class="mb-1"><?php echo htmlspecialchars($_SESSION["username"]); ?></h4>
                            <p class="text-muted mb-1">
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($category['email']); ?>
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-graduation-cap me-1"></i>
                                <?php echo htmlspecialchars($category['category_name']); ?> Specialist
                            </p>
                        </div>
                        <div class="profile-circle">
                            <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col">
                            <div class="stat-item">
                                <i class="fas fa-reply stat-icon answers-icon"></i>
                                <h5 class="mb-0"><?php echo $stats['total_answers']; ?></h5>
                                <small class="text-muted">Answers</small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <i class="fas fa-thumbs-up stat-icon likes-icon"></i>
                                <h5 class="mb-0"><?php echo $stats['total_likes']; ?></h5>
                                <small class="text-muted">Likes</small>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <i class="fas fa-thumbs-down stat-icon dislikes-icon"></i>
                                <h5 class="mb-0"><?php echo $stats['total_dislikes']; ?></h5>
                                <small class="text-muted">Dislikes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Answers</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_answers) > 0): ?>
                        <div class="list-group">
                            <?php while($answer = mysqli_fetch_assoc($recent_answers)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <a href="../question.php?id=<?php echo $answer['question_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($answer['question_title']); ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($answer['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?php echo substr(htmlspecialchars($answer['content']), 0, 200) . '...'; ?></p>
                                    <div class="vote-stats">
                                        <span class="text-success me-2">
                                            <i class="fas fa-thumbs-up"></i> <?php echo $answer['likes']; ?>
                                        </span>
                                        <span class="text-danger">
                                            <i class="fas fa-thumbs-down"></i> <?php echo $answer['dislikes']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No answers yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.vote-stats {
    font-size: 0.9rem;
}

.vote-stats i {
    margin-right: 0.25rem;
}

.list-group-item {
    border-left: 3px solid #007bff;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.profile-card {
    max-width: 500px;
    margin: 0 auto;
    border: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    background: linear-gradient(to bottom right, #ffffff, #f8f9fa);
}

.profile-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(45deg, #4a90e2, #67b26f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.2);
    transition: all 0.3s ease;
}

.profile-circle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(74, 144, 226, 0.3);
}

.profile-info h4 {
    color: #4a90e2;
    font-weight: 600;
}

.profile-info p {
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.profile-info p i {
    color: #67b26f;
    width: 16px;
}

.profile-info p .fa-envelope {
    color: #4a90e2;
}

hr {
    margin: 1.5rem 0;
    opacity: 0.1;
}

.stat-item {
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.stat-item:hover {
    background-color: rgba(74, 144, 226, 0.05);
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.answers-icon {
    color: #4a90e2;
}

.likes-icon {
    color: #67b26f;
}

.dislikes-icon {
    color: #e25c5c;
}

.col h5 {
    color: #4a90e2;
    font-weight: 600;
}

.col small {
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .profile-card {
        max-width: 100%;
    }
    
    .profile-circle {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .profile-info h4 {
        font-size: 1.2rem;
    }

    .stat-icon {
        font-size: 1.2rem;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 