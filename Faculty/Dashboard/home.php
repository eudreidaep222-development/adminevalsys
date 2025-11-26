<?php
session_start();
include '../../1database_conn/connection.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}
    if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'faculty') {
          header("Location: ../../index.php"); 
          exit();
        }

$evaluator_id = intval($_SESSION['user_id']);

$sql = "
    SELECT ea.id AS assignment_id, 
           -- START NAME CONCATENATION HERE
           CONCAT(u.first_name, ' ', IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(SUBSTRING(u.middle_name, 1, 1), '. '), ''), u.last_name) AS fullName,
           -- END NAME CONCATENATION
           d.department_name,
           CASE 
               WHEN COUNT(er.id) > 0 THEN 'completed' 
               ELSE 'pending' 
           END AS status
    FROM evaluation_assignments ea
    INNER JOIN users u ON ea.evaluatee_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN evaluation_results er 
           ON er.admin_id = ea.evaluatee_id
          AND er.evaluator_id = ea.evaluator_id
          AND er.form_id = ea.form_id
          AND er.academic_id = ea.academic_id
    WHERE ea.evaluator_id = $evaluator_id
    -- Update GROUP BY to use new component fields for naming, or keep the alias if supported by your MariaDB version (using alias is simpler).
    GROUP BY ea.id, fullName, d.department_name
";

$result = $conn->query($sql);

$assignments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <title>Evaluator Dashboard</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css" />
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../../assets/css/style.css" />
</head>

<body>
<div class="main-wrapper">

    <?php
    include '../../Layout Components/header.php';
    include '../../Layout Components/sidebar.php';
    ?>

    <div class="page-wrapper">
        <div class="content container-fluid">
            
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Evaluator Dashboard</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Assigned Evaluations</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body" style="background-color:#008000; color:black; border-radius:12px;">

                            <?php if (count($assignments) > 0): ?>

                                    <!-- Notice -->
                                <div class="alert alert-warning mb-3 text-center">
                                    <i class="fa fa-hand-pointer me-2"></i>
                                    Select an employee card below to view details and start the evaluation.
                                </div>
                                
                                <div class="row">
                                    <!-- Grid of assigned evaluatees -->
                                    <div class="col-md-9">
                                        <div class="row" id="employeeGrid">
                                            <?php foreach ($assignments as $a): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card employee-card" 
                                                    onclick="selectEmployee(
                                                        '<?php echo htmlspecialchars($a['fullName']); ?>',
                                                        '<?php echo htmlspecialchars($a['department_name']); ?>',
                                                        '<?php echo $a['assignment_id']; ?>',
                                                        '<?php echo $a['status']; ?>')"
                                                    style="cursor: pointer;">
                                                    <div class="card-body text-center">
                                                        <i class="fa fa-user fa-2x mb-2" style="color:#008000;"></i>
                                                        <h5 class="card-title mb-1">
                                                            <?php echo htmlspecialchars($a['fullName']); ?>
                                                        </h5>
                                                        <p class="text-muted small">
                                                            <?php echo htmlspecialchars($a['department_name'] ?? ''); ?>
                                                        </p>
                                                        <?php if ($a['status'] == 'completed'): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Right Panel -->
                                    <div class="col-md-3">
                                        <div class="card text-center p-3 mb-3">
                                            <i class="fa fa-clipboard-check fa-2x mb-2" style="color:#ffdb19;"></i>
                                            <h3>
                                                <?php 
                                                    echo count(array_filter($assignments, function($a) {
                                                        return $a['status'] === 'pending';
                                                    })); 
                                                ?>
                                            </h3>
                                            <p>Total Assigned</p>
                                        </div>
                                        <div class="card p-3" id="selectedEmployeeBox" style="display: none;">
                                            <p class="text-muted mb-1" id="selectedDept">Department</p>
                                            <span class="badge bg-info mb-2" id="selectedStatus">Status</span>
                                            <h5 id="selectedName">Name</h5>
                                            <a id="evaluateBtn" href="#" class="btn btn-dark w-100">
                                                <i class="fa fa-edit me-1"></i> Start Evaluation
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No evaluation assignments found for you at this time.
                                </div>
                            <?php endif; ?>

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
<script src="../../assets/plugins/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
<script src="../../assets/plugins/twitter-bootstrap-wizard/form-wizard.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
function selectEmployee(fullName, dept, assignmentId, status) {
    // 1. DIRECT REDIRECT: If pending, go straight to evaluation
    if (status.toLowerCase() === 'pending') {
        window.location.href = `evaluation.php?assignment_id=${assignmentId}&fullName=${encodeURIComponent(fullName)}`;
        return; // Stop execution so side panel doesn't open
    }

    // 2. EXISTING LOGIC: Keep side panel ONLY for 'Completed' items (view only)
    document.getElementById("selectedEmployeeBox").style.display = "block";
    document.getElementById("selectedName").innerText = fullName;
    document.getElementById("selectedDept").innerText = dept || "N/A";

    let statusText = status.charAt(0).toUpperCase() + status.slice(1);
    document.getElementById("selectedStatus").innerText = statusText;

    const evaluateBtn = document.getElementById("evaluateBtn");
    
    // Keep link structure just in case
    evaluateBtn.href = `evaluation.php?assignment_id=${assignmentId}&fullName=${encodeURIComponent(fullName)}`;

    // For completed items, keep the button disabled/visual only
    if (status.toLowerCase() === 'completed') {
        evaluateBtn.classList.add('disabled');
        evaluateBtn.setAttribute('aria-disabled', 'true');
        evaluateBtn.style.pointerEvents = 'none';
        evaluateBtn.innerHTML = '<i class="fa fa-check me-1"></i> Evaluation Completed';
    }
}
</script>

</body>
</html>
