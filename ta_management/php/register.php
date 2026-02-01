<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TA Management System - Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
    margin: 0;
    padding: 0;
}

     body {
    background: url('assets/images/alfaisalbg.jpg') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 500px 0; /* Add padding to force space at top and bottom */
}

.registration-container {
    width: 800px;
    max-width: 95%;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    background: white;
    margin: 80px 0; /* Add significant vertical margin */
}
        
        .registration-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .registration-header img {
            height: 80px;
            margin-bottom: 15px;
        }
        
        .registration-header h2 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .registration-body {
            padding: 30px;
        }
        
        .registration-notes {
    margin-top: 20px;
    margin-bottom: 20px; /* Add bottom margin */
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}
        
        .btn-register {
            background-color: #4bbf9c;
            border: none;
            padding: 12px;
            border-radius: 50px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background-color: #3da88a;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background-color: transparent;
            border: 1px solid #6c757d;
            padding: 12px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            background-color: #f8f9fa;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #f5f5f5;
            border: 1px solid #f0f0f0;
            margin-bottom: 5px;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #4bbf9c;
        }
        
        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <?php
    require_once 'config.php';

    $success = '';
    $error = '';

    // Check if the user is already logged in
    if (isLoggedIn()) {
        // Redirect based on role
        if (hasRole('manager')) {
            header("Location: manager/dashboard.php");
        } elseif (hasRole('instructor')) {
            header("Location: instructor/dashboard.php");
        } elseif (hasRole('ta')) {
            header("Location: ta/dashboard.php");
        }
        exit();
    }

    // Process registration form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        
        // Validate input
        if (empty($username) || empty($password) || empty($confirm_password) || empty($fullname) || empty($email) || empty($role)) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (!in_array($role, ['instructor', 'ta'])) {
            $error = "Invalid role selected.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
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
                    $success = "Registration successful! You can now login with your credentials.";
                    
                    // Clear form data
                    $username = $password = $confirm_password = $fullname = $email = $role = '';
                } else {
                    $error = "Error creating account: " . $conn->error;
                }
            }
        }
    }
    ?>

    <div class="registration-container">
        <div class="registration-header">
            <img src="assets/images/alfaisal_logo.png" alt="Alfaisal University Logo">
            <h2>Teaching Assistants Management System</h2>
            <h4>Registration</h4>
        </div>
        
        <div class="registration-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="d-grid gap-2">
                    <a href="index.php" class="btn btn-register">Proceed to Login</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registrationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label required-field">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required 
                                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label required-field">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fullname" class="form-label required-field">Full Name</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required
                                       value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label required-field">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                                <div class="form-text">Please use your university email if possible.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label required-field">Role</label>
                                <select class="form-select form-control" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="instructor" <?php echo (isset($role) && $role == 'instructor') ? 'selected' : ''; ?>>
                                        Instructor
                                    </option>
                                    <option value="ta" <?php echo (isset($role) && $role == 'ta') ? 'selected' : ''; ?>>
                                        Teaching Assistant
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-register">Register</button>
                        <a href="index.php" class="btn btn-back">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="registration-notes mt-4">
                <h5>Registration Notes:</h5>
                <ul>
                    <li>Manager accounts can only be created by system administrators.</li>
                    <li>New accounts may require approval before being activated.</li>
                    <li>For assistance, please contact the system administrator.</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            if (form) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                
                form.addEventListener('submit', function(event) {
                    if (password.value !== confirmPassword.value) {
                        event.preventDefault();
                        alert('Passwords do not match!');
                    }
                    
                    if (password.value.length < 8) {
                        event.preventDefault();
                        alert('Password must be at least 8 characters long!');
                    }
                });
            }
        });
    </script>
</body>
</html>