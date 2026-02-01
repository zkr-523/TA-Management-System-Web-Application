<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "View Course";
$basePath = "../";

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Verify course exists
$stmt = $conn->prepare("
    SELECT c.id, c.course_code, c.course_name, c.students_enrolled, 
           u.fullname as instructor_name, u.email as instructor_email
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $course_id);
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
        <p>Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?> (<?php echo htmlspecialchars($course['instructor_email']); ?>)</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="dashboard.php" class="btn btn-outline-custom"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <a href="edit_course.php?id=<?php echo $course_id; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit Course</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header custom-nav d-flex justify-content-between align-items-center">
        <h5 style="padding-top:5px">Teaching Assistants (<?php echo count($tas); ?>)</h5>
        <a href="assign_ta.php?course_id=<?php echo $course_id; ?>" class="btn btn-sm btn-custom"><i class="fas fa-user-plus"></i> Assign New TA</a>
    </div>
    <div class="card-body">
        <?php if (count($tas) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Total Hours</th>
                            <th>Distributed</th>
                            <th>Remaining</th>
                            <th>Task Distribution</th>
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
                                    <div class="progress" style="height: 20px;">
                                        <?php if ($ta['marking_hours'] > 0): ?>
                                            <div class="progress-bar bg-success" style="width: <?php echo ($ta['marking_hours'] / $ta['total_hours']) * 100; ?>%">
                                                <?php echo $ta['marking_hours']; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($ta['proctoring_hours'] > 0): ?>
                                            <div class="progress-bar bg-info" style="width: <?php echo ($ta['proctoring_hours'] / $ta['total_hours']) * 100; ?>%">
                                                <?php echo $ta['proctoring_hours']; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($ta['lab_supervision_hours'] > 0): ?>
                                            <div class="progress-bar bg-warning" style="width: <?php echo ($ta['lab_supervision_hours'] / $ta['total_hours']) * 100; ?>%">
                                                <?php echo $ta['lab_supervision_hours']; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($ta['remaining_hours'] > 0): ?>
                                            <div class="progress-bar bg-secondary" style="width: <?php echo ($ta['remaining_hours'] / $ta['total_hours']) * 100; ?>%">
                                                <?php echo $ta['remaining_hours']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No teaching assistants have been assigned to this course yet.
                <a href="assign_ta.php?course_id=<?php echo $course_id; ?>" class="alert-link">Assign a TA now</a>.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>