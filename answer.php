<?php
session_start();

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "specialist"){
    header("location: login.php");
    exit;
}

// Include database configuration
require_once "config/database.php";

// Check if question_id is provided
if(!isset($_GET['question_id'])){
    header("location: specialist/questions.php");
    exit;
}

$question_id = $_GET['question_id'];

// Get question details
$sql = "SELECT q.*, c.name as category_name, u.username as asker_name 
        FROM questions q 
        JOIN categories c ON q.category_id = c.id 
        JOIN users u ON q.user_id = u.id 
        WHERE q.id = ?";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $question_id);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        $question = mysqli_fetch_assoc($result);
    }
}

// Get all answers for this question
$sql = "SELECT a.*, u.username as answerer_name, u.role as answerer_role
        FROM answers a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.question_id = ? 
        ORDER BY a.created_at ASC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $question_id);
    if(mysqli_stmt_execute($stmt)){
        $answers = mysqli_stmt_get_result($stmt);
    }
}

// Check if question exists and is in specialist's category
$sql = "SELECT 1 FROM specialist_applications 
        WHERE user_id = ? AND category_id = ? AND status = 'approved'";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $_SESSION["id"], $question['category_id']);
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        if(mysqli_num_rows($result) == 0){
            header("location: specialist/questions.php");
            exit;
        }
    }
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $answer = trim($_POST["answer"]);
    
    if(!empty($answer)){
        $sql = "INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "iis", $question_id, $_SESSION["id"], $answer);
            if(mysqli_stmt_execute($stmt)){
                header("location: answer.php?question_id=" . $question_id);
                exit;
            } else {
                $error_message = "Error posting answer: " . mysqli_error($conn);
                error_log("Error posting answer: " . mysqli_error($conn));
            }
        } else {
            $error_message = "Error preparing statement: " . mysqli_error($conn);
            error_log("Error preparing statement: " . mysqli_error($conn));
        }
    } else {
        $error_message = "Answer cannot be empty";
    }
}

$page_title = "Question and Answers";
ob_start();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <!-- Question Section -->
                <div class="card-header">
                    <h4 class="mb-0"><?php echo htmlspecialchars($question['title']); ?></h4>
                </div>
                <div class="card-body">
                    <div class="question-details mb-4">
                        <p class="text-muted">
                            <small>
                                <i class="fas fa-user"></i> Asked by <?php echo htmlspecialchars($question['asker_name']); ?> |
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($question['category_name']); ?> |
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y', strtotime($question['created_at'])); ?>
                            </small>
                        </p>
                        <p class="question-content"><?php echo nl2br(htmlspecialchars($question['content'])); ?></p>
                    </div>

                    <!-- Answers Section -->
                    <div class="answers-section mb-4">
                        <h5 class="mb-3">Answers (<?php echo mysqli_num_rows($answers); ?>)</h5>
                        <?php if(mysqli_num_rows($answers) > 0): ?>
                            <?php while($answer = mysqli_fetch_assoc($answers)): ?>
                                <div class="answer-item mb-3 p-3 border rounded <?php echo $answer['user_id'] == $_SESSION['id'] ? 'bg-light' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($answer['answerer_name']); ?></strong>
                                            <span class="badge bg-<?php echo $answer['answerer_role'] === 'specialist' ? 'primary' : 'secondary'; ?> ms-2">
                                                <?php echo ucfirst($answer['answerer_role']); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y H:i', strtotime($answer['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($answer['content'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No answers yet. Be the first to answer!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Answer Form Section -->
                    <?php if($question['status'] === 'open'): ?>
                        <div class="answer-form-section">
                            <h5 class="mb-3">Your Answer</h5>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?question_id=' . $question_id; ?>" method="post">
                                <div class="mb-3">
                                    <textarea class="form-control" id="answer" name="answer" rows="4" required 
                                              placeholder="Write your answer here..."></textarea>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <a href="specialist/questions.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Questions
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-1"></i> Submit Answer
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary">
                            <i class="fas fa-lock"></i> This question is closed and no longer accepting answers.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?>
