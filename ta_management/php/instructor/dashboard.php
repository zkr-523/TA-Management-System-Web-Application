<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('instructor');

$pageTitle = "Instructor Dashboard";
$basePath = "../";

// Get instructor's courses
$instructor_id = $_SESSION['user_id'];
$courses = [];

$result = $conn->query("
    SELECT c.id, c.course_code, c.course_name, c.students_enrolled,
           (SELECT COUNT(*) FROM ta_assignments WHERE course_id = c.id) as ta_count
    FROM courses c
    WHERE c.instructor_id = $instructor_id
    ORDER BY c.course_code
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="row align-items-center">
        <div class="col-md-12">
            <h1>Instructor Dashboard</h1>
            <p class="lead">Welcome back, <?php echo $_SESSION['fullname']; ?>! You have <?php echo count($courses); ?> course(s) assigned to you.</p>
        </div>
    </div>
</div>

<?php if (count($courses) > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5>Your Courses</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Students</th>
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
                                <td><?php echo $course['ta_count']; ?></td>
                                <td>
                                    <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-custom btn-action">
                                        <i class="fas fa-eye"></i> View TAs
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Recent Activity</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Check the "View TAs" button for each course to manage task distribution for assigned teaching assistants.
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You don't have any courses assigned to you yet. Please contact the system administrator.
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
