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

// Get online specialists (only those currently online)
$specialists_sql = "SELECT u.id, u.username, u.email, c.name as category_name,
        os.is_online, os.last_seen,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.id) as total_answers
        FROM users u 
        JOIN specialist_applications sa ON u.id = sa.user_id 
        JOIN categories c ON sa.category_id = c.id 
        JOIN online_status os ON u.id = os.user_id
        WHERE sa.status = 'approved' AND os.is_online = 1
        ORDER BY os.last_seen DESC";
$specialists_result = mysqli_query($conn, $specialists_sql);
$specialists = mysqli_fetch_all($specialists_result, MYSQLI_ASSOC);

// Get questions with answers
$sql = "SELECT q.*, c.name as category_name, u.username as asker_name,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count
        FROM questions q 
        JOIN categories c ON q.category_id = c.id 
        JOIN users u ON q.user_id = u.id 
        WHERE q.user_id = ?";

$params = [$_SESSION["id"]];
$types = "i";

// Add search filter
if (!empty($search)) {
    $sql .= " AND (q.title LIKE ? OR q.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Add category filter
if ($category_id > 0) {
    $sql .= " AND q.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

$sql .= " ORDER BY q.created_at DESC";

// Prepare and bind
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params); 
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
    } else {
        echo "Query execution failed.";
    }
} else {
    echo "Failed to prepare statement.";
}

$page_title = "Asker Dashboard";
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
        <div class="col-md-4">
            <div class="online-specialists-compact">
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="specialistsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-2"></i>Online Specialists (<?php echo count($specialists); ?>)
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end specialists-dropdown" aria-labelledby="specialistsDropdown">
                        <?php if(!empty($specialists)): ?>
                            <?php foreach($specialists as $specialist): ?>
                                <li>
                                    <a class="dropdown-item specialist-item" href="chat.php?receiver_id=<?php echo $specialist['id']; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="specialist-avatar-small">
                                                <?php echo strtoupper(substr($specialist['username'], 0, 1)); ?>
                                            </div>
                                            <div class="specialist-info-compact flex-grow-1">
                                                <div class="specialist-name"><?php echo htmlspecialchars($specialist['username']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($specialist['category_name']); ?></small>
                                            </div>
                                            <div class="online-indicator">
                                                <i class="fas fa-circle text-success"></i>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><span class="dropdown-item-text text-muted">No specialists online</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
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
                                        $answers_sql = "SELECT a.*, u.username as answerer_name, u.role as answerer_role
                                                        FROM answers a 
                                                        JOIN users u ON a.user_id = u.id 
                                                        WHERE a.question_id = ? 
                                                        ORDER BY a.created_at ASC";
                                        if($answers_stmt = mysqli_prepare($conn, $answers_sql)){
                                            mysqli_stmt_bind_param($answers_stmt, "i", $row['id']);
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


});
</script>

<!-- Modern styles applied from layout.php -->
<style>
/* Specific styling for answers and voting system */
.answers-section {
    margin-top: 0.75rem !important;
}

.answers-section [role="button"] {
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.5rem 0;
    border-radius: 8px;
}

.answers-section [role="button"]:hover {
    background-color: var(--neutral-50);
}

.answers-section [role="button"] h6 {
    font-size: 0.95rem;
    margin: 0;
    font-weight: 600;
    color: var(--primary-color);
}

.answer-item {
    padding: 1rem !important;
    margin-bottom: 0.75rem !important;
    border-left: 3px solid var(--primary-color);
    background: var(--neutral-50);
    border-radius: 0 12px 12px 0;
    transition: all 0.3s ease;
}

.answer-item:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-md);
}

.answer-item p {
    font-size: 0.95rem;
    margin: 0.5rem 0;
    line-height: 1.6;
    color: var(--neutral-900);
}

.vote-buttons {
    margin-top: 0.75rem;
    display: flex;
    gap: 0.75rem;
}

.vote-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 20px;
    border: 2px solid var(--neutral-200);
    background: white;
    color: var(--neutral-700);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
}

.vote-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.vote-btn.active {
    background: var(--gradient-primary);
    border-color: var(--primary-color);
    color: white;
    box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.3);
}

.vote-btn.active[data-vote-type="dislike"] {
    background: var(--gradient-primary);
    border-color: var(--accent-danger);
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
}

.vote-btn i {
    font-size: 1rem;
}

/* Modern Specialist Cards */
.specialist-card {
    background: #fff;
    border: 1px solid var(--neutral-200);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    box-shadow: var(--shadow-sm);
}

.specialist-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
    border-color: var(--primary-color);
}

.specialist-avatar {
    width: 50px;
    height: 50px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    margin-right: 1rem;
    flex-shrink: 0;
    box-shadow: var(--shadow-md);
}

.specialist-info h6 {
    font-weight: 600;
    color: var(--neutral-900);
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.specialist-info small {
    color: var(--secondary-color);
    font-size: 0.875rem;
}

.online-status-indicator {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

.online-status-indicator.online {
    color: var(--accent-success);
    background: rgba(16, 185, 129, 0.1);
}

.online-status-indicator.offline {
    color: var(--secondary-color);
    background: var(--neutral-100);
}

.specialist-actions .btn {
    border-radius: 12px;
    width: auto;
    height: auto;
    padding: 0.5rem 1rem;
}

.specialist-stats {
    border-top: 1px solid var(--neutral-100);
    padding-top: 1rem;
    margin-top: 1rem;
}

.specialist-stats small {
    color: var(--secondary-color);
    font-size: 0.8rem;
}

/* Modern Dropdown Styles */
.online-specialists-compact {
    display: flex;
    justify-content: flex-end;
}

.specialists-dropdown {
    min-width: 320px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid var(--neutral-200);
    border-radius: 16px;
    box-shadow: var(--shadow-xl);
    background: white;
}

.specialist-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--neutral-100);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.specialist-item:hover {
    background-color: var(--neutral-50);
    text-decoration: none;
    transform: translateX(4px);
}

.specialist-item:last-child {
    border-bottom: none;
}

.specialist-avatar-small {
    width: 40px;
    height: 40px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1rem;
    margin-right: 1rem;
    flex-shrink: 0;
    box-shadow: var(--shadow-sm);
}

.specialist-info-compact {
    margin-right: 0.75rem;
}

.specialist-name {
    font-weight: 600;
    color: var(--neutral-900);
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.specialist-info-compact small {
    color: var(--secondary-color);
    font-size: 0.8rem;
}

.online-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.online-indicator i {
    font-size: 0.75rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.6; }
    100% { opacity: 1; }
}

.dropdown-toggle {
    border-radius: 12px;
    font-weight: 500;
    padding: 0.75rem 1.25rem;
    border: 2px solid var(--neutral-200);
    background: white;
    color: var(--primary-color);
}

.dropdown-toggle:hover {
    border-color: var(--primary-color);
    background: var(--neutral-50);
}

.dropdown-toggle:focus {
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
    border-color: var(--primary-color);
}
</style>