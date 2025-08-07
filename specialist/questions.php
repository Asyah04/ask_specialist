<?php
session_start();

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "specialist"){
    header("location: ../login.php");
    exit;
}

// Include database configuration
require_once "../config/database.php";

// Get specialist's category
$sql = "SELECT category_id FROM specialist_applications WHERE user_id = ? AND status = 'approved'";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $category = mysqli_fetch_assoc($result);
    }
}

// Get questions for specialist's category
$sql = "SELECT q.*, u.username as asker_name, 
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id AND user_id = ?) as has_answered
        FROM questions q 
        LEFT JOIN users u ON q.user_id = u.id 
        WHERE q.category_id = ? 
        ORDER BY q.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION["id"], $category['category_id']);
    if(mysqli_stmt_execute($stmt)){
        $questions = mysqli_stmt_get_result($stmt);
    }
}

$page_title = "Questions in My Category";
ob_start();
?>

<div class="container">
    <!-- <div class="row mb-4">
        <div class="col">
            <h2>Questions in My Category</h2>
        </div>
    </div> -->

    <?php if(mysqli_num_rows($questions) > 0): ?>
        <div class="row">
            <?php while($question = mysqli_fetch_assoc($questions)): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($question['title']); ?></h5>
                            <p class="card-text text-muted">
                                <small>
                                    <i class="fas fa-user"></i> Asked by <?php echo htmlspecialchars($question['asker_name']); ?> |
                                    <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($question['created_at'])); ?> |
                                    <i class="fas fa-comments"></i> <?php echo $question['answer_count']; ?> answers
                                </small>
                            </p>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($question['content'], 0, 200))); ?>...</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?php echo $question['status'] === 'open' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($question['status']); ?>
                                </span>
                                <?php if($question['has_answered'] > 0): ?>
                                    <span class="badge bg-info">You have answered</span>
                                <?php endif; ?>
                                <div>
                                    <?php if($question['status'] === 'open'): ?>
                                        <a href="../answer.php?question_id=<?php echo $question['id']; ?>" class="btn btn-primary btn-sm">
                                            Answer Question
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No questions available in your category at the moment.
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 