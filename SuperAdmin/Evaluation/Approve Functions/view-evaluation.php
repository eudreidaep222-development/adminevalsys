<?php
session_start();

// 1. DATABASE CONNECTION (Go up 3 levels to Root)
require_once '../../../1database_conn/connection.php';
require_once '../../../helper/log_action.php';

// Restrict to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header("Location: /logout.php");
    exit();
}

// 2. Validate ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Invalid Evaluation ID.'];
    header("Location: ../approve-evaluation.php"); 
    exit();
}

$assignment_id = intval($_GET['id']);

// 3. Fetch Info
$queryInfo = "
    SELECT ea.id, 
           ea.status, 
           ea.assigned_at, 
           f.form_name, 
           f.versions,
           CONCAT(u1.first_name, ' ', IF(u1.middle_name IS NOT NULL, CONCAT(u1.middle_name, ' '), ''), u1.last_name) AS evaluator_name,
           u1.email AS evaluator_email, 
           d1.department_name AS evaluator_dept,
           CONCAT(u2.first_name, ' ', IF(u2.middle_name IS NOT NULL, CONCAT(u2.middle_name, ' '), ''), u2.last_name) AS evaluatee_name,
           u2.email AS evaluatee_email, 
           d2.department_name AS evaluatee_dept, 
           pos.position_name AS evaluatee_position
    FROM evaluation_assignments ea
    JOIN users u1 ON ea.evaluator_id = u1.id
    LEFT JOIN departments d1 ON u1.department_id = d1.id
    JOIN users u2 ON ea.evaluatee_id = u2.id
    LEFT JOIN departments d2 ON u2.department_id = d2.id
    LEFT JOIN positions pos ON u2.position_id = pos.id
    JOIN evaluation_forms f ON ea.form_id = f.id
    WHERE ea.id = ?
";
$stmt = $conn->prepare($queryInfo);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$info) die("Evaluation Assignment not found.");

// 4. Fetch Scores
$queryScores = "SELECT * FROM evaluation_results WHERE assignment_id = ?";
$stmtScore = $conn->prepare($queryScores);
$stmtScore->bind_param("i", $assignment_id);
$stmtScore->execute();
$scores = $stmtScore->get_result()->fetch_assoc();
$stmtScore->close();

function getAdjective($score) {
    if ($score >= 4.50) return 'Outstanding';
    if ($score >= 3.50) return 'Very Satisfactory';
    if ($score >= 2.50) return 'Satisfactory';
    if ($score >= 1.50) return 'Unsatisfactory';
    return 'Poor';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
<title>View Evaluation Details</title>
<link rel="shortcut icon" href="../../../assets/img/logo-small.png">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../../assets/plugins/fontawesome/css/all.min.css">
<link rel="stylesheet" href="../../../assets/css/style.css">
<style>
    .card-title { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
    .info-label { font-weight: 600; color: #555; }
    .score-box { font-size: 1.5rem; font-weight: bold; text-align: center; padding: 15px; border-radius: 8px; color: #fff; }
    .bg-q { background-color: #4e73df; }
    .bg-e { background-color: #1cc88a; }
    .bg-t { background-color: #36b9cc; }
    .bg-a { background-color: #f6c23e; color: #333; }
</style>
</head>

<body>
<div class="main-wrapper">

    <?php include '../../../Layout Components/header.php'; ?>
    <?php include '../../../Layout Components/sidebar.php'; ?>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">View Evaluation</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../../Dashboard/home.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="../approve-evaluation.php">Pending Evaluations</a></li>
                            <li class="breadcrumb-item active">View Details</li>
                        </ul>
                    </div>
                    <div class="col-auto float-end ms-auto">
                        <a href="../approve-evaluation.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Evaluation Information</h5>
                            <div class="row">
                                <div class="col-md-6 border-end">
                                    <h6 class="text-primary">Evaluator Details</h6>
                                    <p class="mb-1"><span class="info-label">Name:</span> <?php echo htmlspecialchars($info['evaluator_name']); ?></p>
                                    <p class="mb-1"><span class="info-label">Department:</span> <?php echo htmlspecialchars($info['evaluator_dept']); ?></p>
                                    <p class="mb-1"><span class="info-label">Email:</span> <?php echo htmlspecialchars($info['evaluator_email']); ?></p>
                                </div>
                                <div class="col-md-6 ps-4">
                                    <h6 class="text-success">Evaluatee (Subject) Details</h6>
                                    <p class="mb-1"><span class="info-label">Name:</span> <?php echo htmlspecialchars($info['evaluatee_name']); ?></p>
                                    <p class="mb-1"><span class="info-label">Position:</span> <?php echo htmlspecialchars($info['evaluatee_position']); ?></p>
                                    <p class="mb-1"><span class="info-label">Department:</span> <?php echo htmlspecialchars($info['evaluatee_dept']); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><span class="info-label">Form Type:</span> <?php echo htmlspecialchars($info['form_name']); ?> (Version <?php echo $info['versions']; ?>)</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><span class="info-label">Date Submitted:</span> <?php echo date('F d, Y h:i A', strtotime($info['assigned_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($scores): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Evaluation Results</h5>
                            <?php if ($info['versions'] == 2): ?>
                                <div class="row mb-4">
                                    <div class="col-md-3"><div class="score-box bg-q"><div>Quality</div><div><?php echo number_format($scores['q'], 2); ?></div></div></div>
                                    <div class="col-md-3"><div class="score-box bg-e"><div>Efficiency</div><div><?php echo number_format($scores['e'], 2); ?></div></div></div>
                                    <div class="col-md-3"><div class="score-box bg-t"><div>Timeliness</div><div><?php echo number_format($scores['t'], 2); ?></div></div></div>
                                    <div class="col-md-3">
                                        <div class="score-box bg-a">
                                            <div>Average</div>
                                            <div><?php echo number_format($scores['a'], 2); ?></div>
                                            <div style="font-size:0.8rem; font-weight:normal;"><?php echo getAdjective($scores['a']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Score: <?php echo isset($scores['score']) ? $scores['score'] : 'N/A'; ?></div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" class="btn btn-danger btn-lg me-2" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                
                                <form method="POST" action="../process-approval.php">
                                    <input type="hidden" name="evaluation_id" value="<?php echo $assignment_id; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Approve this evaluation?');"><i class="fas fa-check"></i> Approve</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-warning">Notice: No specific result data found.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../../../Layout Components/footer.php'; ?>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Evaluation (ID: <?php echo $assignment_id; ?>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="../process-approval.php">
                <div class="modal-body">
                    <p>Please provide a detailed reason for rejecting this evaluation. This note will be recorded in the system logs and associated with the rejection action.</p>
                    <div class="form-group">
                        <label for="rejection_note" class="form-label">Rejection Note/Reason:</label>
                        <textarea class="form-control" id="rejection_note" name="rejection_note" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="evaluation_id" value="<?php echo $assignment_id; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script> 
<script src="../../../assets/js/script.js"></script>

</body>
</html>