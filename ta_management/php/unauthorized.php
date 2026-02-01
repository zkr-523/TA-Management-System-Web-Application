<?php
define('INCLUDED', true);
require_once 'config.php';

$pageTitle = "Unauthorized Access";
$basePath = "./";

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2 text-center">
            <div class="alert alert-danger">
                <h1><i class="fas fa-exclamation-triangle"></i> Unauthorized Access</h1>
                <p class="lead">You do not have permission to access this page.</p>
                <hr>
                <p>You are currently logged in as: <strong><?php echo $_SESSION['fullname']; ?></strong> (<?php echo ucfirst($_SESSION['role']); ?>)</p>
                <p>If you believe this is an error, please contact the system administrator.</p>
                <div class="mt-4">
                    <?php if (hasRole('manager')): ?>
                        <a href="manager/dashboard.php" class="btn btn-primary">Go to Manager Dashboard</a>
                    <?php elseif (hasRole('instructor')): ?>
                        <a href="instructor/dashboard.php" class="btn btn-primary">Go to Instructor Dashboard</a>
                    <?php elseif (hasRole('ta')): ?>
                        <a href="ta/dashboard.php" class="btn btn-primary">Go to TA Dashboard</a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-primary">Go to Login Page</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-secondary">Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
