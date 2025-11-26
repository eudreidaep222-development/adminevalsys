<?php
require_once '../../1database_conn/connection.php';
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'faculty') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

$faculty_id = $_SESSION['user_id'];
$academic_id = $_GET['academic_id'] ?? null;

if(!$academic_id){
    echo json_encode(['success'=>false, 'forms'=>[]]);
    exit;
}

// Load only assigned forms
$stmt = $conn->prepare("
    SELECT ef.id, ef.form_name
    FROM evaluation_forms ef
    INNER JOIN evaluation_assignments ea ON ea.form_id = ef.id
    WHERE ea.evaluator_id = ?
      AND ea.academic_id = ?
      AND ef.is_active = 1
");
$stmt->bind_param("ii", $faculty_id, $academic_id);
$stmt->execute();
$res = $stmt->get_result();

$forms = [];
while($row = $res->fetch_assoc()){
    $forms[] = $row;
}

echo json_encode(['success'=>true, 'forms'=>$forms]);
