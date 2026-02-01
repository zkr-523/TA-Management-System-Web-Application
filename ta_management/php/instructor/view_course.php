<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('instructor');

$pageTitle = "View Course TAs";
$basePath = "../";

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = '';

// Verify instructor has access to this course
$instructor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, course_code, course_name, students_enrolled FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$course = $result->fetch_assoc();

// Get all TAs assigned to this course
$tas = [];
$stmt = $conn->query("
    SELECT ta.id as assignment_id, u.id as ta_id, u.fullname, u.email, ta.total_hours,
           td.marking_hours, td.proctoring_hours, td.lab_supervision_hours
    FROM ta_assignments ta
    JOIN users u ON ta.ta_id = u.id
    LEFT JOIN task_distribution td ON ta.id = td.assignment_id
    WHERE ta.course_id = $course_id
    ORDER BY u.fullname
");

if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        // Calculate distributed and remaining hours
        $distributed_hours = $row['marking_hours'] + $row['proctoring_hours'] + $row['lab_supervision_hours'];
        $row['distributed_hours'] = $distributed_hours;
        $row['remaining_hours'] = $row['total_hours'] - $distributed_hours;
        $tas[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1><?php echo htmlspecialchars($course['course_code']); ?> - <?php echo htmlspecialchars($course['course_name']); ?></h1>
        <p class="lead">Students Enrolled: <?php echo htmlspecialchars($course['students_enrolled']); ?></p>
    </div>
    <div class="col-md-4 text-end">
        <a href="dashboard.php" class="btn btn-outline-custom"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (count($tas) > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5>Teaching Assistants (<?php echo count($tas); ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Total Hours</th>
                            <th>Distributed</th>
                            <th>Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tas as $ta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ta['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($ta['email']); ?></td>
                                <td><?php echo $ta['total_hours']; ?></td>
                                <td>
                                    <?php echo $ta['distributed_hours']; ?>
                                    <?php if ($ta['distributed_hours'] > 0): ?>
                                        <span class="text-muted">
                                            (M: <?php echo $ta['marking_hours']; ?>,
                                            P: <?php echo $ta['proctoring_hours']; ?>,
                                            L: <?php echo $ta['lab_supervision_hours']; ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $ta['remaining_hours'] < 0 ? 'text-danger' : ''; ?>">
                                        <?php echo $ta['remaining_hours']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="distribute_hours.php?id=<?php echo $ta['assignment_id']; ?>" class="btn btn-sm btn-custom btn-action">
                                        <i class="fas fa-tasks"></i> Distribute Hours
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> No teaching assistants have been assigned to this course yet. Please contact the system administrator.
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
