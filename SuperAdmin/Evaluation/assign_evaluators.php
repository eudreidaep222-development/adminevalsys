<?php
require_once '../../1database_conn/connection.php';
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents("php://input"), true);

// MATCHING KEYS FROM JS
$evaluatee_id  = $data['evaluatee_id'] ?? null;
$evaluator_ids = $data['evaluator_ids'] ?? []; 
$form_id       = $data['form_id'] ?? null;
$academic_id   = $data['academic_id'] ?? null;

// Validation
if (!$evaluatee_id || empty($evaluator_ids) || !$form_id || !$academic_id) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing fields. Received: EvalID: $evaluatee_id, FormID: $form_id, AcadID: $academic_id"
    ]);
    exit;
}

try {
    // Get Department
    $deptStmt = $conn->prepare("SELECT department_id FROM users WHERE id = ?");
    $deptStmt->bind_param("i", $evaluatee_id);
    $deptStmt->execute();
    $res = $deptStmt->get_result();
    
    if ($res->num_rows === 0) throw new Exception("Evaluatee not found.");
    $row = $res->fetch_assoc();
    $department_id = $row['department_id'];
    $deptStmt->close();

    // Insert
    $stmt = $conn->prepare("INSERT INTO evaluation_assignments (department_id, evaluator_id, evaluatee_id, form_id, academic_id) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($evaluator_ids as $evaluator_id) {
        $stmt->bind_param("iiiii", $department_id, $evaluator_id, $evaluatee_id, $form_id, $academic_id);
        $stmt->execute();
    }

    echo json_encode(["success" => true, "message" => "Assignments saved successfully."]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>