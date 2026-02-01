<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Manage Users";
$basePath = "../";

// Determine which role to filter by
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
if (!in_array($role_filter, ['all', 'instructor', 'ta'])) {
    $role_filter = 'all';
}

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = '';

// Process user deletion if requested
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $user_id = (int)$_GET['delete'];
    
    // Check if user exists and is not the current user
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "User not found.";
        } else {
            // Check if user is associated with any courses or assignments
            $user_role = $result->fetch_assoc()['role'];
            $can_delete = true;
            
            if ($user_role == 'instructor') {
                // Check if instructor has courses
                $stmt = $conn->prepare("SELECT id FROM courses WHERE instructor_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Cannot delete user because they are assigned as instructor to one or more courses.";
                    $can_delete = false;
                }
            } elseif ($user_role == 'ta') {
                // Check if TA has assignments
                $stmt = $conn->prepare("SELECT id FROM ta_assignments WHERE ta_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Cannot delete user because they are assigned as TA to one or more courses.";
                    $can_delete = false;
                }
            }
            
            if ($can_delete) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $success = "User deleted successfully.";
                } else {
                    $error = "Error deleting user: " . $conn->error;
                }
            }
        }
    }
}

// Get all users based on filter
$users = [];
if ($role_filter == 'all') {
    $result = $conn->query("SELECT id, username, fullname, email, role, created_at FROM users ORDER BY role, fullname");
} else {
    $stmt = $conn->prepare("SELECT id, username, fullname, email, role, created_at FROM users WHERE role = ? ORDER BY fullname");
    $stmt->bind_param("s", $role_filter);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Manage Users</h1>
            <p class="lead">Add, edit, or remove users from the system</p>
        </div>
        <div class="col-md-4 text-end">
            <!-- Add New User button positioned as in the image -->
            <div class="d-grid">
                <a href="add_user.php" class="btn btn-custom rounded-3 mb-2">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
                <!-- Back button below it -->
                <a href="dashboard.php" class="btn btn-outline-secondary rounded-3">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success rounded-3"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger rounded-3"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card rounded-3 shadow-sm">
        <div class="card-header ">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $role_filter == 'all' ? 'active' : ''; ?>" href="?role=all">All Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $role_filter == 'instructor' ? 'active' : ''; ?>" href="?role=instructor">Instructors</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $role_filter == 'ta' ? 'active' : ''; ?>" href="?role=ta">Teaching Assistants</a>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <?php if (count($users) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $user['role'] == 'manager' ? 'bg-danger' : 
                                                ($user['role'] == 'instructor' ? 'bg-primary' : 'bg-success'); 
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="action-buttons-cell">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning btn-action rounded-pill">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_id'] && $user['role'] != 'manager'): ?>
                                            <a href="?delete=<?php echo $user['id']; ?>&role=<?php echo $role_filter; ?>" 
                                               class="btn btn-sm btn-danger btn-action rounded-pill" 
                                               onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                    <p>No users found with the selected role.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>