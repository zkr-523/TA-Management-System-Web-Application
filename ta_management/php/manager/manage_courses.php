<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Manage Courses";
$basePath = "../";

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = '';

// Process course deletion if requested
if (isset($_GET['delete']) && $_GET['delete'] > 0) {
    $course_id = (int)$_GET['delete'];
    
    // Check if course exists
    $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = "Course not found.";
    } else {
        // Delete course (ta_assignments will be deleted due to CASCADE constraint)
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $success = "Course deleted successfully.";
        } else {
            $error = "Error deleting course: " . $conn->error;
        }
    }
}

// Get all courses
$courses = [];
$result = $conn->query("
    SELECT c.id, c.course_code, c.course_name, c.students_enrolled, u.fullname as instructor_name, 
           (SELECT COUNT(*) FROM ta_assignments WHERE course_id = c.id) as ta_count
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    ORDER BY c.course_code
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Manage Courses</h1>
            <p class="lead">View, edit and delete courses</p>
        </div>
        <div class="col-md-4 text-end">
            <!-- Create New Course button positioned as in the image -->
            <div class="d-grid">
                <a href="create_course.php" class="btn btn-custom  rounded-3 mb-2">
                    <i class="fas fa-plus"></i> Create New Course
                </a>
                <!-- Back button below it -->
                <a href="dashboard.php" class="btn btn-outline-secondary rounded-3">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card rounded-3 shadow-sm">
        <div class="card-header custom-nav">
            <h5>All Courses (<?php echo count($courses); ?>)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (count($courses) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Students</th>
                                <th>Instructor</th>
                                <th>TAs Assigned</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['students_enrolled']); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                    <td><?php echo $course['ta_count']; ?></td>
                                    <td class="action-buttons-cell">
                                        <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-dark btn-action rounded-pill">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-warning btn-action rounded-pill">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?php echo $course['id']; ?>" 
                                           class="btn btn-sm btn-danger btn-action rounded-pill" 
                                           onclick="return confirm('Are you sure you want to delete this course? This will also delete all TA assignments for this course.');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                    <p>No courses found. <a href="create_course.php" class="alert-link">Create your first course</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>