<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Edit Course";
$basePath = "../";

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Get all instructors for dropdown
$instructors = [];
$result = $conn->query("SELECT id, fullname FROM users WHERE role = 'instructor' ORDER BY fullname");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $instructors[] = $row;
    }
}

// Get course data
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$course = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $students_enrolled = (int)$_POST['students_enrolled'];
    $instructor_id = (int)$_POST['instructor_id'];
    
    if (empty($course_code) || empty($course_name) || $students_enrolled <= 0 || $instructor_id <= 0) {
        $error = "All fields are required and students enrolled must be a positive number.";
    } else {
        // Check if course code already exists for other courses
        $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
        $stmt->bind_param("si", $course_code, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Course code already exists for another course.";
        } else {
            // Check if instructor exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'instructor'");
            $stmt->bind_param("i", $instructor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "Selected instructor does not exist.";
            } else {
                // Update course
                $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_name = ?, students_enrolled = ?, instructor_id = ? WHERE id = ?");
                $stmt->bind_param("ssiii", $course_code, $course_name, $students_enrolled, $instructor_id, $course_id);
                
                if ($stmt->execute()) {
                    $success = "Course updated successfully!";
                    
                    // Update course data for display
                    $course['course_code'] = $course_code;
                    $course['course_name'] = $course_name;
                    $course['students_enrolled'] = $students_enrolled;
                    $course['instructor_id'] = $instructor_id;
                } else {
                    $error = "Error updating course: " . $conn->error;
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
            <div class="card-header custom-nav d-flex justify-content-between align-items-center">
                <h5 style="padding-top: 5px;">Edit Course</h5>
                <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-sm btn-custom">
                    <i class="fas fa-arrow-left"></i> Back to Course
                </a>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $course_id); ?>" onsubmit="return validateCourseForm()">
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code *</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" required 
                               value="<?php echo htmlspecialchars($course['course_code']); ?>">
                        <div class="form-text">Example: CS101, MATH202</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name *</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" required
                               value="<?php echo htmlspecialchars($course['course_name']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="students_enrolled" class="form-label">Number of Students Enrolled *</label>
                        <input type="number" class="form-control" id="students_enrolled" name="students_enrolled" min="1" required
                               value="<?php echo $course['students_enrolled']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="instructor_id" class="form-label">Instructor *</label>
                        <select class="form-select" id="instructor_id" name="instructor_id" required>
                            <option value="">Select Instructor</option>
                            <?php foreach ($instructors as $instructor): ?>
                                <option value="<?php echo $instructor['id']; ?>" 
                                    <?php echo ($course['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($instructor['fullname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">If the instructor is not in the list, add them from the <a href="manage_users.php" style="color:#08979c">Users</a> section.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-custom">Save Changes</button>
                        <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>