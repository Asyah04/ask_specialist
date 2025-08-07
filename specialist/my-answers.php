<?php
session_start();

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "specialist"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Get questions that the specialist has answered
$sql = "SELECT q.*, u.username, a.created_at as answer_date, a.content as answer_content, a.id as answer_id,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as total_answers
        FROM questions q 
        JOIN answers a ON q.id = a.question_id 
        JOIN users u ON q.user_id = u.id 
        WHERE a.user_id = ? 
        ORDER BY a.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$questions = mysqli_stmt_get_result($stmt);

$page_title = "My Answers";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Questions I've Answered</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($questions) > 0): ?>
                        <div class="list-group">
                            <?php while($question = mysqli_fetch_assoc($questions)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">
                                                <a href="../question.php?id=<?php echo $question['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($question['title']); ?>
                                                </a>
                                            </h5>
                                            <p class="mb-1"><?php echo substr(htmlspecialchars($question['content']), 0, 200) . '...'; ?></p>
                                            <small class="text-muted">
                                                Asked by <?php echo htmlspecialchars($question['username']); ?> • 
                                                <?php echo date('M d, Y', strtotime($question['created_at'])); ?> • 
                                                <?php echo $question['total_answers']; ?> total answer(s)
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo $question['status'] == 'open' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($question['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <h6 class="mb-2" style="color: var(--text-color, #333);">Your Answer:</h6>
                                        <div class="p-3 rounded" style="background: var(--card-background, #f8f9fa); border: 1px solid var(--border-color, #dee2e6);">
                                            <p class="mb-1" style="color: var(--text-color, #333);"><?php echo substr(htmlspecialchars($question['answer_content']), 0, 200) . '...'; ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small style="color: var(--text-muted, #6c757d);">
                                                    Answered on <?php echo date('M d, Y', strtotime($question['answer_date'])); ?>
                                                </small>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="../view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> View Full Question
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-reply fa-3x text-muted mb-3"></i>
                            <h5>No Answers Yet</h5>
                            <p class="text-muted">You haven't answered any questions yet.</p>
                            <a href="questions.php" class="btn btn-primary mt-3">
                                <i class="fas fa-question-circle me-1"></i> Browse Questions
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Answers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="get">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Question Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Answer Date Range</label>
                            <div class="input-group">
                                <input type="date" name="start_date" class="form-control">
                                <span class="input-group-text">to</span>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 