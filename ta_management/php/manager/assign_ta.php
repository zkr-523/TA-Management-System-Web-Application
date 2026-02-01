<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Assign Teaching Assistant";
$basePath = "../";

$success = '';
$error = '';

// Get all courses for dropdown
$courses = [];
$result = $conn->query("
    SELECT c.id, c.course_code, c.course_name, u.fullname as instructor_name 
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    ORDER BY c.course_code
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Get all TAs for dropdown
$tas = [];
$result = $conn->query("SELECT id, fullname, email FROM users WHERE role = 'ta' ORDER BY fullname");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tas[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if we need to create a new TA
    if (isset($_POST['new_ta']) && $_POST['new_ta'] == '1') {
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
            $error = "All fields are required for creating a new TA.";
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
                
                // Insert new TA
                $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, 'ta')");
                $stmt->bind_param("ssss", $username, $hashed_password, $fullname, $email);
                
                if ($stmt->execute()) {
                    $ta_id = $conn->insert_id;
                } else {
                    $error = "Error creating TA account: " . $conn->error;
                }
            }
        }
    } else {
        $ta_id = (int)$_POST['ta_id'];
    }
    
    // If no error so far and we have a TA ID, proceed with assignment
    if (empty($error) && isset($ta_id)) {
        $course_id = (int)$_POST['course_id'];
        $total_hours = (int)$_POST['total_hours'];
        
        if ($course_id <= 0 || $ta_id <= 0 || $total_hours <= 0) {
            $error = "Invalid course, TA or hours value.";
        } else {
            // Check if assignment already exists
            $stmt = $conn->prepare("SELECT id FROM ta_assignments WHERE course_id = ? AND ta_id = ?");
            $stmt->bind_param("ii", $course_id, $ta_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "This TA is already assigned to this course.";
            } else {
                // Insert assignment
                $stmt = $conn->prepare("INSERT INTO ta_assignments (course_id, ta_id, total_hours) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $course_id, $ta_id, $total_hours);
                
                if ($stmt->execute()) {
                    $assignment_id = $conn->insert_id;
                    
                    // Initialize task distribution record with 0 hours
                    $stmt = $conn->prepare("INSERT INTO task_distribution (assignment_id, marking_hours, proctoring_hours, lab_supervision_hours) VALUES (?, 0, 0, 0)");
                    $stmt->bind_param("i", $assignment_id);
                    $stmt->execute();
                    
                    $success = "TA assigned successfully to the course!";
                } else {
                    $error = "Error assigning TA: " . $conn->error;
                }
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
                <h5 style="padding-top: 5px;">Assign Teaching Assistant to Course</h5>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateTAAssignmentForm()">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course *</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name'] . ' (Instructor: ' . $course['instructor_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Teaching Assistant *</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ta_option" id="existing_ta" value="existing" checked>
                            <label class="form-check-label" for="existing_ta">
                                Select Existing TA
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ta_option" id="new_ta" value="new">
                            <label class="form-check-label" for="new_ta">
                                Create New TA
                            </label>
                        </div>
                    </div>
                    
                    <div id="existing_ta_form">
                        <div class="mb-3">
                            <label for="ta_id" class="form-label">Select TA *</label>
                            <select class="form-select" id="ta_id" name="ta_id">
                                <option value="">Select Teaching Assistant</option>
                                <?php foreach ($tas as $ta): ?>
                                    <option value="<?php echo $ta['id']; ?>">
                                        <?php echo htmlspecialchars($ta['fullname'] . ' (' . $ta['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div id="new_ta_form" style="display: none;">
                        <input type="hidden" name="new_ta" value="1">
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="fullname" name="fullname">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="total_hours" class="form-label">Total Hours Allocated *</label>
                        <input type="number" class="form-control" id="total_hours" name="total_hours" min="1" required>
                        <div class="form-text">The instructor will distribute these hours among different tasks.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-assign-ta">Assign TA</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const existingTaRadio = document.getElementById('existing_ta');
    const newTaRadio = document.getElementById('new_ta');
    const existingTaForm = document.getElementById('existing_ta_form');
    const newTaForm = document.getElementById('new_ta_form');
    const taIdSelect = document.getElementById('ta_id');
    
    existingTaRadio.addEventListener('change', function() {
        if (this.checked) {
            existingTaForm.style.display = 'block';
            newTaForm.style.display = 'none';
            taIdSelect.setAttribute('required', 'required');
            document.querySelector('input[name="new_ta"]').value = '0';
        }
    });
    
    newTaRadio.addEventListener('change', function() {
        if (this.checked) {
            existingTaForm.style.display = 'none';
            newTaForm.style.display = 'block';
            taIdSelect.removeAttribute('required');
            document.querySelector('input[name="new_ta"]').value = '1';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
