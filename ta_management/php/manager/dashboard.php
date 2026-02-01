<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Manager Dashboard";
$basePath = "../";

// Initialize variables with default values to prevent errors
$courseCount = 0;
$taCount = 0;
$instructorCount = 0;
$latestCourses = []; // Initialize as empty array

// Get statistics
$result = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($result) {
    $courseCount = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'ta'");
if ($result) {
    $taCount = $result->fetch_assoc()['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'instructor'");
if ($result) {
    $instructorCount = $result->fetch_assoc()['count'];
}

// Get latest courses
$result = $conn->query("
    SELECT c.id, c.course_code, c.course_name, c.students_enrolled, u.fullname as instructor_name, 
           (SELECT COUNT(*) FROM ta_assignments WHERE course_id = c.id) as ta_count
    FROM courses c
    LEFT JOIN users u ON c.instructor_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestCourses[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>Manager Dashboard</h1>
        <div class="header-actions">
            <a href="create_course.php" class="btn btn-create-course">
                <i class="fas fa-plus"></i> Create New Course
            </a>
            <a href="assign_ta.php" class="btn btn-assign-ta">
                <i class="fas fa-user-plus"></i> Assign TA
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <!-- Left column for stats cards -->
            <div class="row">
                <div class="col-12">
                    <div class="card stats-card courses-card">
                        <i class="fas fa-book card-icon"></i>
                        <h5>Total Courses</h5>
                        <h2 class="display-4"><?php echo $courseCount; ?></h2>
                        <a href="create_course.php" class="text-white">Add new course</a>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="card stats-card tas-card">
                        <i class="fas fa-users card-icon"></i>
                        <h5>Teaching Assistants</h5>
                        <h2 class="display-4"><?php echo $taCount; ?></h2>
                        <a href="manage_users.php?role=ta" class="text-white">Manage TAs</a>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="card stats-card instructors-card">
                        <i class="fas fa-chalkboard-teacher card-icon"></i>
                        <h5>Instructors</h5>
                        <h2 class="display-4"><?php echo $instructorCount; ?></h2>
                        <a href="manage_users.php?role=instructor" class="text-white">Manage Instructors</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Right column for courses table -->
            <div class="card courses-table">
                <div class="card-header custom-nav">
                    <h5>Latest Courses</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($latestCourses) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Students</th>
                                        <th>Instructor</th>
                                        <th>TAs</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestCourses as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars($course['students_enrolled']); ?></td>
                                            <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($course['ta_count']); ?></td>
                                            <td class="action-buttons-cell">
                                                <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-view btn-action">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-edit btn-action">
                                                    <i class="fas fa-edit"></i> Edit
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
                            <p>No courses found. <a href="create_course.php">Create your first course</a>.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer ">
                    <a href="manage_courses.php" class="btn view-all-button">
                        <i class="fas fa-list"></i> View All Courses
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>