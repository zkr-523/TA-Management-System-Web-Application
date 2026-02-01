<?php
define('INCLUDED', true);
require_once '../config.php';
requireRole('manager');

$pageTitle = "Reports";
$basePath = "../";

// Get overall stats
$stats = [];

// Number of courses
$result = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($result) {
    $stats['courses'] = $result->fetch_assoc()['count'];
}

// Number of TAs
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'ta'");
if ($result) {
    $stats['tas'] = $result->fetch_assoc()['count'];
}

// Number of instructors
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'instructor'");
if ($result) {
    $stats['instructors'] = $result->fetch_assoc()['count'];
}

// Total TA hours allocated
$result = $conn->query("SELECT SUM(total_hours) as total FROM ta_assignments");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['total_hours'] = $row['total'] ? $row['total'] : 0;
}

// Get task type distribution across all courses
$task_distribution = [];
$result = $conn->query("
    SELECT 
        SUM(td.marking_hours) as total_marking,
        SUM(td.proctoring_hours) as total_proctoring,
        SUM(td.lab_supervision_hours) as total_lab
    FROM task_distribution td
");
if ($result) {
    $row = $result->fetch_assoc();
    $task_distribution['marking'] = $row['total_marking'] ? $row['total_marking'] : 0;
    $task_distribution['proctoring'] = $row['total_proctoring'] ? $row['total_proctoring'] : 0;
    $task_distribution['lab'] = $row['total_lab'] ? $row['total_lab'] : 0;
    $task_distribution['total_distributed'] = $task_distribution['marking'] + $task_distribution['proctoring'] + $task_distribution['lab'];
    $task_distribution['undistributed'] = $stats['total_hours'] - $task_distribution['total_distributed'];
}

// Get course-wise TA allocation
$course_allocations = [];
$result = $conn->query("
    SELECT 
        c.id, 
        c.course_code, 
        c.course_name, 
        u.fullname as instructor_name,
        (SELECT COUNT(*) FROM ta_assignments WHERE course_id = c.id) as ta_count,
        (SELECT SUM(total_hours) FROM ta_assignments WHERE course_id = c.id) as total_hours,
        (
            SELECT SUM(td.marking_hours) 
            FROM ta_assignments ta 
            JOIN task_distribution td ON ta.id = td.assignment_id 
            WHERE ta.course_id = c.id
        ) as marking_hours,
        (
            SELECT SUM(td.proctoring_hours) 
            FROM ta_assignments ta 
            JOIN task_distribution td ON ta.id = td.assignment_id 
            WHERE ta.course_id = c.id
        ) as proctoring_hours,
        (
            SELECT SUM(td.lab_supervision_hours) 
            FROM ta_assignments ta 
            JOIN task_distribution td ON ta.id = td.assignment_id 
            WHERE ta.course_id = c.id
        ) as lab_hours
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    ORDER BY c.course_code
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Fill null values with 0
        $row['marking_hours'] = $row['marking_hours'] ? $row['marking_hours'] : 0;
        $row['proctoring_hours'] = $row['proctoring_hours'] ? $row['proctoring_hours'] : 0;
        $row['lab_hours'] = $row['lab_hours'] ? $row['lab_hours'] : 0;
        $row['distributed_hours'] = $row['marking_hours'] + $row['proctoring_hours'] + $row['lab_hours'];
        $row['undistributed_hours'] = $row['total_hours'] - $row['distributed_hours'];
        $course_allocations[] = $row;
    }
}

// Get TA workload across all courses
$ta_workloads = [];
$result = $conn->query("
    SELECT 
        u.id,
        u.fullname,
        u.email,
        COUNT(ta.id) as course_count,
        SUM(ta.total_hours) as total_hours,
        SUM(
            CASE WHEN td.marking_hours IS NOT NULL 
            THEN td.marking_hours ELSE 0 END
        ) as marking_hours,
        SUM(
            CASE WHEN td.proctoring_hours IS NOT NULL 
            THEN td.proctoring_hours ELSE 0 END
        ) as proctoring_hours,
        SUM(
            CASE WHEN td.lab_supervision_hours IS NOT NULL 
            THEN td.lab_supervision_hours ELSE 0 END
        ) as lab_hours
    FROM users u
    LEFT JOIN ta_assignments ta ON u.id = ta.ta_id
    LEFT JOIN task_distribution td ON ta.id = td.assignment_id
    WHERE u.role = 'ta'
    GROUP BY u.id
    ORDER BY total_hours DESC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['distributed_hours'] = $row['marking_hours'] + $row['proctoring_hours'] + $row['lab_hours'];
        $row['undistributed_hours'] = $row['total_hours'] - $row['distributed_hours'];
        $ta_workloads[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
        <h1>Reports & Analytics</h1>
        <p class="lead">Comprehensive overview of TA allocations and workload distribution</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="d-grid">
                <a href="dashboard.php" class="btn btn-outline-custom rounded-3 mb-2">
                    <i class="fas fa-arrow-left"></i> Back to Dashboar
                </a>
                <button class="btn btn-custom" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            </div>
        </div>
    </div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <h5 class="card-title">Total Courses</h5>
                <h2 class="display-4"><?php echo $stats['courses']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <h5 class="card-title">Total TAs</h5>
                <h2 class="display-4"><?php echo $stats['tas']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <h5 class="card-title">Total Instructors</h5>
                <h2 class="display-4"><?php echo $stats['instructors']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <h5 class="card-title">Total Hours Allocated</h5>
                <h2 class="display-4"><?php echo $stats['total_hours']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>Task Distribution Overview</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <canvas id="taskDistributionChart" width="400" height="400"></canvas>
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Task Type</th>
                                <th>Hours</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Marking</td>
                                <?php echo $task_distribution['marking']; ?></td>
                                <td><?php echo $stats['total_hours'] > 0 ? round(($task_distribution['marking'] / $stats['total_hours']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td>Proctoring</td>
                                <td><?php echo $task_distribution['proctoring']; ?></td>
                                <td><?php echo $stats['total_hours'] > 0 ? round(($task_distribution['proctoring'] / $stats['total_hours']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td>Lab/Tutorial Supervision</td>
                                <td><?php echo $task_distribution['lab']; ?></td>
                                <td><?php echo $stats['total_hours'] > 0 ? round(($task_distribution['lab'] / $stats['total_hours']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr>
                                <td>Undistributed</td>
                                <td><?php echo $task_distribution['undistributed']; ?></td>
                                <td><?php echo $stats['total_hours'] > 0 ? round(($task_distribution['undistributed'] / $stats['total_hours']) * 100, 1) : 0; ?>%</td>
                            </tr>
                            <tr class="table-primary">
                                <td><strong>Total</strong></td>
                                <td><strong><?php echo $stats['total_hours']; ?></strong></td>
                                <td><strong>100%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Course-wise TA Allocation</h5>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="courseReportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Export
            </button>
            <ul class="dropdown-menu" aria-labelledby="courseReportDropdown">
                <li><a class="dropdown-item" href="#" onclick="exportTableToCSV('course_allocation_report.csv', 'courseTable')">CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="printTable('courseTable')">Print</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="courseTable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Instructor</th>
                        <th>TAs</th>
                        <th>Total Hours</th>
                        <th>Marking</th>
                        <th>Proctoring</th>
                        <th>Lab/Tutorial</th>
                        <th>Undistributed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($course_allocations as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                            <td><?php echo $course['ta_count']; ?></td>
                            <td><?php echo $course['total_hours']; ?></td>
                            <td><?php echo $course['marking_hours']; ?></td>
                            <td><?php echo $course['proctoring_hours']; ?></td>
                            <td><?php echo $course['lab_hours']; ?></td>
                            <td><?php echo $course['undistributed_hours']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <td colspan="4"><strong>Total</strong></td>
                        <td><strong><?php echo $stats['total_hours']; ?></strong></td>
                        <td><strong><?php echo $task_distribution['marking']; ?></strong></td>
                        <td><strong><?php echo $task_distribution['proctoring']; ?></strong></td>
                        <td><strong><?php echo $task_distribution['lab']; ?></strong></td>
                        <td><strong><?php echo $task_distribution['undistributed']; ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>TA Workload Distribution</h5>
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="taReportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Export
            </button>
            <ul class="dropdown-menu" aria-labelledby="taReportDropdown">
                <li><a class="dropdown-item" href="#" onclick="exportTableToCSV('ta_workload_report.csv', 'taTable')">CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="printTable('taTable')">Print</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="taTable">
                <thead>
                    <tr>
                        <th>TA Name</th>
                        <th>Email</th>
                        <th>Courses</th>
                        <th>Total Hours</th>
                        <th>Marking</th>
                        <th>Proctoring</th>
                        <th>Lab/Tutorial</th>
                        <th>Undistributed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ta_workloads as $ta): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ta['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($ta['email']); ?></td>
                            <td><?php echo $ta['course_count']; ?></td>
                            <td><?php echo $ta['total_hours']; ?></td>
                            <td><?php echo $ta['marking_hours']; ?></td>
                            <td><?php echo $ta['proctoring_hours']; ?></td>
                            <td><?php echo $ta['lab_hours']; ?></td>
                            <td><?php echo $ta['undistributed_hours']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5>Average Hours per Course</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <canvas id="coursesComparisonChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
<script>
// Task distribution chart
const taskCtx = document.getElementById('taskDistributionChart').getContext('2d');
const taskChart = new Chart(taskCtx, {
    type: 'pie',
    data: {
        labels: ['Marking', 'Proctoring', 'Lab/Tutorial', 'Undistributed'],
        datasets: [{
            label: 'Task Distribution',
            data: [
                <?php echo $task_distribution['marking']; ?>,
                <?php echo $task_distribution['proctoring']; ?>,
                <?php echo $task_distribution['lab']; ?>,
                <?php echo $task_distribution['undistributed']; ?>
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
                text: 'Overall Task Distribution'
            }
        }
    }
});

// Course comparison chart
const courseCtx = document.getElementById('coursesComparisonChart').getContext('2d');
const courseChart = new Chart(courseCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($course_allocations as $course): ?>
                '<?php echo $course['course_code']; ?>',
            <?php endforeach; ?>
        ],
        datasets: [
            {
                label: 'Marking Hours',
                data: [
                    <?php foreach ($course_allocations as $course): ?>
                        <?php echo $course['marking_hours']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            },
            {
                label: 'Proctoring Hours',
                data: [
                    <?php foreach ($course_allocations as $course): ?>
                        <?php echo $course['proctoring_hours']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(23, 162, 184, 0.7)',
                borderColor: 'rgba(23, 162, 184, 1)',
                borderWidth: 1
            },
            {
                label: 'Lab/Tutorial Hours',
                data: [
                    <?php foreach ($course_allocations as $course): ?>
                        <?php echo $course['lab_hours']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                borderColor: 'rgba(255, 193, 7, 1)',
                borderWidth: 1
            },
            {
                label: 'Undistributed Hours',
                data: [
                    <?php foreach ($course_allocations as $course): ?>
                        <?php echo $course['undistributed_hours']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(108, 117, 125, 0.7)',
                borderColor: 'rgba(108, 117, 125, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Hours Distribution by Course'
            }
        },
        scales: {
            x: {
                stacked: true,
            },
            y: {
                stacked: true,
                title: {
                    display: true,
                    text: 'Hours'
                }
            }
        }
    }
});

// Utility function to export table to CSV
function exportTableToCSV(filename, tableId) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            // Replace any commas in the cell text to avoid CSV parsing issues
            let data = cols[j].innerText.replace(/,/g, ' ');
            // Wrap in quotes to handle any special characters
            row.push('"' + data + '"');
        }
        
        csv.push(row.join(','));
    }
    
    // Download CSV file
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], {type: 'text/csv'});
    const downloadLink = document.createElement('a');
    
    // Create a download link
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    
    // Add link to DOM, click it, then remove it
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Print specific table
function printTable(tableId) {
    const printContents = document.getElementById(tableId).outerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <h1 style="text-align: center; margin-bottom: 20px;">Teaching Assistants Management System Report</h1>
        <h2 style="text-align: center; margin-bottom: 30px;">${tableId === 'courseTable' ? 'Course-wise TA Allocation' : 'TA Workload Distribution'}</h2>
        ${printContents}
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
}
</script>

<?php include '../includes/footer.php'; ?>