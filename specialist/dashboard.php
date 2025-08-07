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
            (SELECT COUNT(*) FROM answer_votes WHERE answer_id IN (SELECT id FROM answers WHERE user_id = ?) AND vote_type = 'like') as total_likes,
            (SELECT COUNT(*) FROM answer_votes WHERE answer_id IN (SELECT id FROM answers WHERE user_id = ?) AND vote_type = 'dislike') as total_dislikes";
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

// Store recent answers data in array for reuse
$recent_answers_data = [];
while($answer = mysqli_fetch_assoc($recent_answers)) {
    $recent_answers_data[] = $answer;
}

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

// Store open questions data in array for reuse
$open_questions_data = [];
while($question = mysqli_fetch_assoc($open_questions)) {
    $open_questions_data[] = $question;
}

$page_title = "Specialist Dashboard";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="min-height: auto; max-height: auto;"> 
                <div class="card-body">
                    <h5 class="card-title">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h5>
                    <p class="card-text">You are a specialist in <strong><?php echo htmlspecialchars($category['category_name']); ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Statistics Cards -->
    <div class="row equal-height mb-4">
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: var(--gradient-primary); animation-delay: 0.1s; ">
                <div class="stats-icon">
                    <i class="fas fa-reply"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Answers</div>
                    <div class="stats-number"><?php echo $stats['total_answers']; ?></div>
                    <div class="stats-label">Answers Given</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: var(--gradient-success); animation-delay: 0.2s;">
                <div class="stats-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Questions</div>
                    <div class="stats-number"><?php echo $stats['total_questions']; ?></div>
                    <div class="stats-label">In Your Category</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: var(--gradient-info); animation-delay: 0.3s;">
                <div class="stats-icon">
                    <i class="fas fa-thumbs-up"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Likes</div>
                    <div class="stats-number"><?php echo $stats['total_likes']; ?></div>
                    <div class="stats-label">On Your Answers</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card fade-in" style="background: var(--gradient-danger); animation-delay: 0.4s;">
                <div class="stats-icon">
                    <i class="fas fa-thumbs-down"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Dislikes</div>
                    <div class="stats-number"><?php echo $stats['total_dislikes']; ?></div>
                    <div class="stats-label">On Your Answers</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Answers - Full Width Top -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-reply me-2"></i>Recent Answers
                    </h5>
                    <a href="../questions.php" class="btn btn-sm btn-primary">View All Questions</a>
                </div>
                <div class="card-body">
                    <?php if(count($recent_answers_data) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover specialist-answers-table">
                                <thead>
                                    <tr>
                                        <th>Question Title</th>
                                        <th>Your Answer Preview</th>
                                        <th>Date Answered</th>
                                        <th>Question Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_answers_data as $answer): ?>
                                        <tr>
                                            <td class="question-title-cell" title="<?php echo htmlspecialchars($answer['question_title']); ?>">
                                                <strong><?php echo htmlspecialchars(substr($answer['question_title'], 0, 50) . (strlen($answer['question_title']) > 50 ? '...' : '')); ?></strong>
                                            </td>
                                            <td class="answer-preview-cell" title="<?php echo htmlspecialchars($answer['content']); ?>">
                                                <?php echo htmlspecialchars(substr($answer['content'], 0, 80) . (strlen($answer['content']) > 80 ? '...' : '')); ?>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M d, Y', strtotime($answer['created_at'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $answer['question_status'] === 'open' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo ucfirst($answer['question_status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="../answer.php?question_id=<?php echo $answer['question_id']; ?>" class="btn btn-sm btn-primary" title="View Question & Answer">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-reply fa-3x text-muted mb-3"></i>
                            <h6>No Answers Yet</h6>
                            <p class="text-muted mb-0">Start answering questions to help users and build your reputation</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Open Questions - Full Width Below -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>Open Questions in Your Category
                    </h5>
                    <span class="badge bg-info"><?php echo htmlspecialchars($category['category_name']); ?></span>
                </div>
                <div class="card-body">
                    <?php if(count($open_questions_data) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover specialist-questions-table">
                                <thead>
                                    <tr>
                                        <th>Question Title</th>
                                        <th>Asked By</th>
                                        <th>Content Preview</th>
                                        <th>Answers</th>
                                        <th>Asked Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($open_questions_data as $question): ?>
                                        <tr>
                                            <td class="question-title-cell" title="<?php echo htmlspecialchars($question['title']); ?>">
                                                <strong><?php echo htmlspecialchars(substr($question['title'], 0, 40) . (strlen($question['title']) > 40 ? '...' : '')); ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-user me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($question['username']); ?>
                                            </td>
                                            <td class="content-preview-cell" title="<?php echo htmlspecialchars($question['content']); ?>">
                                                <?php echo htmlspecialchars(substr($question['content'], 0, 60) . (strlen($question['content']) > 60 ? '...' : '')); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">
                                                    <?php echo $question['answer_count']; ?>
                                                </span>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php echo date('M d, Y', strtotime($question['created_at'])); ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="../answer.php?question_id=<?php echo $question['id']; ?>" class="btn btn-sm btn-success" title="Answer This Question">
                                                    <i class="fas fa-reply me-1"></i> Answer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <h6>No Open Questions</h6>
                            <p class="text-muted mb-0">No open questions in your category at the moment</p>
                        </div>
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