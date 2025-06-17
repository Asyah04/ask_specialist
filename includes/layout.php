<?php
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask Specialist Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color:rgb(85, 80, 83); /* Hot Pink */
            --secondary-color:rgb(35, 32, 32); /* Light Pink */
            --accent-color:rgb(0, 0, 0); /* Deep Pink */
            --light-bg: #FFF0F5; /* Lavender Blush */
            --dark-bg:rgb(33, 15, 21); /* Pale Violet Red */
            --text-color: #4A4A4A; /* Dark Gray for text */
        }

        body {
            overflow-x: hidden;
            background-color: var(--light-bg);
            color: var(--text-color);
        }
        /* Hide scrollbar for Chrome, Safari and Opera */
        .sidebar-sticky::-webkit-scrollbar,
        .main-content::-webkit-scrollbar {
            display: none;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .sidebar-sticky,
        .main-content {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
            width: 240px;
            transition: all 0.3s;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .navbar {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color)) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            height: 60px;
            position: fixed;
            width: 100%;
            z-index: 1000;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
            background-color: #f8f9fa;
            overflow-y: auto;
            transition: all 0.3s;
        }
        .nav-link {
            color: #fff;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            transform: translateX(5px);
        }
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
        /* Common container styles */
        .question-container,
        .answer-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .question-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        .page-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary-color);
            color: var(--accent-color);
        }
        .container {
            max-width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }

        /* Enhanced Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            background-color: #fff;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: #fff;
            border-bottom: none;
            padding: 15px 20px;
            flex-shrink: 0; /* Prevent header from shrinking */
        }
        .card-body {
            padding: 20px;
            flex-grow: 1; /* Allow body to grow and fill space */
            display: flex;
            flex-direction: column;
        }
        .card-title {
            margin-bottom: 0;
            color: #333;
            font-weight: 600;
        }

        /* Statistics Cards */
        .stats-card {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            height: 100%; /* Make all stats cards full height */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        .stats-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }

        /* Table Styles */
        .table {
            margin-bottom: 0;
        }
        .table th {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
        }
        .table td {
            vertical-align: middle;
        }
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        .table-hover tbody tr:hover {
            background-color: var(--light-bg);
        }

        /* Button Animations */
        .btn {
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn:active {
            transform: translateY(0);
        }

        /* List Group Animations */
        .list-group-item {
            border: 1px solid rgba(255, 105, 180, 0.1);
            margin-bottom: 5px;
            border-radius: 8px !important;
        }
        .list-group-item:hover {
            background-color: var(--light-bg);
            transform: translateX(5px);
        }

        /* Modal Animations */
        .modal.fade .modal-dialog {
            transform: scale(0.8);
            transition: all 0.3s ease;
        }
        .modal.show .modal-dialog {
            transform: scale(1);
        }

        /* Alert Animations */
        .alert {
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Loading Animation */
        .loading {
            position: relative;
        }
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 0.8; }
            100% { opacity: 0.6; }
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 767.98px) {
            .sidebar {
                width: 0;
                padding: 0;
            }
            .sidebar.show {
                width: 240px;
                padding: 48px 0 0;
            }
            .main-content {
                margin-left: 0;
            }
            .navbar-brand {
                font-size: 1.1rem;
            }
            .navbar .ms-auto {
                margin-right: 10px;
            }
            .navbar .badge {
                display: none;
            }
            .question-container,
            .answer-container {
                margin: 10px;
                padding: 15px;
            }
            .card {
                margin: 10px;
            }
        }

        @media (max-width: 575.98px) {
            .container {
                padding-right: 10px;
                padding-left: 10px;
            }
            .navbar .me-3 {
                display: none;
            }
            .page-title {
                font-size: 1.5rem;
            }
            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.9rem;
            }
        }

        /* Toggle button for mobile */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            transition: all 0.3s ease;
        }
        .sidebar-toggle:hover {
            background: var(--accent-color);
            transform: scale(1.05);
        }

        @media (max-width: 767.98px) {
            .sidebar-toggle {
                display: block;
            }
        }

        /* Row with equal height cards */
        .row.equal-height {
            display: flex;
            flex-wrap: wrap;
        }
        .row.equal-height > [class*='col-'] {
            display: flex;
        }

        .navbar-brand, .navbar .text-light {
            color: #fff !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-success {
            background-color: var(--dark-bg);
            border-color: var(--dark-bg);
        }

        .btn-success:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }

        .badge.bg-success {
            background-color: var(--dark-bg) !important;
        }

        .badge.bg-info {
            background-color: var(--secondary-color) !important;
        }

        .list-group-item:hover {
            background-color: var(--light-bg);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
        }

        .modal-header .btn-close {
            color: white;
        }

        .alert-success {
            background-color: var(--light-bg);
            border-color: var(--dark-bg);
            color: var(--accent-color);
        }

        .alert-danger {
            background-color: #FFF0F5;
            border-color:rgb(50, 32, 40);
            color:rgb(0, 0, 0);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 105, 180, 0.25);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(255, 105, 180, 0.25);
        }

        .badge.bg-secondary {
            background-color: var(--secondary-color) !important;
        }

        a {
            color: var(--primary-color);
        }

        a:hover {
            color: var(--accent-color);
        }

        .btn-group .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-group .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Add some decorative elements */
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-color);
        }

        /* Update active navigation items styles */
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            border-right: 3px solid white;
            font-weight: 500;
        }

        .sidebar .nav-link.active i {
            color: white;
        }

        .sidebar .nav-link i {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <!-- Toggle Button for Mobile -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $_SESSION["role"] === "admin" ? "../admin/dashboard.php" : "dashboard.php"; ?>">Ask Specialist Portal</a>
            <div class="ms-auto">
                <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                <span class="badge bg-<?php 
                    echo $_SESSION["role"] === "admin" ? "danger" : 
                        ($_SESSION["role"] === "specialist" ? "success" : "primary"); 
                ?> me-3"><?php echo ucfirst(htmlspecialchars($_SESSION["role"])); ?></span>
                <a href="<?php 
                    if ($_SESSION["role"] === "admin") {
                        echo "../logout.php";
                    } elseif ($_SESSION["role"] === "specialist") {
                        echo "../logout.php";
                    } else {
                        echo "logout.php";
                    }
                ?>" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <?php 
                // Get current page URL
                $current_page = basename($_SERVER['PHP_SELF']);
                ?>
                <?php if($_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="../admin/users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'applications.php' ? 'active' : ''; ?>" href="../admin/applications.php">
                            <i class="fas fa-file-alt me-2"></i>Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'questions.php' ? 'active' : ''; ?>" href="../admin/questions.php">
                            <i class="fas fa-question-circle me-2"></i>Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="../admin/categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="../admin/reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                <?php elseif($_SESSION["role"] === "specialist"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="../specialist/dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'questions.php' ? 'active' : ''; ?>" href="../specialist/questions.php">
                            <i class="fas fa-question-circle me-2"></i>Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my-answers.php' ? 'active' : ''; ?>" href="../specialist/my-answers.php">
                            <i class="fas fa-reply me-2"></i>My Answers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="../specialist/profile.php">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'specialist_messages.php' ? 'active' : ''; ?>" href="../specialist_messages.php">
                            <i class="fas fa-comments me-2"></i>Messages
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="/dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'ask_question.php' ? 'active' : ''; ?>" href="/ask_question.php">
                            <i class="fas fa-question-circle me-2"></i>Ask Question
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my_questions.php' ? 'active' : ''; ?>" href="/my_questions.php">
                            <i class="fas fa-list me-2"></i>My Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'view_specialist.php' ? 'active' : ''; ?>" href="/view_specialist.php">
                            <i class="fas fa-user-tie me-2"></i>View Specialists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" href="/messages.php">
                            <i class="fas fa-comments me-2"></i>Messages
                        </a>
                    </li>
                    <?php if($_SESSION["role"] === "student"): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'apply_specialist.php' ? 'active' : ''; ?>" href="/apply_specialist.php">
                                <i class="fas fa-user-plus me-2"></i>Become a Specialist
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?php if(isset($page_title)): ?>
            <h2 class="page-title"><?php echo $page_title; ?></h2>
        <?php endif; ?>
        <?php if(isset($content)): ?>
            <?php echo $content; ?>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 767.98 && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        // Update online status
        function updateOnlineStatus() {
            fetch('update_online_status.php')
                .then(response => {
                    if (!response.ok) {
                        console.error('Failed to update online status');
                    }
                })
                .catch(error => {
                    console.error('Error updating online status:', error);
                });
        }

        // Update status immediately and then every 30 seconds
        updateOnlineStatus();
        setInterval(updateOnlineStatus, 30000);
    </script>
</body>
</html> 