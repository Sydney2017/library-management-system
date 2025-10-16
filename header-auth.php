<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Library Management System";
if (basename($_SERVER['PHP_SELF']) == 'login.php') {
    $pageTitle = "Login - Library Management System";
} elseif (basename($_SERVER['PHP_SELF']) == 'register.php') {
    $pageTitle = "Register - Library Management System";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }
        
        .navbar {
            padding: 1rem 0;
            background: rgba(255,255,255,0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary) !important;
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--dark) !important;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: var(--secondary) !important;
        }
        
        .btn-nav {
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-left: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--secondary), #2980b9);
            border: none;
            border-radius: 25px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--secondary);
            color: var(--secondary);
            border-radius: 25px;
        }
        
        .btn-outline-primary:hover {
            background: var(--secondary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            border-radius: 25px;
        }
        
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2.5rem;
            margin: 2rem auto;
            border: none;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }
        
        .auth-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .auth-btn {
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Auth Navigation - Matches Homepage Style -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-book me-2"></i>LibrarySystem
            </a>
            <div class="d-flex">
                <?php if (basename($_SERVER['PHP_SELF']) == 'login.php'): ?>
                    <a href="register.php" class="btn btn-primary btn-nav">
                        <i class="bi bi-person-plus me-1"></i>Register
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary btn-nav">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary btn-nav">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary btn-nav">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>