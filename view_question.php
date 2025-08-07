<?php
session_start();

// Check if user is logged in and is admin or specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || 
   ($_SESSION["role"] !== "admin" && $_SESSION["role"] !== "specialist" && $_SESSION["role"] !== "asker")){
    header("location: login.php");
    exit;
}

require_once "config/database.php";

// Get question ID from URL
$question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($question_id <= 0) {
    header("location: dashboard.php");
    exit;
}

// Fetch question details
$sql = "SELECT q.*, u.username, c.name as category_name 
        FROM questions q 
        JOIN users u ON q.user_id = u.id 
        JOIN categories c ON q.category_id = c.id 
        WHERE q.id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $question_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $question = mysqli_fetch_assoc($result);
        
        if(!$question) {
            header("location: dashboard.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch answers
$sql = "SELECT a.*, u.username, u.role, a.id as answer_id
        FROM answers a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.question_id = ? 
        ORDER BY a.created_at ASC";

$answers = [];
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $question_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $answers[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

$page_title = "View Question";
ob_start();
?>

<style>
/* Fix card stretching: remove flex and set height to auto for this page only */
.card {
    display: block !important;
    height: auto !important;
    min-height: 0 !important;
    margin-bottom: 6px !important;
}
.card-body {
    display: block !important;
    padding: 8px 12px 4px 12px !important;
}
/* Ensure back button stays vertically centered and fixed height */
.d-flex.gap-2 {
    align-items: center !important;
    height: 48px;
}
.btn.btn-outline-secondary {
    height: 40px;
    min-width: 90px;
    line-height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <!-- Question Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-1"><?php echo htmlspecialchars($question['title']); ?></h1>
                    <div class="text-muted small">
                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($question['category_name']); ?></span>
                        Asked by <?php echo htmlspecialchars($question['username']); ?> 
                        on <?php echo date('F j, Y', strtotime($question['created_at'])); ?>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php
                    $dashboardHref = "dashboard.php";
                    if ($_SESSION["role"] === "admin") {
                        $dashboardHref = "admin/dashboard.php";
                    } elseif ($_SESSION["role"] === "specialist") {
                        $dashboardHref = "specialist/dashboard.php";
                    } elseif ($_SESSION["role"] === "student") {
                        $dashboardHref = "dashboard.php";
                    }
                    ?>
                    <a href="<?php echo $dashboardHref; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <?php if($_SESSION["role"] === "admin"): ?>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Question Content -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="question-content mb-4">
                        <?php echo nl2br(htmlspecialchars($question['content'])); ?>
                    </div>
                    
                    <?php if($question['image_path']): ?>
                    <div class="question-image mb-3">
                        <img src="<?php echo htmlspecialchars($question['image_path']); ?>" 
                             class="img-fluid rounded" 
                             alt="Question attachment">
                    </div>
                    <?php endif; ?>

                    <!-- Question Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex gap-2">
                            <?php if ($_SESSION["role"] === "specialist"): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleAnswerForm()">
                                <i class="fas fa-reply me-1"></i> Answer
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleComments()">
                                <i class="fas fa-comments me-1"></i> Comments
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Answer Form (Hidden by default) -->
            <?php if ($_SESSION["role"] === "specialist"): ?>
            <div id="answerForm" class="card mb-4 d-none">
                <div class="card-body">
                    <h5 class="card-title mb-3">Your Answer</h5>
                    <form action="post_answer.php" method="post">
                        <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                        <div class="mb-3">
                            <textarea name="content" class="form-control" rows="6" required 
                                    placeholder="Write your answer here..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleAnswerForm()">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Post Answer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Answers Section -->
            <div class="answers-section">
                <h3 class="h4 mb-3">
                    <?php echo count($answers); ?> Answer<?php echo count($answers) !== 1 ? 's' : ''; ?>
                </h3>
                
                <?php foreach($answers as $answer): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2"><?php echo htmlspecialchars($answer['username']); ?></span>
                                    <span class="badge bg-<?php echo $answer['role'] === 'specialist' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($answer['role']); ?>
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    Answered on <?php echo date('F j, Y', strtotime($answer['created_at'])); ?>
                                </div>
                            </div>
                            <?php if($_SESSION["role"] === "admin" || $_SESSION["id"] === $answer['user_id']): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="editAnswer(<?php echo $answer['id']; ?>)">
                                            <i class="fas fa-edit me-2"></i> Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" 
                                           onclick="deleteAnswer(<?php echo $answer['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i> Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="answer-content">
                            <?php echo nl2br(htmlspecialchars($answer['content'])); ?>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Question Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this question? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete_question.php" method="post" class="d-inline">
                    <input type="hidden" name="question_id" value="<?php echo $question_id; ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAnswerForm() {
    const form = document.getElementById('answerForm');
    form.classList.toggle('d-none');
}

function toggleComments() {
    // Implement comments functionality
    alert('Comments feature coming soon!');
}

function editAnswer(answerId) {
    // Implement edit answer functionality
    window.location.href = `edit_answer.php?id=${answerId}`;
}

function deleteAnswer(answerId) {
    if(confirm('Are you sure you want to delete this answer?')) {
        window.location.href = `delete_answer.php?id=${answerId}`;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 