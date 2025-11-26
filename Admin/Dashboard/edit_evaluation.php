<?php
session_start();
include '../../1database_conn/connection.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['assignment_id'])) {
    header("Location: evaluator_assignments.php");
    exit();
}

$assignment_id = intval($_GET['assignment_id']);
$evaluator_id = intval($_SESSION['user_id']);

// 1. Fetch Assignment Details & Verify Ownership
$sql = "
    SELECT ea.id, ef.id as form_id, ef.form_name, u.fullName as evaluatee_name
    FROM evaluation_assignments ea
    JOIN evaluation_forms ef ON ea.form_id = ef.id
    JOIN users u ON ea.evaluatee_id = u.id
    WHERE ea.id = ? AND ea.evaluator_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $assignment_id, $evaluator_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    die("Assignment not found or access denied.");
}

// 2. Fetch Criteria (Questions) linked to this form
// We use LEFT JOIN to get existing answers if any (from evaluation_form_details)
$form_id = $assignment['form_id'];
$criteria_sql = "SELECT * FROM evaluation_form_details WHERE form_id = ?";
$cStmt = $conn->prepare($criteria_sql);
$cStmt->bind_param("i", $form_id);
$cStmt->execute();
$criteria = $cStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Review Evaluation</title>
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body>
<div class="main-wrapper">
    <?php include '../../Layout Components/header.php'; ?>
    <?php include '../../Layout Components/sidebar.php'; ?>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <h3 class="page-title">Review: <?= htmlspecialchars($assignment['evaluatee_name']) ?></h3>
                <p class="text-muted">Form: <?= htmlspecialchars($assignment['form_name']) ?></p>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="reviewForm">
                        <input type="hidden" name="assignment_id" value="<?= $assignment_id ?>">
                        <input type="hidden" name="form_id" value="<?= $form_id ?>">

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">Output</th>
                                        <th width="30%">Success Indicator</th>
                                        <th width="40%">Actual Accomplishment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($criteria->num_rows > 0): ?>
                                        <?php while ($row = $criteria->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <!-- Hidden ID to track which row to update -->
                                                    <input type="hidden" name="criteria_id[]" value="<?= $row['id'] ?>">
                                                    <textarea class="form-control" name="output[]" rows="3" required><?= htmlspecialchars($row['output_name']) ?></textarea>
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="success_indicator[]" rows="3" required><?= htmlspecialchars($row['success_indicator']) ?></textarea>
                                                </td>
                                                <td>
                                                    <textarea class="form-control" name="accomplishment[]" rows="3" placeholder="Enter accomplishments here..."><?= htmlspecialchars($row['accomplishment']) ?></textarea>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center">No criteria found for this form.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle"></i> Submit for Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function() {
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Sending...');

        $.ajax({
            url: 'save_evaluation_review.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => window.location.href = "evaluator_assignments.php", 1500);
                } else {
                    toastr.error(response.error);
                    btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit for Approval');
                }
            },
            error: function() {
                toastr.error("Server Error. Please try again.");
                btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit for Approval');
            }
        });
    });
});
</script>
</body>
</html>