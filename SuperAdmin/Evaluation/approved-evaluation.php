<?php
session_start();
// Path correction: SuperAdmin/Evaluation/approved-evaluation.php -> Root
require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: /logout.php");
    exit();
}

// Fetch all approved evaluations with archive status
// **FIX:** Replaced u1.fullName and u2.fullName with CONCAT function.
$query = "
    SELECT 
        e.id, 
        -- Corrected Evaluator Name generation
        CONCAT(u1.first_name, ' ', IF(u1.middle_name IS NOT NULL, CONCAT(u1.middle_name, ' '), ''), u1.last_name) AS evaluator, 
        
        -- Corrected Evaluatee Name generation
        CONCAT(u2.first_name, ' ', IF(u2.middle_name IS NOT NULL, CONCAT(u2.middle_name, ' '), ''), u2.last_name) AS evaluatee, 
        
        d.department_name AS department, 
        dom.domain_name AS domain, 
        p.position_name AS position, 
        e.submitted_at, 
        e.archived
    FROM user_evaluations e
    JOIN users u1 ON e.evaluator_id = u1.id
    JOIN users u2 ON e.evaluatee_id = u2.id
    LEFT JOIN departments d ON u2.department_id = d.id
    LEFT JOIN domains dom ON u2.domain_id = dom.id
    LEFT JOIN positions p ON u2.position_id = p.id
    WHERE e.form_id = 2 AND e.status = 'approved'
    ORDER BY e.submitted_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$evaluations = $result->fetch_all(MYSQLI_ASSOC);

// Fetch filters
$deptRes = $conn->query("SELECT department_name FROM departments ORDER BY department_name ASC");
$departments = [];
while ($row = $deptRes->fetch_assoc()) $departments[] = $row['department_name'];

$domRes = $conn->query("SELECT domain_name FROM domains ORDER BY domain_name ASC");
$domains = [];
while ($row = $domRes->fetch_assoc()) $domains[] = $row['domain_name'];

$posRes = $conn->query("SELECT position_name FROM positions ORDER BY position_name ASC");
$positions = [];
while ($row = $posRes->fetch_assoc()) $positions[] = $row['position_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
<title>Approved Evaluations</title>
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
                    <h3 class="page-title">Approved Evaluations</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../Dashboard/home.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Approved Evaluations</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" id="searchText" class="form-control" placeholder="Search by name...">
            </div>
            <div class="col-md-2">
                <select id="filterDepartment" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterDomain" class="form-select">
                    <option value="">All Domains</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?= htmlspecialchars($domain) ?>"><?= htmlspecialchars($domain) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterPosition" class="form-select">
                    <option value="">All Positions</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= htmlspecialchars($pos) ?>"><?= htmlspecialchars($pos) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterArchive" class="form-select">
                    <option value="0" selected>Show Active</option>
                    <option value="1">Show Archived</option>
                    <option value="">All</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <table id="approvedEvalTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Evaluator</th>
                                    <th>Evaluatee</th>
                                    <th>Department</th>
                                    <th>Domain</th>
                                    <th>Position</th>
                                    <th>Date Submitted</th>
                                    <th>Archived</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($evaluations as $eval): ?>
                                <tr data-archived="<?= $eval['archived'] ?>">
                                    <td><?= $eval['id'] ?></td>
                                    <td><?= htmlspecialchars($eval['evaluator']) ?></td>
                                    <td><?= htmlspecialchars($eval['evaluatee']) ?></td>
                                    <td><?= htmlspecialchars($eval['department']) ?></td>
                                    <td><?= htmlspecialchars($eval['domain']) ?></td>
                                    <td><?= htmlspecialchars($eval['position']) ?></td>
                                    <td><?= $eval['submitted_at'] ?></td>
                                    <td><?= $eval['archived'] ? 'Yes' : 'No' ?></td>
                                    <td>
                                        <a href="view-evaluation.php?id=<?= $eval['id'] ?>" class="btn btn-info btn-sm">View</a>
                                        <button class="btn btn-warning btn-sm toggle-archive" data-id="<?= $eval['id'] ?>">
                                            <?= $eval['archived'] ? 'Restore' : 'Archive' ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
    var table = $('#approvedEvalTable').DataTable({
        pageLength: 10,
        order: [[6,'desc']],
        dom: 'lrtip' // removes default DataTables search box
    });

    // Text search
    $('#searchText').on('keyup', function() {
        table.columns([1,2]).search(this.value).draw();
    });

    // Filters
    $('#filterDepartment').on('change', function() {
        table.column(3).search(this.value).draw();
    });
    $('#filterDomain').on('change', function() {
        table.column(4).search(this.value).draw();
    });
    $('#filterPosition').on('change', function() {
        table.column(5).search(this.value).draw();
    });

    // Archived filter
    // NOTE: DataTables search/filter works best on column data. This current approach 
    // uses jQuery to manually show/hide rows based on the data-archived attribute, 
    // which bypasses DataTables' built-in filtering efficiency.
    $('#filterArchive').on('change', function() {
        var val = $(this).val();
        table.rows().every(function() {
            var archived = $(this.node()).data('archived');
            var rowNode = $(this.node());
            
            // Draw visibility logic
            if (val === '') { // Show All
                rowNode.show();
            } else if (val == '1' && archived == 1) { // Show Archived
                rowNode.show();
            } else if (val == '0' && archived == 0) { // Show Active
                rowNode.show();
            } else {
                rowNode.hide();
            }
        });
        // You might need to re-draw the table to update DataTables' internal view
        // table.draw(); // Uncomment if necessary to fix pagination issues after filtering
    });

    // Toggle archive
    $('.toggle-archive').on('click', function() {
        var row = $(this).closest('tr');
        var id = $(this).data('id');
        var btn = $(this);
        $.post('toggle-archive.php', { id: id }, function(response) {
            // Toggle in table visually
            // Use the row's data attribute for status tracking
            var archived = row.data('archived') == 1 ? 0 : 1;
            row.data('archived', archived);
            row.find('td:eq(7)').text(archived ? 'Yes' : 'No');
            btn.text(archived ? 'Restore' : 'Archive');
        }, 'json');
    });
});
</script>
</body>
</html>