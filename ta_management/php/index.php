<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TA Management System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('assets/images/alfaisalbg.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        
        .login-container {
            width: 900px;
            max-width: 90%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .login-panel {
            background: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
        }
        
        .register-panel {
            background: linear-gradient(135deg, #4bbf9c 0%, #217e9c 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        
        .btn-login {
            background-color: #4bbf9c;
            border: none;
            padding: 12px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #3da88a;
            transform: translateY(-2px);
        }
        
        .btn-register {
            background-color: transparent;
            border: 2px solid white;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #f5f5f5;
            border: 1px solid #f0f0f0;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #4bbf9c;
        }
        
        .login-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .register-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-container img {
            height: 80px;
        }
    </style>
</head>
<body>
    <?php
    require_once 'config.php';

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

    // Process login form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Validate input
        if (empty($username) || empty($password)) {
            $error = "Username and password are required";
        } else {
            // Prepare a statement
            $stmt = $conn->prepare("SELECT id, username, password, fullname, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    if ($user['role'] == 'manager') {
                        header("Location: manager/dashboard.php");
                    } elseif ($user['role'] == 'instructor') {
                        header("Location: instructor/dashboard.php");
                    } elseif ($user['role'] == 'ta') {
                        header("Location: ta/dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "User not found";
            }
            
            $stmt->close();
        }
    }
    ?>

    <div class="login-container">
        <div class="row g-0">
            <!-- Login Panel (Left Side) -->
            <div class="col-md-6 login-panel">
                <div class="logo-container">
                    <img src="assets/images/alfaisal_logo.png" alt="Alfaisal University Logo">
                </div>
                
                <h1 class="login-title">SIGN IN</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    </div>
                    
                    <div class="mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-login text-white">Login</button>
                    </div>
                </form>
                                
                <div class="social-login">
                    <div class="social-btn">
                        <i class="fas fa-university text-primary"></i>
                    </div>
                    <div class="social-btn">
                        <i class="fab fa-google"></i>
                    </div>
                    <div class="social-btn">
                        <i class="fab fa-linkedin-in text-primary"></i>
                    </div>
                </div>
            </div>
            
            <!-- Registration Panel (Right Side) -->
            <div class="col-md-6 register-panel">
    <h1 class="register-title">Join our TA Management System</h1>
    <p class="mb-5">If you're an instructor or teaching assistant at Alfaisal University, create your account to manage course assignments and tasks.</p>
    <a href="register.php" class="btn btn-register">Register Now</a>
</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>