<?php
session_start();
require_once '../../1database_conn/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$assignment_id = intval($_POST['assignment_id']);
$ids = $_POST['criteria_id'] ?? [];
$outputs = $_POST['output'] ?? [];
$indicators = $_POST['success_indicator'] ?? [];
$accomplishments = $_POST['accomplishment'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'No data to save.']);
    exit;
}

$conn->begin_transaction();

try {
    // 1. Update each criteria row
    $stmt = $conn->prepare("UPDATE evaluation_form_details SET output_name = ?, success_indicator = ?, accomplishment = ? WHERE id = ?");

    for ($i = 0; $i < count($ids); $i++) {
        $id = intval($ids[$i]);
        $out = $outputs[$i] ?? '';
        $ind = $indicators[$i] ?? '';
        $acc = $accomplishments[$i] ?? '';

        $stmt->bind_param("sssi", $out, $ind, $acc, $id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update row ID: $id");
        }
    }
    $stmt->close();

    // 2. Update Assignment Status (e.g., to 'reviewed' or 'submitted')
    // This flags it for the Super Admin to see it's ready for distribution/approval
    $statusStmt = $conn->prepare("UPDATE evaluation_assignments SET status = 'submitted_for_approval', date_completed = NOW() WHERE id = ?");
    $statusStmt->bind_param("i", $assignment_id);
    $statusStmt->execute();
    $statusStmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Evaluation updated and sent for approval.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>