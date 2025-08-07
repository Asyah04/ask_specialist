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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Modern Color Palette - Professional Blue */
            --primary-color: #2563EB;
            --primary-light: #3B82F6;
            --primary-dark: #1D4ED8;
            
            /* Secondary Palette - Warm Gray */
            --secondary-color: #6B7280;
            --secondary-light: #9CA3AF;
            --secondary-dark: #374151;
            
            /* Accent Colors */
            --accent-success: #10B981;
            --accent-warning: #F59E0B;
            --accent-danger: #EF4444;
            --accent-info: #06B6D4;
            
            /* Neutral Colors */
            --neutral-50: #F9FAFB;
            --neutral-100: #F3F4F6;
            --neutral-200: #E5E7EB;
            --neutral-900: #111827;
            
            /* Background Colors */
            --light-bg: #F9FAFB;
            --dark-bg: #1F2937;
            --text-color: #111827;
            
            /* Modern Gradients */
            --gradient-primary: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            --gradient-secondary: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #059669 100%);
            --gradient-info: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%);
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            --gradient-danger: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            --gradient-accent: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
        }

        body {
            overflow-x: hidden;
            background-color: var(--light-bg);
            color: var(--text-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            font-size: 16px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            line-height: 1.4;
            color: var(--neutral-900);
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
            background: linear-gradient(180deg, #1F2937 0%, #111827 100%);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            width: 240px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-xl);
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
            background: var(--gradient-primary) !important;
            box-shadow: 0 2px 8px rgba(77, 182, 172, 0.2);
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
        .nav-item {
            margin: 4px 12px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .nav-link {
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
            text-decoration: none;
        }
        
        .nav-link.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.4);
            transform: translateX(2px);
        }
        
        .nav-link i {
            font-size: 16px;
            width: 20px;
            text-align: center;
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
        /* Modern Page Title */
        .page-title {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--primary-color);
            color: var(--neutral-900);
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.25rem;
            position: relative;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        .container {
            max-width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }

        /* Modern Card Styles - Compact but with Better Height (Increased height as requested) */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--neutral-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease, color 0.3s ease;
            margin-bottom: 16px;
            overflow: hidden;
            min-height: 280px;
            height: auto;
            display: flex;
            flex-direction: column;
            background-color: #fff;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        .card-header {
            background: var(--gradient-primary);
            color: #fff;
            border-bottom: none;
            padding: 18px 20px;
            flex-shrink: 0;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            font-size: 1rem;
        }
        .card-body {
            padding: 22px 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 180px;
        }
        
        /* Card responsiveness for better height management */
        @media (max-width: 768px) {
            .card {
                min-height: 240px;
            }
            .card-body {
                min-height: 140px;
                padding: 18px 16px;
            }
            .list-group-item {
                min-height: 55px;
                padding: 14px 16px;
            }
        }
        .card-title {
            margin-bottom: 0;
            color: #333;
            font-weight: 600;
        }

        /* Modern Statistics Cards - Compact & Short */
        .stats-card {
            background: var(--gradient-primary);
            border-radius: 12px;
            padding: 16px;
            color: white;
            position: relative;
            overflow: hidden;
            border: none;
            min-height: 85px;
            margin-bottom: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(20px, -20px);
        }
        
        .stats-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.25);
        }
        
        .stats-icon {
            font-size: 1.6rem;
            opacity: 0.9;
            margin-bottom: 6px;
            position: relative;
            z-index: 2;
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 4px 0;
            font-family: 'Poppins', sans-serif;
            position: relative;
            z-index: 2;
            line-height: 1.2;
        }
        
        .stats-label {
            font-size: 0.75rem;
            opacity: 0.9;
            font-weight: 500;
            position: relative;
            z-index: 2;
            line-height: 1.3;
        }

        /* Modern Table Styles - Optimized for Screen Fit */
        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            background: white;
            margin-bottom: 0;
            font-size: 0.85rem;
            table-layout: fixed;
            width: 100%;
        }
        .table th {
            background: var(--gradient-primary);
            color: white;
            border: none;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 12px 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: none;
            letter-spacing: normal;
        }
        .table td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid var(--neutral-100);
            font-size: 0.8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        .table-hover tbody tr:hover {
            background-color: var(--neutral-50);
            transform: scale(1.005);
        }
        
        /* Table Column Widths for Better Fit */
        .table th:nth-child(1), .table td:nth-child(1) { width: 15%; } /* Username */
        .table th:nth-child(2), .table td:nth-child(2) { width: 25%; } /* Email */
        .table th:nth-child(3), .table td:nth-child(3) { width: 10%; } /* Role */
        .table th:nth-child(4), .table td:nth-child(4) { width: 8%; }  /* Questions */
        .table th:nth-child(5), .table td:nth-child(5) { width: 8%; }  /* Answers */
        .table th:nth-child(6), .table td:nth-child(6) { width: 15%; } /* Joined */
        .table th:nth-child(7), .table td:nth-child(7) { width: 19%; } /* Actions */
        
        /* Email truncation for better fit */
        .table .email-cell {
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Description truncation for categories table */
        .table .description-cell {
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Categories table specific column widths */
        .table.categories-table th:nth-child(1), .table.categories-table td:nth-child(1) { width: 20%; } /* Name */
        .table.categories-table th:nth-child(2), .table.categories-table td:nth-child(2) { width: 50%; } /* Description */
        .table.categories-table th:nth-child(3), .table.categories-table td:nth-child(3) { width: 10%; } /* Questions */
        .table.categories-table th:nth-child(4), .table.categories-table td:nth-child(4) { width: 20%; } /* Actions */
        
        /* Reports category table - Full width layout */
        .table.reports-category-table th:nth-child(1), .table.reports-category-table td:nth-child(1) { width: 30%; } /* Category */
        .table.reports-category-table th:nth-child(2), .table.reports-category-table td:nth-child(2) { width: 15%; } /* Questions */
        .table.reports-category-table th:nth-child(3), .table.reports-category-table td:nth-child(3) { width: 15%; } /* Percentage */
        .table.reports-category-table th:nth-child(4), .table.reports-category-table td:nth-child(4) { width: 40%; } /* Progress */
        
        /* Reports activity table - Scrollable content */
        .table.reports-activity-table {
            min-width: 800px; /* Force horizontal scroll if needed */
        }
        .table.reports-activity-table th:nth-child(1), .table.reports-activity-table td:nth-child(1) { width: 12%; } /* Type */
        .table.reports-activity-table th:nth-child(2), .table.reports-activity-table td:nth-child(2) { width: 35%; } /* Content */
        .table.reports-activity-table th:nth-child(3), .table.reports-activity-table td:nth-child(3) { width: 15%; } /* User */
        .table.reports-activity-table th:nth-child(4), .table.reports-activity-table td:nth-child(4) { width: 18%; } /* Date & Time */
        .table.reports-activity-table th:nth-child(5), .table.reports-activity-table td:nth-child(5) { width: 10%; } /* Category */
        .table.reports-activity-table th:nth-child(6), .table.reports-activity-table td:nth-child(6) { width: 10%; } /* Status */
        
        /* Content cell for activity table */
        .table .content-cell {
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Progress bar styling */
        .progress {
            background-color: var(--neutral-200);
            border-radius: 10px;
        }
        
        .progress-bar {
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Admin Applications table - Full width layout */
        .table.admin-applications-table th:nth-child(1), .table.admin-applications-table td:nth-child(1) { width: 20%; } /* Applicant */
        .table.admin-applications-table th:nth-child(2), .table.admin-applications-table td:nth-child(2) { width: 25%; } /* Email */
        .table.admin-applications-table th:nth-child(3), .table.admin-applications-table td:nth-child(3) { width: 15%; } /* Category */
        .table.admin-applications-table th:nth-child(4), .table.admin-applications-table td:nth-child(4) { width: 15%; } /* Applied Date */
        .table.admin-applications-table th:nth-child(5), .table.admin-applications-table td:nth-child(5) { width: 10%; } /* Status */
        .table.admin-applications-table th:nth-child(6), .table.admin-applications-table td:nth-child(6) { width: 15%; } /* Actions */
        
        /* Admin Questions table - Full width layout */
        .table.admin-questions-table th:nth-child(1), .table.admin-questions-table td:nth-child(1) { width: 25%; } /* Question Title */
        .table.admin-questions-table th:nth-child(2), .table.admin-questions-table td:nth-child(2) { width: 15%; } /* Asked By */
        .table.admin-questions-table th:nth-child(3), .table.admin-questions-table td:nth-child(3) { width: 18%; } /* Category - Increased for full text */
        .table.admin-questions-table th:nth-child(4), .table.admin-questions-table td:nth-child(4) { width: 8%; }  /* Answers */
        .table.admin-questions-table th:nth-child(5), .table.admin-questions-table td:nth-child(5) { width: 12%; } /* Asked Date */
        .table.admin-questions-table th:nth-child(6), .table.admin-questions-table td:nth-child(6) { width: 10%; } /* Status */
        .table.admin-questions-table th:nth-child(7), .table.admin-questions-table td:nth-child(7) { width: 12%; } /* Actions */
        
        /* Question title cell truncation */
        .table .question-title-cell {
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Specialist Answers table - Full width layout */
        .table.specialist-answers-table th:nth-child(1), .table.specialist-answers-table td:nth-child(1) { width: 25%; } /* Question Title */
        .table.specialist-answers-table th:nth-child(2), .table.specialist-answers-table td:nth-child(2) { width: 35%; } /* Answer Preview */
        .table.specialist-answers-table th:nth-child(3), .table.specialist-answers-table td:nth-child(3) { width: 15%; } /* Date Answered */
        .table.specialist-answers-table th:nth-child(4), .table.specialist-answers-table td:nth-child(4) { width: 12%; } /* Status */
        .table.specialist-answers-table th:nth-child(5), .table.specialist-answers-table td:nth-child(5) { width: 13%; } /* Actions */
        
        /* Specialist Questions table - Full width layout */
        .table.specialist-questions-table th:nth-child(1), .table.specialist-questions-table td:nth-child(1) { width: 25%; } /* Question Title */
        .table.specialist-questions-table th:nth-child(2), .table.specialist-questions-table td:nth-child(2) { width: 15%; } /* Asked By */
        .table.specialist-questions-table th:nth-child(3), .table.specialist-questions-table td:nth-child(3) { width: 30%; } /* Content Preview */
        .table.specialist-questions-table th:nth-child(4), .table.specialist-questions-table td:nth-child(4) { width: 8%; }  /* Answers */
        .table.specialist-questions-table th:nth-child(5), .table.specialist-questions-table td:nth-child(5) { width: 12%; } /* Asked Date */
        .table.specialist-questions-table th:nth-child(6), .table.specialist-questions-table td:nth-child(6) { width: 10%; } /* Actions */
        
        /* Answer preview and content preview cell truncation */
        .table .answer-preview-cell, 
        .table .content-preview-cell {
            max-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Compact badge styles */
        .table .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
        }
        
        /* Compact button group */
        .table .btn-group .btn {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        
        /* Responsive table improvements */
        .table-responsive {
            border-radius: 12px;
            border: 1px solid var(--neutral-200);
        }
        
        @media (max-width: 768px) {
            .table {
                font-size: 0.75rem;
            }
            .table th, .table td {
                padding: 8px 6px;
            }
            .table th:nth-child(2), .table td:nth-child(2) { width: 20%; } /* Email smaller on mobile */
            .table th:nth-child(6), .table td:nth-child(6) { width: 12%; } /* Date smaller on mobile */
            .table th:nth-child(7), .table td:nth-child(7) { width: 22%; } /* Actions wider on mobile */
        }
        
        /* Additional table responsiveness */
        @media (max-width: 576px) {
            .table {
                font-size: 0.7rem;
            }
            .table th, .table td {
                padding: 6px 4px;
            }
            .table .btn-group .btn {
                padding: 2px 6px;
                font-size: 0.7rem;
            }
            .table .badge {
                font-size: 0.6rem;
                padding: 2px 6px;
            }
        }
        
        /* Container responsiveness for tables */
        .container {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        @media (min-width: 576px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        /* Modern Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(37, 99, 235, 0.4);
            color: white;
        }
        
        .btn-outline-primary {
            color: var(--primary-color) !important;
            border: 2px solid var(--primary-color) !important;
            background: transparent !important;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active,
        .btn-outline-primary.show {
            background: var(--gradient-primary) !important;
            border-color: var(--primary-color) !important;
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: var(--gradient-success);
            color: white;
            box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(16, 185, 129, 0.4);
            color: white;
        }
        
        /* Modern Form Styles */
        .form-control {
            padding: 14px 16px;
            border: 2px solid var(--neutral-200);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #FFFFFF;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--neutral-900);
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }
        
        .form-select {
            padding: 14px 16px;
            border: 2px solid var(--neutral-200);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #FFFFFF;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        /* Modern Badge Styles */
        .badge {
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.bg-primary {
            background: var(--gradient-primary) !important;
            color: white;
        }
        
        .badge.bg-success {
            background: var(--gradient-success) !important;
            color: white;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%) !important;
            color: white;
        }
        
        .badge.bg-danger {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%) !important;
            color: white;
        }
        /* Modern Background Colors */
        .bg-primary {
            background: var(--gradient-primary) !important;
        }
        
        .bg-success {
            background: var(--gradient-success) !important;
        }
        
        .bg-info {
            background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%) !important;
        }
        
        .bg-warning {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%) !important;
        }
        
        .bg-danger {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%) !important;
        }
        .table-hover tbody tr:hover {
            background-color: var(--light-bg);
        }

        /* Enhanced Button Animations */
        .btn:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }
        
        /* Ripple Effect for Buttons */
        .btn {
            position: relative;
            overflow: hidden;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }
        
        .btn:active::after {
            width: 300px;
            height: 300px;
        }

        /* Modern List Group - Better Height */
        .list-group-item {
            border: 1px solid var(--neutral-200);
            margin-bottom: 8px;
            border-radius: 8px !important;
            padding: 16px 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            font-size: 0.95rem;
            min-height: 65px;
            display: flex;
            align-items: center;
        }
        
        .list-group-item:hover {
            background-color: var(--neutral-50);
            transform: translateX(6px);
            box-shadow: var(--shadow-sm);
            border-color: var(--primary-color);
        }
        
        .list-group-item:first-child {
            border-top-left-radius: 8px !important;
            border-top-right-radius: 8px !important;
        }
        
        .list-group-item:last-child {
            border-bottom-left-radius: 8px !important;
            border-bottom-right-radius: 8px !important;
        }

        /* Modern Modal Styles */
        .modal.fade .modal-dialog {
            transform: scale(0.8) translateY(-30px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modal.show .modal-dialog {
            transform: scale(1) translateY(0);
        }
        
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            border-bottom: 1px solid var(--neutral-200);
            padding: 24px;
            border-radius: 16px 16px 0 0;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            border-top: 1px solid var(--neutral-200);
            padding: 24px;
            border-radius: 0 0 16px 16px;
        }

        /* Modern Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-up {
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Alert Animations */
        .alert {
            animation: slideIn 0.5s ease;
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            color: var(--accent-success);
            border-left: 4px solid var(--accent-success);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            color: var(--accent-danger);
            border-left: 4px solid var(--accent-danger);
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1) 0%, rgba(8, 145, 178, 0.1) 100%);
            color: var(--accent-info);
            border-left: 4px solid var(--accent-info);
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

        @media (max-width: 400px) {
            .navbar-brand {
                margin-left: 48px;
            }
            .navbar {
                border-radius: 0 0 12px 0;
                box-shadow: 2px 2px 8px white;
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
            align-items: center;
            justify-content: center;
            margin: 0px 0px;
        }
        /* .row.equal-height > [class*='col-'] {
            display: flex;
        } */

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
        
        /* Modern Responsive Design */
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
            }
            
            .sidebar.show {
                transform: translateX(0);
                box-shadow: var(--shadow-xl);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .stats-card {
                min-height: 120px;
                padding: 20px;
            }
            
            .stats-number {
                font-size: 2.2rem;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }
            
            .page-title {
                font-size: 1.75rem;
                margin-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 12px;
            }
            
            .card-body {
                padding: 16px;
            }
            
            .stats-card {
                padding: 16px;
                min-height: 100px;
            }
            
            .stats-number {
                font-size: 1.8rem;
            }
        }
        
        /* Loading States */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Utility Classes */
        .shadow-sm { box-shadow: var(--shadow-sm); }
        .shadow-md { box-shadow: var(--shadow-md); }
        .shadow-lg { box-shadow: var(--shadow-lg); }
        .shadow-xl { box-shadow: var(--shadow-xl); }
        
        .rounded-lg { border-radius: 12px; }
        .rounded-xl { border-radius: 16px; }
        .rounded-2xl { border-radius: 20px; }
        
        .text-gradient {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Interactive Elements */
        .interactive-element {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .interactive-element:hover {
            transform: scale(1.02);
        }
        
        /* Focus States */
        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }
        
        /* Dark Mode Foundation */
        [data-theme="dark"] {
            --primary-color: #3B82F6;
            --primary-light: #60A5FA;
            --primary-dark: #2563EB;
            --background-color: #111827;
            --card-background: #1F2937;
            --text-color: #F9FAFB;
            --text-muted: #9CA3AF;
            --neutral-50: #374151;
            --neutral-100: #374151;
            --neutral-200: #4B5563;
            --neutral-900: #F9FAFB;
            --light-bg: #1F2937;
            --dark-bg: #111827;
            --sidebar-bg: #0F172A;
            --navbar-bg: #1F2937;
            --border-color: #374151;
            --input-bg: #374151;
            --input-border: #4B5563;
            --table-bg: #1F2937;
            --table-hover: #374151;
        }
        
        /* Dark Mode Body */
        [data-theme="dark"] body {
            background-color: var(--background-color) !important;
            color: var(--text-color) !important;
        }
        
        /* Dark Mode Sidebar */
        [data-theme="dark"] .sidebar {
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--background-color) 100%) !important;
        }
        
        /* Dark Mode Navigation */
        [data-theme="dark"] .navbar {
            background-color: var(--navbar-bg) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }
        
        [data-theme="dark"] .nav-link {
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .nav-link:hover {
            background-color: var(--neutral-100) !important;
            color: var(--primary-light) !important;
        }
        
        [data-theme="dark"] .nav-link.active {
            background-color: var(--primary-color) !important;
            color: white !important;
        }
        
        /* Dark Mode Cards */
        [data-theme="dark"] .card {
            background-color: var(--card-background) !important;
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .card-header {
            background: var(--gradient-primary) !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: white !important;
        }
        
        [data-theme="dark"] .card-body {
            background-color: var(--card-background) !important;
            color: var(--text-color) !important;
        }
        
        /* Dark Mode Main Content */
        [data-theme="dark"] .main-content {
            background-color: var(--background-color) !important;
        }
        
        /* Dark Mode Tables */
        [data-theme="dark"] .table {
            background-color: var(--table-bg) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .table th {
            background-color: var(--neutral-100) !important;
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .table td {
            background-color: var(--table-bg) !important;
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .table-hover tbody tr:hover {
            background-color: var(--table-hover) !important;
        }
        
        /* Dark Mode Forms */
        [data-theme="dark"] .form-control {
            background-color: var(--input-bg) !important;
            border-color: var(--input-border) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .form-control:focus {
            background-color: var(--input-bg) !important;
            border-color: var(--primary-color) !important;
            color: var(--text-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
        }
        
        [data-theme="dark"] .form-label {
            color: var(--text-color) !important;
        }
        
        /* Dark Mode Text */
        [data-theme="dark"] h1, 
        [data-theme="dark"] h2, 
        [data-theme="dark"] h3, 
        [data-theme="dark"] h4, 
        [data-theme="dark"] h5, 
        [data-theme="dark"] h6 {
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .text-muted {
            color: var(--text-muted) !important;
        }
        
        [data-theme="dark"] .text-primary {
            color: var(--primary-light) !important;
        }
        
        /* Dark Mode Badges */
        [data-theme="dark"] .badge {
            border: 1px solid var(--border-color);
        }
        
        /* Dark Mode Alerts */
        [data-theme="dark"] .alert {
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .alert-success {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #34D399 !important;
            border-color: #10B981 !important;
        }
        
        [data-theme="dark"] .alert-danger {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: #F87171 !important;
            border-color: #EF4444 !important;
        }
        
        [data-theme="dark"] .alert-warning {
            background-color: rgba(245, 158, 11, 0.1) !important;
            color: #FBBF24 !important;
            border-color: #F59E0B !important;
        }
        
        [data-theme="dark"] .alert-info {
            background-color: rgba(6, 182, 212, 0.1) !important;
            color: #22D3EE !important;
            border-color: #06B6D4 !important;
        }
        
        /* Dark Mode Buttons */
        [data-theme="dark"] .btn-outline-primary {
            color: var(--primary-light) !important;
            border-color: var(--primary-light) !important;
        }
        
        [data-theme="dark"] .btn-outline-primary:hover {
            background-color: var(--primary-light) !important;
            border-color: var(--primary-light) !important;
        }
        
        /* Dark Mode List Groups */
        [data-theme="dark"] .list-group-item {
            background-color: var(--card-background) !important;
            border-color: var(--border-color) !important;
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .list-group-item:hover {
            background-color: var(--table-hover) !important;
        }
        
        /* Dark Mode Modals */
        [data-theme="dark"] .modal-content {
            background-color: var(--card-background) !important;
            color: var(--text-color) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .modal-header {
            border-bottom-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .modal-footer {
            border-top-color: var(--border-color) !important;
        }
        
        /* Dropdown Fixes */
        .dropdown-menu {
            background-color: var(--card-background, white) !important;
            border: 1px solid var(--border-color, #dee2e6) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }

        .dropdown-item {
            color: var(--text-color, #333) !important;
            background-color: transparent !important;
            padding: 8px 16px !important;
            transition: all 0.2s ease !important;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: white !important;
            background-color: var(--primary-color) !important;
        }

        .dropdown-item:active {
            color: white !important;
            background-color: var(--primary-color) !important;
        }

        /* Specialist dropdown items */
        .specialist-item {
            color: var(--text-color, #333) !important;
            background-color: transparent !important;
            text-decoration: none !important;
        }

        .specialist-item:hover {
            color: white !important;
            background-color: var(--primary-color) !important;
            text-decoration: none !important;
        }
        
        /* Dark Mode Dropdown */
        [data-theme="dark"] .dropdown-menu {
            background-color: var(--card-background) !important;
            border-color: var(--border-color) !important;
        }
        
        [data-theme="dark"] .dropdown-item {
            color: var(--text-color) !important;
        }
        
        [data-theme="dark"] .dropdown-item:hover {
            background-color: var(--table-hover) !important;
            color: var(--text-color) !important;
        }
        
        /* Theme Toggle Button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: var(--shadow-xl);
            background: var(--primary-light);
        }
        
        .theme-toggle:active {
            transform: scale(0.95);
        }
        
        /* Dark mode theme toggle styling */
        [data-theme="dark"] .theme-toggle {
            background: var(--primary-color);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        [data-theme="dark"] .theme-toggle:hover {
            background: var(--primary-light);
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.4);
        }
        
        /* Dark Mode Stats Cards */
        [data-theme="dark"] .stats-card {
            background: var(--gradient-primary) !important;
            color: white !important;
            border: 1px solid var(--border-color) !important;
        }
        
        [data-theme="dark"] .stats-icon {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        [data-theme="dark"] .stats-number {
            color: white !important;
        }
        
        [data-theme="dark"] .stats-label {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        
        .theme-toggle i {
            font-size: 18px;
            transition: all 0.3s ease;
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
                ?>" class="btn btn-outline-danger btn-sm" style="box-shadow: 1px 1px 8px black; padding: 10px 20px; color: white;" >Logout</a>
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
                
                // Detect current directory context for correct navigation
                $is_admin_dir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
                $is_specialist_dir = strpos($_SERVER['PHP_SELF'], '/specialist/') !== false;
                $admin_prefix = $is_admin_dir ? '' : 'admin/';
                $specialist_prefix = $is_specialist_dir ? '' : 'specialist/';
                
                // Debug: Navigation context fixed - <?php echo date('H:i:s'); ?>
                
                <?php if($_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $admin_prefix; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="<?php echo $admin_prefix; ?>users.php">
                            <i class="fas fa-users"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'applications.php' ? 'active' : ''; ?>" href="<?php echo $admin_prefix; ?>applications.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Applications</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'questions.php' ? 'active' : ''; ?>" href="<?php echo $admin_prefix; ?>questions.php">
                            <i class="fas fa-question-circle"></i>
                            <span>Questions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="<?php echo $admin_prefix; ?>categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="<?php echo $admin_prefix; ?>reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                <?php elseif($_SESSION["role"] === "specialist"): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === '/specialist/dashboard.php' ? 'active' : ''; ?>" href="<?php echo $specialist_prefix; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'questions.php' ? 'active' : ''; ?>" href="<?php echo $is_specialist_dir ? 'questions.php' : 'questions.php'; ?>">
                            <i class="fas fa-question-circle"></i>
                            <span>Questions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my-answers.php' ? 'active' : ''; ?>" href="<?php echo $specialist_prefix; ?>my-answers.php">
                            <i class="fas fa-reply"></i>
                            <span>My Answers</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="<?php echo $specialist_prefix; ?>profile.php">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" href="<?php echo $is_specialist_dir ? '../messages.php' : 'messages.php'; ?>">
                            <i class="fas fa-comments"></i>
                            <span>Messages</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'ask_question.php' ? 'active' : ''; ?>" href="ask_question.php">
                            <i class="fas fa-question-circle"></i>
                            <span>Ask Question</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my_questions.php' ? 'active' : ''; ?>" href="my_questions.php">
                            <i class="fas fa-list"></i>
                            <span>My Questions</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'apply_specialist.php' ? 'active' : ''; ?>" href="apply_specialist.php">
                            <i class="fas fa-user-tie"></i>
                            <span>Apply as Specialist</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'view_specialist.php' ? 'active' : ''; ?>" href="view_specialist.php">
                            <i class="fas fa-user-tie"></i>
                            <span>View Specialists</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                            <i class="fas fa-comments"></i>
                            <span>Messages</span>
                        </a>
                    </li>
                   
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

    <!-- Theme Toggle Button -->
    <button class="theme-toggle" id="themeToggle" title="Toggle Dark Mode">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>

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
            // Determine the correct path based on current directory
            const currentPath = window.location.pathname;
            let statusPath = 'update_online_status.php';
            
            // If we're in admin or specialist directory, go up one level
            if (currentPath.includes('/admin/') || currentPath.includes('/specialist/')) {
                statusPath = '../update_online_status.php';
            }
            
            fetch(statusPath)
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
        
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        // Set initial theme
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Add click animation
            themeToggle.style.transform = 'scale(0.9)';
            setTimeout(() => {
                themeToggle.style.transform = '';
            }, 100);
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            // Add rotation animation
            themeIcon.style.transform = 'rotate(180deg)';
            
            setTimeout(() => {
                if (theme === 'dark') {
                    themeIcon.className = 'fas fa-sun';
                    themeToggle.setAttribute('title', 'Switch to Light Mode');
                } else {
                    themeIcon.className = 'fas fa-moon';
                    themeToggle.setAttribute('title', 'Switch to Dark Mode');
                }
                themeIcon.style.transform = 'rotate(0deg)';
            }, 150);
        }
        
        // Add smooth animations to all cards on page load
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.card, .stats-card');
            cards.forEach((card, index) => {
                card.classList.add('fade-in');
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html> 