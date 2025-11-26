<?php
session_start();
include '../../1database_conn/connection.php';

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'])) !== 'administrator') {
    header("Location: ../../index.php");
    exit();
}

$evaluator_id = intval($_SESSION['user_id']);

$sql = "
    SELECT ea.id AS assignment_id, 
           ef.form_name AS title, 
           -- START: Dynamic fullName Generation
           CONCAT(
               u.first_name, 
               ' ', 
               IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(SUBSTRING(u.middle_name, 1, 1), '. '), ''), 
               u.last_name
           ) AS evaluatee_name,
           -- END: Dynamic fullName Generation
           ea.status
    FROM evaluation_assignments ea
    JOIN evaluation_forms ef ON ea.form_id = ef.id
    JOIN users u ON ea.evaluatee_id = u.id
    WHERE ea.evaluator_id = ? 
    AND ef.versions = 2
    AND ea.status != 'completed'
    ORDER BY ea.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluator_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Evaluations (V2)</title>
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="../../assets/js/jquery-3.6.0.min.js"></script>
</head>

<body>
<div class="main-wrapper">

    <?php include '../../Layout Components/header.php'; ?>
    <?php include '../../Layout Components/sidebar.php'; ?>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Pending Evaluations (Version 2)</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../Dashboard/home.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">My Evaluations</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-table">
                        <div class="card-header">
                            <h4 class="card-title">Assigned Evaluation Forms</h4>
                            <p class="card-text text-muted small">These evaluations are assigned to you and ready for review.</p>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-center mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Evaluatee</th>
                                            <th>Form Title</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>

                                            <tr>
                                                <td>
                                                    <h2 class="table-avatar">
                                                        <a href="javascript:void(0);" class="avatar avatar-sm me-2">
                                                            <i class="fas fa-user-tie fa-lg text-secondary"></i>
                                                        </a>
                                                        <a href="#"><?= htmlspecialchars($row['evaluatee_name']) ?></a>
                                                    </h2>
                                                </td>

                                                <td><?= htmlspecialchars($row['title']) ?></td>

                                                <td>
                                                    <span class="badge bg-warning text-dark">
                                                        <?= htmlspecialchars(ucfirst($row['status'])) ?>
                                                    </span>
                                                </td>

                                                <td class="text-end">
                                                    <a href="edit_evaluation.php?assignment_id=<?= $row['assignment_id'] ?>"
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i> Review & Edit
                                                    </a>
                                                </td>
                                            </tr>

                                        <?php endwhile; ?>

                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No pending Version 2 evaluations found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <?php include '../../Layout Components/footer.php'; ?>
    </div>
</div>

<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script src="../../assets/js/script.js"></script>

</body>
</html>
