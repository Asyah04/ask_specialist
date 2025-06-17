<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database configuration
require_once "config/database.php";

// Get all approved specialists with their categories
$sql = "SELECT u.id, u.username, u.email, c.name as category_name,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.id) as total_answers,
        (SELECT COUNT(*) FROM answer_votes av 
         JOIN answers a ON av.answer_id = a.id 
         WHERE a.user_id = u.id AND av.vote_type = 'like') as total_likes,
        (SELECT COUNT(*) FROM answer_votes av 
         JOIN answers a ON av.answer_id = a.id 
         WHERE a.user_id = u.id AND av.vote_type = 'dislike') as total_dislikes
        FROM users u 
        JOIN specialist_applications sa ON u.id = sa.user_id 
        JOIN categories c ON sa.category_id = c.id 
        WHERE sa.status = 'approved' 
        ORDER BY total_answers DESC";
$result = mysqli_query($conn, $sql);
$specialists = mysqli_fetch_all($result, MYSQLI_ASSOC);

$page_title = "View Specialists";
ob_start();
?>

    <title>View Specialists - Ask Specialist</title>

    <style>
        .specialist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }
       
        .profile-circle {
            width: 60px;
            height: 60px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .profile-card {
            max-width: 100%;
            margin: 0 auto;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: linear-gradient(to bottom right, #ffffff, #f8f9fa);
            height: 100%;
        }

        .profile-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #4a90e2, #67b26f);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.2);
            transition: all 0.3s ease;
        }

        .profile-circle:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.3);
        }

        .profile-info h4 {
            color: #4a90e2;
            font-weight: 600;
        }

        .profile-info p {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .profile-info p i {
            color: #67b26f;
            width: 16px;
        }

        .profile-info p .fa-envelope {
            color: #4a90e2;
        }

        hr {
            margin: 1rem 0;
            opacity: 0.1;
        }

        .stat-item {
            padding: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background-color: rgba(74, 144, 226, 0.05);
            transform: translateY(-2px);
        }

        .stat-icon {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }

        .answers-icon {
            color: #4a90e2;
        }

        .likes-icon {
            color: #67b26f;
        }

        .dislikes-icon {
            color: #e25c5c;
        }

        .col h5 {
            color: #4a90e2;
            font-weight: 600;
            margin-bottom: 2px;
            font-size: 1rem;
        }

        .col small {
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .profile-circle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .profile-info h4 {
                font-size: 1.2rem;
            }

            .stat-icon {
                font-size: 1rem;
            }

            .col h5 {
                font-size: 0.9rem;
            }
        }

        .btn-message {
            background: linear-gradient(45deg, #4a90e2, #67b26f);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(74, 144, 226, 0.2);
        }

        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3);
            color: white;
            background: linear-gradient(45deg, #67b26f, #4a90e2);
        }

        .btn-message i {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .btn-message {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }
    </style>

    <div class="container">
        <div class="row">
            <?php foreach($specialists as $specialist): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card profile-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="profile-info">
                                    <h4 class="mb-1"><?php echo htmlspecialchars($specialist['username']); ?></h4>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-envelope me-1"></i>
                                        <?php echo htmlspecialchars($specialist['email']); ?>
                                    </p>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-graduation-cap me-1"></i>
                                        <?php echo htmlspecialchars($specialist['category_name']); ?> Specialist
                                    </p>
                                </div>
                                <div class="profile-circle">
                                    <?php echo strtoupper(substr($specialist['username'], 0, 1)); ?>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col">
                                    <div class="stat-item">
                                        <i class="fas fa-reply stat-icon answers-icon"></i>
                                        <h5 class="mb-0"><?php echo $specialist['total_answers']; ?></h5>
                                        <small class="text-muted">Answers</small>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="stat-item">
                                        <i class="fas fa-thumbs-up stat-icon likes-icon"></i>
                                        <h5 class="mb-0"><?php echo $specialist['total_likes']; ?></h5>
                                        <small class="text-muted">Likes</small>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="stat-item">
                                        <i class="fas fa-thumbs-down stat-icon dislikes-icon"></i>
                                        <h5 class="mb-0"><?php echo $specialist['total_dislikes']; ?></h5>
                                        <small class="text-muted">Dislikes</small>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <a href="chat.php?receiver_id=<?php echo $specialist['id']; ?>" class="btn btn-message">
                                    <i class="fas fa-comments me-1"></i> Start Conversation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="comingSoonToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-info-circle me-2"></i>
                <strong class="me-auto">Coming Soon</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Private conversations will be available in a future update!
            </div>
        </div>
    </div>

    <script>
        function showComingSoonToast() {
            const toast = new bootstrap.Toast(document.getElementById('comingSoonToast'));
            toast.show();
        }
    </script>

    <?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 
