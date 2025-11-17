<?php
require_once '../../1database_conn/connection.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

$dept_id      = $data['department_id'] ?? null;
$evaluator_id = $data['admin_id'] ?? null;
$evaluatees   = $data['ratees'] ?? [];
$form_id      = $data['form_id'] ?? null;
$academic_id  = $data['academic_id'] ?? null;

if (!$dept_id || !$evaluator_id || empty($evaluatees) || !$form_id || !$academic_id) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO evaluation_assignments (department_id, evaluator_id, evaluatee_id, form_id, academic_id) 
                            VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    foreach ($evaluatees as $evaluatee_id) {
        $stmt->bind_param("iiiii", $dept_id, $evaluator_id, $evaluatee_id, $form_id, $academic_id);
        $stmt->execute();
    }

    echo json_encode(["success" => true, "message" => "Assignments saved successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
