<?php
require_once "../../1database_conn/connection.php";
header('Content-Type: application/json; charset=utf-8');

$academic_id = $_GET['academic_id'] ?? null;

if (!$academic_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, form_name 
        FROM evaluation_forms 
        WHERE academic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $academic_id);
$stmt->execute();
$result = $stmt->get_result();

$forms = [];
while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}

echo json_encode($forms);
