<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Add New User";
$basePath = "../";

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    
    if (empty($username) || empty($password) || empty($fullname) || empty($email) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!in_array($role, ['instructor', 'ta', 'manager'])) {
        $error = "Invalid role selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $email, $role);
            
            if ($stmt->execute()) {
                $success = "User created successfully!";
                
                // Clear form data
                $username = $password = $fullname = $email = $role = '';
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header custom-nav">
                <h5 style="padding-top: 5px;">Add New User</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required
                               value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="instructor" <?php echo (isset($role) && $role == 'instructor') ? 'selected' : ''; ?>>
                                Instructor
                            </option>
                            <option value="ta" <?php echo (isset($role) && $role == 'ta') ? 'selected' : ''; ?>>
                                Teaching Assistant
                            </option>
                            <option value="manager" <?php echo (isset($role) && $role == 'manager') ? 'selected' : ''; ?>>
                                Manager
                            </option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-custom">Create User</button>
                        <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
