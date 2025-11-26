<?php
session_start();
require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: /logout.php");
    exit();
}

// Fetch all Version 2 assignments with status 'pending'
// FIXED: Replaced u1.fullName and u2.fullName with CONCAT logic
$query = "
SELECT ea.id, 
       CONCAT(u1.first_name, ' ', IF(u1.middle_name IS NOT NULL AND u1.middle_name != '', CONCAT(u1.middle_name, ' '), ''), u1.last_name) AS evaluator, 
       CONCAT(u2.first_name, ' ', IF(u2.middle_name IS NOT NULL AND u2.middle_name != '', CONCAT(u2.middle_name, ' '), ''), u2.last_name) AS evaluatee,
       d.department_name AS department, 
       dom.domain_name AS domain,
       p.position_name AS position,
       ea.assigned_at AS submitted_at
FROM evaluation_assignments ea
JOIN users u1 ON ea.evaluator_id = u1.id
JOIN users u2 ON ea.evaluatee_id = u2.id
LEFT JOIN departments d ON u2.department_id = d.id
LEFT JOIN domains dom ON u2.domain_id = dom.id
LEFT JOIN positions p ON u2.position_id = p.id
JOIN evaluation_forms f ON ea.form_id = f.id
WHERE f.versions = 2 AND ea.status = 'pending'
ORDER BY ea.assigned_at DESC
";


$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$evaluations = $result->fetch_all(MYSQLI_ASSOC);

// Departments
$deptRes = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
$departments = [];
while ($row = $deptRes->fetch_assoc()) {
    $departments[] = $row['department_name'];
}

// Domains
$domRes = $conn->query("SELECT id, domain_name FROM domains ORDER BY domain_name ASC");
$domains = [];
while ($row = $domRes->fetch_assoc()) {
    $domains[] = $row['domain_name'];
}

// Positions
$posRes = $conn->query("SELECT id, position_name FROM positions ORDER BY position_name ASC");
$positions = [];
while ($row = $posRes->fetch_assoc()) {
    $positions[] = $row['position_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
<title>Approve Evaluations V2</title>
<link rel="shortcut icon" href="../../assets/img/logo-small.png">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body>
<div class="main-wrapper">

<?php
include '../../Layout Components/header.php';
include '../../Layout Components/sidebar.php';
?>

<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Approve Evaluations</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../Dashboard/home.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pending Evaluations</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" id="searchName" class="form-control" placeholder="Search by name...">
            </div>
            <div class="col-md-3">
                <select id="filterDepartment" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterDomain" class="form-select">
                    <option value="">All Domains</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?php echo htmlspecialchars($domain); ?>"><?php echo htmlspecialchars($domain); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterPosition" class="form-select">
                    <option value="">All Positions</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?php echo htmlspecialchars($pos); ?>"><?php echo htmlspecialchars($pos); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <table id="evaluationsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Evaluator</th>
                                    <th>Evaluatee</th>
                                    <th>Department</th>
                                    <th>Domain</th>
                                    <th>Position</th>
                                    <th>Date Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($evaluations)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No evaluations pending approval.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($evaluations as $eval): ?>
                                        <tr>
                                            <td><?php echo $eval['id']; ?></td>
                                            <td><?php echo htmlspecialchars($eval['evaluator']); ?></td>
                                            <td><?php echo htmlspecialchars($eval['evaluatee']); ?></td>
                                            <td><?php echo htmlspecialchars($eval['department']); ?></td>
                                            <td><?php echo htmlspecialchars($eval['domain']); ?></td>
                                            <td><?php echo htmlspecialchars($eval['position']); ?></td>
                                            <td><?php echo $eval['submitted_at']; ?></td>
                                            <td>
                                                <a href="Approve Functions/view-evaluation.php?id=<?php echo $eval['id']; ?>" class="btn btn-info btn-sm">View</a>
                                                <form method="POST" action="Approve Functions/process-approval.php" style="display:inline;">
                                                    <input type="hidden" name="evaluation_id" value="<?php echo $eval['id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php include '../../Layout Components/footer.php'; ?>
</div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/feather.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#evaluationsTable').DataTable({
        "order": [[ 6, "desc" ]],
        "pageLength": 10,
    });

    // Search by evaluator/evaluatee
    $('#searchName').on('keyup', function() {
        table.columns([1,2]).search(this.value).draw();
    });

    // Filter by Department
    $('#filterDepartment').on('change', function() {
        table.column(3).search(this.value).draw();
    });

    // Filter by Domain
    $('#filterDomain').on('change', function() {
        table.column(4).search(this.value).draw();
    });

    // Filter by Position
    $('#filterPosition').on('change', function() {
        table.column(5).search(this.value).draw();
    });
});
</script>
</body>
</html>