<?php
session_start();

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "specialist"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Get specialist's category
$sql = "SELECT c.id, c.name as category_name 
        FROM specialist_applications sa 
        JOIN categories c ON sa.category_id = c.id 
        WHERE sa.user_id = ? AND sa.status = 'approved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$category = mysqli_fetch_assoc($result);

// Get statistics
$sql = "SELECT 
            (SELECT COUNT(*) FROM answers WHERE user_id = ?) as total_answers,
            (SELECT COUNT(*) FROM questions WHERE category_id = ?) as total_questions,
            (SELECT COUNT(*) FROM questions WHERE category_id = ? AND status = 'open') as open_category_questions,
            (SELECT COUNT(*) FROM answer_votes av 
             JOIN answers a ON av.answer_id = a.id 
             WHERE a.user_id = ? AND av.vote_type = 'like') as total_likes,
            (SELECT COUNT(*) FROM answer_votes av 
             JOIN answers a ON av.answer_id = a.id 
             WHERE a.user_id = ? AND av.vote_type = 'dislike') as total_dislikes";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iiiii", $_SESSION["id"], $category['id'], $category['id'], $_SESSION["id"], $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$stats = mysqli_stmt_get_result($stmt)->fetch_assoc();

// Get recent answers
$sql = "SELECT a.*, q.title as question_title, q.status as question_status 
        FROM answers a 
        JOIN questions q ON a.question_id = q.id 
        WHERE a.user_id = ? 
        ORDER BY a.created_at DESC 
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$recent_answers = mysqli_stmt_get_result($stmt);

// Get open questions in specialist's category
$sql = "SELECT q.*, u.username, 
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count 
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        WHERE q.category_id = ? AND q.status = 'open' 
        ORDER BY q.created_at DESC 
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $category['id']);
mysqli_stmt_execute($stmt);
$open_questions = mysqli_stmt_get_result($stmt);

$page_title = "Specialist Dashboard";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h5>
                    <p class="card-text">You are a specialist in <strong><?php echo htmlspecialchars($category['category_name']); ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Answers</h6>
                    <h2 class="mb-0"><?php echo $stats['total_answers']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Questions</h6>
                    <h2 class="mb-0"><?php echo $stats['total_questions']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Likes</h6>
                    <h2 class="mb-0"><?php echo $stats['total_likes']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Dislikes</h6>
                    <h2 class="mb-0"><?php echo $stats['total_dislikes']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Answers</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_answers) > 0): ?>
                        <div class="list-group">
                            <?php while($answer = mysqli_fetch_assoc($recent_answers)): ?>
                                <a href="../answer.php?question_id=<?php echo $answer['question_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($answer['question_title']); ?></h6>
                                        <small><?php echo date('M d, Y', strtotime($answer['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo substr(htmlspecialchars($answer['content']), 0, 100) . '...'; ?></p>
                                    <small class="text-muted">Question Status: <?php echo ucfirst($answer['question_status']); ?></small>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No answers yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Open Questions in Your Category</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($open_questions) > 0): ?>
                        <div class="list-group">
                            <?php while($question = mysqli_fetch_assoc($open_questions)): ?>
                                <a href="../answer.php?question_id=<?php echo $question['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($question['title']); ?></h6>
                                        <small><?php echo date('M d, Y', strtotime($question['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo substr(htmlspecialchars($question['content']), 0, 100) . '...'; ?></p>
                                    <small class="text-muted">
                                        Asked by <?php echo htmlspecialchars($question['username']); ?> • 
                                        <?php echo $question['answer_count']; ?> answer(s)
                                    </small>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No open questions in your category.</p>
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