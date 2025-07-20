<?php
session_start();

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "specialist"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";


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

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow border-0 bg-gradient-primary text-white" style="background: linear-gradient(90deg,rgb(7, 245, 205) 0%,rgb(118, 234, 222) 100%);">
                <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between" style="background: #fff; color: #1976d2;">
                    <div>
                        <h3 class="card-title mb-1 fw-bold" style="color: #1976d2;">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h3>
                        <p class="card-text mb-0" style="color: #222;">You are now a <span class="fw-semibold" style="color: #1976d2;"><?php echo htmlspecialchars($category['category_name']); ?> Specialist</span></p>
                    </div>
                    <img src="https://img.icons8.com/color/96/000000/verified-badge.png" alt="Specialist" class="ms-md-4 d-none d-md-block" style="height:60px;">
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-chat-dots-fill fs-2 text-primary"></i></div>
                    <h6 class="card-title text-muted">All Answers</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_answers']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-question-circle-fill fs-2 text-success"></i></div>
                    <h6 class="card-title text-muted">All Questions</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_questions']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-hand-thumbs-up-fill fs-2 text-info"></i></div>
                    <h6 class="card-title text-muted">Likes</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_likes']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 text-center h-100">
                <div class="card-body">
                    <div class="mb-2"><i class="bi bi-hand-thumbs-down-fill fs-2 text-warning"></i></div>
                    <h6 class="card-title text-muted">Dislikes</h6>
                    <h2 class="fw-bold"><?php echo $stats['total_dislikes']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-transparent border-bottom-0">
                    <h5 class="mb-0 fw-semibold">Your recent answers</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($recent_answers) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($answer = mysqli_fetch_assoc($recent_answers)): ?>
                                <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <div>
                                        <a href="../answer.php?question_id=<?php echo $answer['question_id']; ?>" class="fw-semibold text-decoration-none text-primary">
                                            <?php echo htmlspecialchars($answer['question_title']); ?>
                                        </a>
                                        <div class="small text-muted"><?php echo substr(htmlspecialchars($answer['content']), 0, 80) . '...'; ?></div>
                                        <span class="badge bg-<?php echo $answer['question_status'] == 'open' ? 'success' : 'secondary'; ?> mt-1">
                                            <?php echo ucfirst($answer['question_status']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted ms-md-3"><?php echo date('M d, Y', strtotime($answer['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No answers yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow border-0 h-100">
                <div class="card-header bg-transparent border-bottom-0">
                    <h5 class="mb-0 fw-semibold">Questions in your category</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($open_questions) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($question = mysqli_fetch_assoc($open_questions)): ?>
                                <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                                    <div>
                                        <a href="../answer.php?question_id=<?php echo $question['id']; ?>" class="fw-semibold text-decoration-none text-success">
                                            <?php echo htmlspecialchars($question['title']); ?>
                                        </a>
                                        <div class="small text-muted"><?php echo substr(htmlspecialchars($question['content']), 0, 80) . '...'; ?></div>
                                        <span class="badge bg-primary mt-1">Majibu: <?php echo $question['answer_count']; ?></span>
                                    </div>
                                    <small class="text-muted ms-md-3">Asked by <?php echo htmlspecialchars($question['username']); ?> <br><?php echo date('M d, Y', strtotime($question['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No questions in your category.</p>
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