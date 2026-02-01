<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('instructor');

$pageTitle = "Distribute TA Hours";
$basePath = "../";

$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Verify instructor has access to this assignment
$instructor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT ta.id, ta.course_id, ta.ta_id, ta.total_hours, c.course_code, c.course_name, 
           u.fullname as ta_name, u.email as ta_email,
           td.marking_hours, td.proctoring_hours, td.lab_supervision_hours
    FROM ta_assignments ta
    JOIN courses c ON ta.course_id = c.id
    JOIN users u ON ta.ta_id = u.id
    LEFT JOIN task_distribution td ON ta.id = td.assignment_id
    WHERE ta.id = ? AND c.instructor_id = ?
");
$stmt->bind_param("ii", $assignment_id, $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: dashboard.php");
    exit();
}

$assignment = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $marking_hours = (int)$_POST['marking_hours'];
    $proctoring_hours = (int)$_POST['proctoring_hours'];
    $lab_supervision_hours = (int)$_POST['lab_supervision_hours'];
    
    // Validate input
    if ($marking_hours < 0 || $proctoring_hours < 0 || $lab_supervision_hours < 0) {
        $error = "Hours cannot be negative.";
    } else {
        $total_distributed = $marking_hours + $proctoring_hours + $lab_supervision_hours;
        
        if ($total_distributed > $assignment['total_hours']) {
            $error = "Total distributed hours ($total_distributed) exceeds allocated hours ({$assignment['total_hours']}).";
        } else {
            // Check if task distribution record exists
            $stmt = $conn->prepare("SELECT id FROM task_distribution WHERE assignment_id = ?");
            $stmt->bind_param("i", $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $stmt = $conn->prepare("
                    UPDATE task_distribution 
                    SET marking_hours = ?, proctoring_hours = ?, lab_supervision_hours = ? 
                    WHERE assignment_id = ?
                ");
                $stmt->bind_param("iiii", $marking_hours, $proctoring_hours, $lab_supervision_hours, $assignment_id);
            } else {
                // Insert new record
                $stmt = $conn->prepare("
                    INSERT INTO task_distribution (assignment_id, marking_hours, proctoring_hours, lab_supervision_hours) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("iiii", $assignment_id, $marking_hours, $proctoring_hours, $lab_supervision_hours);
            }
            
            if ($stmt->execute()) {
                $success = "Hours distributed successfully!";
                // Update the assignment data for display
                $assignment['marking_hours'] = $marking_hours;
                $assignment['proctoring_hours'] = $proctoring_hours;
                $assignment['lab_supervision_hours'] = $lab_supervision_hours;
            } else {
                $error = "Error distributing hours: " . $conn->error;
            }
        }
    }
}

// Calculate current distribution
$distributed_hours = $assignment['marking_hours'] + $assignment['proctoring_hours'] + $assignment['lab_supervision_hours'];
$remaining_hours = $assignment['total_hours'] - $distributed_hours;

include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Distribute TA Hours</h1>
        <p class="lead">
            Course: <?php echo htmlspecialchars($assignment['course_code'] . ' - ' . $assignment['course_name']); ?><br>
            TA: <?php echo htmlspecialchars($assignment['ta_name'] . ' (' . $assignment['ta_email'] . ')'); ?><br>
            Total Allocated Hours: <?php echo $assignment['total_hours']; ?>
        </p>
    </div>
    <div class="col-md-4 text-end">
        <a href="view_course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-outline-custom">
            <i class="fas fa-arrow-left"></i> Back to Course
        </a>
        </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5>Distribute Working Hours</h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $assignment_id; ?>" id="distribute-form">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="marking_hours" class="form-label">Marking Hours</label>
                        <input type="number" class="form-control hours-input" id="marking_hours" name="marking_hours" min="0" 
                               value="<?php echo $assignment['marking_hours'] ?? 0; ?>">
                        <div class="form-text">Hours allocated for marking assignments and exams</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="proctoring_hours" class="form-label">Proctoring Hours</label>
                        <input type="number" class="form-control hours-input" id="proctoring_hours" name="proctoring_hours" min="0" 
                               value="<?php echo $assignment['proctoring_hours'] ?? 0; ?>">
                        <div class="form-text">Hours allocated for proctoring exams</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="lab_supervision_hours" class="form-label">Lab/Tutorial Supervision Hours</label>
                        <input type="number" class="form-control hours-input" id="lab_supervision_hours" name="lab_supervision_hours" min="0" 
                               value="<?php echo $assignment['lab_supervision_hours'] ?? 0; ?>">
                        <div class="form-text">Hours allocated for supervising labs or tutorials</div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <h5>Total Hours:</h5>
                                    <h3 id="total_hours" data-total="<?php echo $assignment['total_hours']; ?>"><?php echo $assignment['total_hours']; ?></h3>
                                </div>
                                <div class="col-md-3">
                                    <h5>Distributed:</h5>
                                    <h3 id="distributed_hours"><?php echo $distributed_hours; ?></h3>
                                </div>
                                <div class="col-md-3">
                                    <h5>Remaining:</h5>
                                    <h3 id="remaining_hours" class="<?php echo $remaining_hours < 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $remaining_hours; ?>
                                    </h3>
                                </div>
                                <div class="col-md-3">
                                    <div class="progress mt-4" style="height: 25px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo ($distributed_hours / $assignment['total_hours']) * 100; ?>%;" 
                                             aria-valuenow="<?php echo $distributed_hours; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="<?php echo $assignment['total_hours']; ?>">
                                            <?php echo round(($distributed_hours / $assignment['total_hours']) * 100); ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-custom" id="save-button">Save Task Distribution</button>
                <a href="view_course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalHours = parseInt(document.getElementById('total_hours').dataset.total);
    const hoursInputs = document.querySelectorAll('.hours-input');
    const distributedHoursElement = document.getElementById('distributed_hours');
    const remainingHoursElement = document.getElementById('remaining_hours');
    const progressBar = document.querySelector('.progress-bar');
    const saveButton = document.getElementById('save-button');
    
    // Function to update the hours calculation
    function updateHoursCalculation() {
        const markingHours = parseInt(document.getElementById('marking_hours').value) || 0;
        const proctoringHours = parseInt(document.getElementById('proctoring_hours').value) || 0;
        const labHours = parseInt(document.getElementById('lab_supervision_hours').value) || 0;
        
        const distributedHours = markingHours + proctoringHours + labHours;
        const remainingHours = totalHours - distributedHours;
        
        distributedHoursElement.textContent = distributedHours;
        remainingHoursElement.textContent = remainingHours;
        
        // Update progress bar
        const percentageUsed = (distributedHours / totalHours) * 100;
        progressBar.style.width = percentageUsed + '%';
        progressBar.setAttribute('aria-valuenow', distributedHours);
        progressBar.textContent = Math.round(percentageUsed) + '%';
        
        // Update remaining hours color
        if (remainingHours < 0) {
            remainingHoursElement.classList.add('text-danger');
            remainingHoursElement.classList.remove('text-success');
            saveButton.disabled = true;
        } else {
            remainingHoursElement.classList.add('text-success');
            remainingHoursElement.classList.remove('text-danger');
            saveButton.disabled = false;
        }
    }
    
    // Add event listeners to all hour inputs
    hoursInputs.forEach(input => {
        input.addEventListener('input', updateHoursCalculation);
    });
    
    // Form validation
    document.getElementById('distribute-form').addEventListener('submit', function(event) {
        const markingHours = parseInt(document.getElementById('marking_hours').value) || 0;
        const proctoringHours = parseInt(document.getElementById('proctoring_hours').value) || 0;
        const labHours = parseInt(document.getElementById('lab_supervision_hours').value) || 0;
        
        const distributedHours = markingHours + proctoringHours + labHours;
        
        if (distributedHours > totalHours) {
            event.preventDefault();
            alert('Total distributed hours exceeds allocated hours. Please adjust your values.');
        }
    });
    
    // Initialize calculation on load
    updateHoursCalculation();
});
</script>

<?php include '../includes/footer.php'; ?>