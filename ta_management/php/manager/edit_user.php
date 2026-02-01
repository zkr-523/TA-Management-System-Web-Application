<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Edit User";
$basePath = "../";

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php");
    exit();
}

$user = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $new_password = trim($_POST['new_password']);
    
    if (empty($username) || empty($fullname) || empty($email) || empty($role)) {
        $error = "All fields except password are required.";
    } elseif (!in_array($role, ['instructor', 'ta', 'manager'])) {
        $error = "Invalid role selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if username or email already exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists for another user.";
        } else {
            // Update user information
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, fullname = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $username, $fullname, $email, $role, $hashed_password, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET username = ?, fullname = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $username, $fullname, $email, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $success = "User updated successfully!";
                
                // Update user data for display
                $user['username'] = $username;
                $user['fullname'] = $fullname;
                $user['email'] = $email;
                $user['role'] = $role;
            } else {
                $error = "Error updating user: " . $conn->error;
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Edit User</h5>
                <a href="manage_users.php" class="btn btn-sm btn-outline-custom">
                    <i class="fas fa-arrow-left"></i> Back to Users
                </a>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $user_id); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="Leave blank to keep current password">
                        <div class="form-text">Only fill this if you want to change the password.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required
                               value="<?php echo htmlspecialchars($user['fullname']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="instructor" <?php echo ($user['role'] == 'instructor') ? 'selected' : ''; ?>>
                                Instructor
                            </option>
                            <option value="ta" <?php echo ($user['role'] == 'ta') ? 'selected' : ''; ?>>
                                Teaching Assistant
                            </option>
                            <option value="manager" <?php echo ($user['role'] == 'manager') ? 'selected' : ''; ?>>
                                Manager
                            </option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-custom">Save Changes</button>
                        <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>