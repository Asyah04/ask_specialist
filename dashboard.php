<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Get categories for filter
$categories_query = "SELECT id, name FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
while($row = mysqli_fetch_assoc($categories_result)) {
    $categories[] = $row;
}

// Get questions with answers
$sql = "SELECT q.*, c.name as category_name, u.username as asker_name,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count
        FROM questions q 
        JOIN categories c ON q.category_id = c.id 
        JOIN users u ON q.user_id = u.id 
        ORDER BY q.created_at DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
    }
}

$page_title = "Student Dashboard";
ob_start();
?>

<div class="container-fluid">
    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search questions..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="category_id" class="form-select" style="max-width: 200px;">
                    <option value="0">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if(!empty($search) || $category_id > 0): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="col-md-4 text-end">
            <a href="ask_question.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ask New Question
            </a>
        </div>
    </div>

    <!-- Questions List -->
    <div class="row-5">
        <div class="col-12">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title">
                                    <a href="view_question.php?id=<?php echo $row['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </a>
                                </h5>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($row['category_name']); ?></span>
                            </div>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($row['content']); ?>
                            </p>
                            
                            <!-- Answers Section -->
                            <?php if($row['answer_count'] > 0): ?>
                                <div class="answers-section">
                                    <div class="d-flex align-items-center" role="button" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#answers-<?php echo $row['id']; ?>" 
                                         aria-expanded="false" 
                                         aria-controls="answers-<?php echo $row['id']; ?>">
                                        <h6 class="me-2">Answers (<?php echo $row['answer_count']; ?>)</h6>
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                    <div class="collapse" id="answers-<?php echo $row['id']; ?>">
                                        <?php 
                                        // Get answers for this question
                                        $answers_sql = "SELECT a.*, u.username as answerer_name, u.role as answerer_role,
                                                        (SELECT COUNT(*) FROM answer_votes WHERE answer_id = a.id AND vote_type = 'like') as likes,
                                                        (SELECT COUNT(*) FROM answer_votes WHERE answer_id = a.id AND vote_type = 'dislike') as dislikes,
                                                        (SELECT vote_type FROM answer_votes WHERE answer_id = a.id AND user_id = ?) as user_vote
                                                        FROM answers a 
                                                        JOIN users u ON a.user_id = u.id 
                                                        WHERE a.question_id = ? 
                                                        ORDER BY a.created_at ASC";
                                        if($answers_stmt = mysqli_prepare($conn, $answers_sql)){
                                            mysqli_stmt_bind_param($answers_stmt, "ii", $_SESSION["id"], $row['id']);
                                            if(mysqli_stmt_execute($answers_stmt)){
                                                $answers_result = mysqli_stmt_get_result($answers_stmt);
                                                while($answer = mysqli_fetch_assoc($answers_result)):
                                        ?>
                                            <div class="answer-item bg-light rounded">
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">
                                                        Answered by 
                                                        <strong><?php echo htmlspecialchars($answer['answerer_name']); ?></strong>
                                                        <?php if($answer['answerer_role'] === 'specialist'): ?>
                                                            <span class="badge bg-success ms-1">Specialist</span>
                                                        <?php endif; ?>
                                                        on <?php echo date('M d, Y', strtotime($answer['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <p><?php echo htmlspecialchars($answer['content']); ?></p>
                                                <div class="vote-buttons">
                                                    <button class="btn btn-sm btn-outline-primary vote-btn <?php echo $answer['user_vote'] === 'like' ? 'active' : ''; ?>" 
                                                            data-answer-id="<?php echo $answer['id']; ?>" 
                                                            data-vote-type="like">
                                                        <i class="fas fa-thumbs-up"></i>
                                                        <span class="likes-count"><?php echo $answer['likes']; ?></span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger vote-btn <?php echo $answer['user_vote'] === 'dislike' ? 'active' : ''; ?>" 
                                                            data-answer-id="<?php echo $answer['id']; ?>" 
                                                            data-vote-type="dislike">
                                                        <i class="fas fa-thumbs-down"></i>
                                                        <span class="dislikes-count"><?php echo $answer['dislikes']; ?></span>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php 
                                                endwhile;
                                            }
                                            mysqli_stmt_close($answers_stmt);
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="text-muted">
                                    <small>
                                        Asked by <?php echo htmlspecialchars($row['asker_name']); ?> on 
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <a href="view_question.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No questions found. Be the first to ask a question!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to all answer toggles
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(element) {
        element.addEventListener('click', function() {
            const icon = this.querySelector('.fas');
            if (icon) {
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            }
        });
    });

    // Handle vote buttons
    document.querySelectorAll('.vote-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const answerId = this.dataset.answerId;
            const voteType = this.dataset.voteType;
            const answerItem = this.closest('.answer-item');
            const likesCount = answerItem.querySelector('.likes-count');
            const dislikesCount = answerItem.querySelector('.dislikes-count');
            const likeBtn = answerItem.querySelector('[data-vote-type="like"]');
            const dislikeBtn = answerItem.querySelector('[data-vote-type="dislike"]');

            fetch('vote_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `answer_id=${answerId}&vote_type=${voteType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update counts
                    likesCount.textContent = data.likes;
                    dislikesCount.textContent = data.dislikes;

                    // Update button states
                    if (data.user_vote === 'like') {
                        likeBtn.classList.add('active');
                        dislikeBtn.classList.remove('active');
                    } else if (data.user_vote === 'dislike') {
                        likeBtn.classList.remove('active');
                        dislikeBtn.classList.add('active');
                    } else {
                        likeBtn.classList.remove('active');
                        dislikeBtn.classList.remove('active');
                    }
                } else {
                    alert(data.error || 'An error occurred while processing your vote');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your vote');
            });
        });
    });
});
</script>

<style>
.card {
    border: 1px solid rgba(0,0,0,.125);
    margin-bottom: 1rem;
}

.card-body {
    padding: 1rem;
}

.card-title {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.card-text {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.answers-section {
    margin-top: 0.5rem !important;
}

.answers-section [role="button"] {
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0.25rem 0;
}

.answers-section [role="button"] h6 {
    font-size: 0.9rem;
    margin: 0;
}

.answer-item {
    padding: 0.5rem !important;
    margin-bottom: 0.5rem !important;
    border-left: 2px solid #007bff;
}

.answer-item p {
    font-size: 0.9rem;
    margin: 0.25rem 0 0 0;
}

.text-muted small {
    font-size: 0.8rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.collapse {
    margin-top: 0.5rem;
}

.vote-buttons {
    margin-top: 0.5rem;
    display: flex;
    gap: 0.5rem;
}

.vote-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.vote-btn.active {
    background-color: var(--bs-primary);
    color: white;
}

.vote-btn.active[data-vote-type="dislike"] {
    background-color: var(--bs-danger);
    color: white;
}

.vote-btn i {
    font-size: 0.9rem;
}
</style>