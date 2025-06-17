<?php
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin"){
    header("location: ../login.php");
    exit;
}

require_once "../config/database.php";

// Get all questions with category and user info
$sql = "SELECT q.*, c.name as category_name, u.username,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count,
        GROUP_CONCAT(
            CONCAT(
                a.id, '|',
                a.content, '|',
                a.created_at, '|',
                u2.username, '|',
                u2.role
            ) SEPARATOR '||'
        ) as answers_data
        FROM questions q 
        JOIN categories c ON q.category_id = c.id 
        JOIN users u ON q.user_id = u.id 
        LEFT JOIN answers a ON q.id = a.question_id
        LEFT JOIN users u2 ON a.user_id = u2.id
        GROUP BY q.id
        ORDER BY q.created_at DESC";
$result = mysqli_query($conn, $sql);
$questions = mysqli_fetch_all($result, MYSQLI_ASSOC);

$page_title = "Manage Questions";
ob_start();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Questions</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(count($questions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"></th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Asked By</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Answers</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($questions as $question): ?>
                                        <tr>
                                            <td>
                                                <?php if($question['answer_count'] > 0): ?>
                                                <button class="btn btn-sm btn-link text-dark p-0" 
                                                        type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#answers<?php echo $question['id']; ?>">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="../view_question.php?id=<?php echo $question['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($question['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($question['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($question['username']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($question['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $question['status'] == 'open' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($question['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $question['answer_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="../view_question.php?id=<?php echo $question['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php if($question['answer_count'] > 0): ?>
                                        <tr class="collapse-row">
                                            <td colspan="8" class="p-0">
                                                <div class="collapse" id="answers<?php echo $question['id']; ?>">
                                                    <div class="card card-body border-0 rounded-0 bg-light">
                                                        <h6 class="mb-3">Answers</h6>
                                                        <?php 
                                                        $answers = explode('||', $question['answers_data']);
                                                        foreach($answers as $answer):
                                                            list($answer_id, $content, $created_at, $username, $role) = explode('|', $answer);
                                                        ?>
                                                        <div class="answer-item mb-3 pb-3 border-bottom">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div>
                                                                    <span class="fw-bold me-2"><?php echo htmlspecialchars($username); ?></span>
                                                                    <span class="badge bg-<?php echo $role === 'specialist' ? 'success' : 'secondary'; ?>">
                                                                        <?php echo ucfirst($role); ?>
                                                                    </span>
                                                                    <small class="text-muted ms-2">
                                                                        <?php echo date('M d, Y', strtotime($created_at)); ?>
                                                                    </small>
                                                                </div>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-link text-dark p-0" type="button" data-bs-toggle="dropdown">
                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li>
                                                                            <a class="dropdown-item" href="#" onclick="editAnswer(<?php echo $answer_id; ?>)">
                                                                                <i class="fas fa-edit me-2"></i> Edit
                                                                            </a>
                                                                        </li>
                                                                        <li>
                                                                            <a class="dropdown-item text-danger" href="#" onclick="deleteAnswer(<?php echo $answer_id; ?>)">
                                                                                <i class="fas fa-trash me-2"></i> Delete
                                                                            </a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="answer-content">
                                                                <?php echo nl2br(htmlspecialchars($content)); ?>
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                            <h5>No Questions Found</h5>
                            <p class="text-muted">There are no questions in the system at this time.</p>
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
                    <h5 class="modal-title">Filter Questions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="get">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php
                                $categories = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
                                while($category = mysqli_fetch_assoc($categories)) {
                                    echo "<option value='" . $category['id'] . "'>" . htmlspecialchars($category['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
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

<style>
.collapse-row {
    background-color: #f8f9fa;
}
.collapse-row td {
    padding: 0 !important;
}
.answer-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
</style>

<script>
function deleteQuestion(id) {
    if(confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        window.location.href = 'delete_question.php?id=' + id;
    }
}

function editAnswer(id) {
    window.location.href = '../edit_answer.php?id=' + id;
}

function deleteAnswer(id) {
    if(confirm('Are you sure you want to delete this answer?')) {
        window.location.href = 'delete_answer.php?id=' + id;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once "../includes/layout.php";
?> 