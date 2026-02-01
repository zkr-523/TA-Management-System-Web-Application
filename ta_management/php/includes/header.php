<?php
// If directly accessed, redirect to index
if (!defined('INCLUDED')) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'TA Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
    <script>
        // Function to change theme
        function setTheme(themeName) {
            localStorage.setItem('theme', themeName);
            document.documentElement.className = themeName;
        }

        // Function to change font size
        function setFontSize(size) {
            localStorage.setItem('fontSize', size);
            document.body.style.fontSize = size;
        }

        // Initialize theme and font size from local storage
        (function() {
            if (localStorage.getItem('theme')) {
                setTheme(localStorage.getItem('theme'));
            } else {
                setTheme('light-theme');
            }
            
            if (localStorage.getItem('fontSize')) {
                document.body.style.fontSize = localStorage.getItem('fontSize');
            }
        })();
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark custom-nav">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $basePath; ?>">
                <img src="<?php echo $basePath; ?>assets/images/alfaisal_logo.png" alt="Alfaisal University Logo" height="30">
                TA Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('manager')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $basePath; ?>manager/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $basePath; ?>manager/create_course.php">Create Course</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $basePath; ?>manager/assign_ta.php">Assign TA</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $basePath; ?>manager/reports.php">Reports</a>
                            </li>
                        <?php elseif (hasRole('instructor')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $basePath; ?>instructor/dashboard.php">Dashboard</a>
                            </li>
                        <?php elseif (hasRole('ta')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $basePath; ?>ta/dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i> Settings
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                            <li><h6 class="dropdown-header">Theme</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="setTheme('light-theme')">Light Theme</a></li>
                            <li><a class="dropdown-item" href="#" onclick="setTheme('dark-theme')">Dark Theme</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Font Size</h6></li>
                            <li><a class="dropdown-item" href="#" onclick="setFontSize('0.7rem')">Small</a></li>
                            <li><a class="dropdown-item" href="#" onclick="setFontSize('1rem')">Medium</a></li>
                            <li><a class="dropdown-item" href="#" onclick="setFontSize('1.3rem')">Large</a></li>
                        </ul>
                    </div>
                    
                    <div class="d-flex">
                        <span class="navbar-text me-3">
                            Welcome, <?php echo $_SESSION['fullname']; ?> (<?php echo ucfirst($_SESSION['role']); ?>)
                        </span>
                        <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light">Logout</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4 content-wrapper">