<?php
include '../../1database_conn/connection.php';
session_start();

// Get assignment_id from query string
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
if ($assignment_id <= 0) {
    die("Invalid assignment ID");
}

// Fetch assignment and evaluatee info
$stmt = $conn->prepare("
    SELECT ea.id AS assignment_id, ea.form_id, ea.evaluator_id, u.fullName AS evaluatee_name, d.department_name
    FROM evaluation_assignments ea
    INNER JOIN users u ON ea.evaluatee_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE ea.id = ? AND ea.status='completed'
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("No completed evaluation found for this assignment.");
$assignment = $result->fetch_assoc();
$stmt->close();

// Fetch evaluation results for this assignment and evaluator
$evaluator_id = intval($assignment['evaluator_id']);
$results = [];
$res = $conn->prepare("
    SELECT q, e, t, a, submitted_at
    FROM evaluation_results
    WHERE assignment_id = ? AND evaluator_id = ?
    ORDER BY submitted_at ASC
");
$res->bind_param("ii", $assignment_id, $evaluator_id);
$res->execute();
$resResult = $res->get_result();
while ($row = $resResult->fetch_assoc()) {
    $results[] = $row;
}
$res->close();

// Fetch form details grouped by category
$formId = intval($assignment['form_id']);
$categories = [];
$catStmt = $conn->prepare("SELECT id, category_name, category_percentage FROM evaluation_categories WHERE form_id = ? ORDER BY id ASC");
$catStmt->bind_param("i", $formId);
$catStmt->execute();
$catResult = $catStmt->get_result();
while ($cat = $catResult->fetch_assoc()) {
    $categories[$cat['id']] = [
        'category_name' => $cat['category_name'],
        'percentage' => $cat['category_percentage'],
        'details' => []
    ];
}
$catStmt->close();

$detailStmt = $conn->prepare("SELECT category_id, output_name, success_indicator, accomplishment FROM evaluation_form_details WHERE form_id = ? ORDER BY id ASC");
$detailStmt->bind_param("i", $formId);
$detailStmt->execute();
$detailResult = $detailStmt->get_result();
while ($detail = $detailResult->fetch_assoc()) {
    $cat_id = $detail['category_id'];
    if (isset($categories[$cat_id])) {
        $categories[$cat_id]['details'][] = $detail;
    }
}
$detailStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Evaluation Overview</title>
<link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="container mt-4">
    <h3>Evaluation Overview (Read-Only)</h3>
    <div class="mb-3">
        <strong>Evaluatee:</strong> <?= htmlspecialchars($assignment['evaluatee_name']) ?><br>
        <strong>Department:</strong> <?= htmlspecialchars($assignment['department_name'] ?? 'N/A') ?>
    </div>

    <?php if (empty($results)): ?>
        <div class="alert alert-warning">No evaluation results found for this assignment.</div>
    <?php else: ?>
        <?php foreach ($results as $resRow): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Evaluator ID: <?= htmlspecialchars($evaluator_id) ?></strong>
                    <span class="float-end">Submitted at: <?= htmlspecialchars($resRow['submitted_at']) ?></span>
                </div>
                <div class="card-body">
                    <h5>Scores</h5>
                    <p>
                        <strong>Quality (Q):</strong> <?= htmlspecialchars($resRow['q']) ?>,
                        <strong>Efficiency (E):</strong> <?= htmlspecialchars($resRow['e']) ?>,
                        <strong>Timeliness (T):</strong> <?= htmlspecialchars($resRow['t']) ?>,
                        <strong>Average (A):</strong> <?= htmlspecialchars($resRow['a']) ?>
                    </p>

                    <h5>Form Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Output</th>
                                    <th>Success Indicator</th>
                                    <th>Actual Accomplishment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <?php foreach ($cat['details'] as $detail): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['category_name']) ?></td>
                                            <td><?= htmlspecialchars($detail['output_name']) ?></td>
                                            <td><?= htmlspecialchars($detail['success_indicator']) ?></td>
                                            <td><?= htmlspecialchars($detail['accomplishment']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
