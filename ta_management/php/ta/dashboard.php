<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('ta');

$pageTitle = "TA Dashboard";
$basePath = "../";

// Get TA's assignments
$ta_id = $_SESSION['user_id'];
$assignments = [];

$result = $conn->query("
    SELECT c.id as course_id, c.course_code, c.course_name, 
           u.fullname as instructor_name, u.email as instructor_email,
           ta.total_hours, 
           td.marking_hours, td.proctoring_hours, td.lab_supervision_hours
    FROM ta_assignments ta
    JOIN courses c ON ta.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN task_distribution td ON ta.id = td.assignment_id
    WHERE ta.ta_id = $ta_id
    ORDER BY c.course_code
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calculate distributed and remaining hours
        $distributed_hours = $row['marking_hours'] + $row['proctoring_hours'] + $row['lab_supervision_hours'];
        $row['distributed_hours'] = $distributed_hours;
        $row['remaining_hours'] = $row['total_hours'] - $distributed_hours;
        $assignments[] = $row;
    }
}

// Calculate total hours
$total_allocated_hours = 0;
$total_distributed_hours = 0;
$total_marking_hours = 0;
$total_proctoring_hours = 0;
$total_lab_hours = 0;

foreach ($assignments as $assignment) {
    $total_allocated_hours += $assignment['total_hours'];
    $total_distributed_hours += $assignment['distributed_hours'];
    $total_marking_hours += $assignment['marking_hours'];
    $total_proctoring_hours += $assignment['proctoring_hours'];
    $total_lab_hours += $assignment['lab_supervision_hours'];
}

include '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="row align-items-center">
        <div class="col-md-12">
            <h1>Teaching Assistant Dashboard</h1>
            <p class="lead">Welcome back, <?php echo $_SESSION['fullname']; ?>! You are assigned to <?php echo count($assignments); ?> course(s).</p>
        </div>
    </div>
</div>

<?php if (count($assignments) > 0): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Hours</h5>
                    <h2 class="display-4"><?php echo $total_allocated_hours; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Marking Hours</h5>
                    <h2 class="display-4"><?php echo $total_marking_hours; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Proctoring Hours</h5>
                    <h2 class="display-4"><?php echo $total_proctoring_hours; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-dark">
                <div class="card-body text-center">
                    <h5 class="card-title">Lab Hours</h5>
                    <h2 class="display-4"><?php echo $total_lab_hours; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header custom-nav">
            <h5>Your Course Assignments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Instructor</th>
                            <th>Total Hours</th>
                            <th>Task Distribution</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assignment['course_code']); ?></strong><br>
                                    <?php echo htmlspecialchars($assignment['course_name']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($assignment['instructor_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($assignment['instructor_email']); ?></small>
                                </td>
                                <td><?php echo $assignment['total_hours']; ?> hrs</td>
                                <td>
                                    <?php if ($assignment['distributed_hours'] > 0): ?>
                                        <div class="progress mb-2" style="height: 20px;">
                                            <?php if ($assignment['marking_hours'] > 0): ?>
                                                <div class="progress-bar bg-success" style="width: <?php echo ($assignment['marking_hours'] / $assignment['total_hours']) * 100; ?>%">
                                                    Marking: <?php echo $assignment['marking_hours']; ?> hrs
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($assignment['proctoring_hours'] > 0): ?>
                                                <div class="progress-bar bg-info" style="width: <?php echo ($assignment['proctoring_hours'] / $assignment['total_hours']) * 100; ?>%">
                                                    Proctoring: <?php echo $assignment['proctoring_hours']; ?> hrs
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($assignment['lab_supervision_hours'] > 0): ?>
                                                <div class="progress-bar bg-warning" style="width: <?php echo ($assignment['lab_supervision_hours'] / $assignment['total_hours']) * 100; ?>%">
                                                    Lab: <?php echo $assignment['lab_supervision_hours']; ?> hrs
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <small>
                                            Marking: <?php echo $assignment['marking_hours']; ?> hrs,
                                            Proctoring: <?php echo $assignment['proctoring_hours']; ?> hrs,
                                            Lab/Tutorial: <?php echo $assignment['lab_supervision_hours']; ?> hrs
                                        </small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not yet distributed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($assignment['distributed_hours'] === 0): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($assignment['distributed_hours'] < $assignment['total_hours']): ?>
                                        <span class="badge bg-info">Partially Distributed</span>
                                        <small class="d-block"><?php echo $assignment['remaining_hours']; ?> hrs remaining</small>
                                    <?php else: ?>
                                        <span class="badge bg-success">Fully Distributed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header custom-nav">
            <h5>Hours Distribution Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <canvas id="distributionChart" width="400" height="400"></canvas>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Allocated Hours</h5>
                            <p class="card-text display-6"><?php echo $total_allocated_hours; ?></p>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Distributed Hours</h5>
                            <p class="card-text display-6"><?php echo $total_distributed_hours; ?></p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Remaining Hours</h5>
                            <p class="card-text display-6"><?php echo $total_allocated_hours - $total_distributed_hours; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> You haven't been assigned to any courses yet. Please contact your instructor or the system administrator.
    </div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (count($assignments) > 0): ?>
    // Create chart
    const ctx = document.getElementById('distributionChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Marking', 'Proctoring', 'Lab/Tutorial', 'Unallocated'],
            datasets: [{
                label: 'Hours Distribution',
                data: [
                    <?php echo $total_marking_hours; ?>,
                    <?php echo $total_proctoring_hours; ?>,
                    <?php echo $total_lab_hours; ?>,
                    <?php echo $total_allocated_hours - $total_distributed_hours; ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(23, 162, 184, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(108, 117, 125, 0.7)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(108, 117, 125, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'Hours Distribution by Task Type'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>
