<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database configuration
require_once "config/database.php";

// Get user's questions
$sql = "SELECT q.*, c.name as category_name, 
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count 
        FROM questions q 
        LEFT JOIN categories c ON q.category_id = c.id 
        WHERE q.user_id = ? 
        ORDER BY q.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
    }
}

$page_title = "My Questions";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <!-- <div class="col">
            <h2>My Questions</h2>
        </div> -->
        <div class="col text-end">
            <a href="ask_question.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ask New Question
            </a>
        </div>
    </div>

    <?php if(mysqli_num_rows($result) > 0): ?>
        <div class="row">
            <?php while($question = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($question['title']); ?></h5>
                            <p class="card-text text-muted">
                                <small>
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($question['category_name']); ?> |
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($question['created_at'])); ?> |
                                    <i class="fas fa-comments"></i> <?php echo $question['answer_count']; ?> answers
                                </small>
                            </p>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($question['content'], 0, 200))); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?php echo $question['status'] === 'open' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($question['status']); ?>
                                </span>
                                <a href="view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    View Answes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You haven't asked any questions yet.
            <a href="ask_question.php" class="alert-link">Ask your first question</a>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 