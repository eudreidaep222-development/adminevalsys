<?php
require_once "../../1database_conn/connection.php";
header('Content-Type: application/json; charset=utf-8');

// Get academic_id from GET request
$academic_id = isset($_GET['academic_id']) ? intval($_GET['academic_id']) : null;

if (!$academic_id) {
    // Return empty array if no academic_id
    echo json_encode([]);
    exit;
}

// Prepare SQL to get forms for the given academic_id
$sql = "SELECT id, form_name 
        FROM evaluation_forms 
        WHERE academic_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // In case of prepare error
    echo json_encode([]);
    exit;
}

$stmt->bind_param("i", $academic_id);
$stmt->execute();
$result = $stmt->get_result();

$forms = [];
while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}

// Return JSON array of forms
echo json_encode($forms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
