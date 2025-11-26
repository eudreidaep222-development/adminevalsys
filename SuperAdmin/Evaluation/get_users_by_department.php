<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../1database_conn/connection.php';

header('Content-Type: application/json; charset=utf-8');

$department_id = $_GET['department_id'] ?? null;

if (!$department_id) {
    echo json_encode(['success' => false, 'message' => 'No department selected.']);
    exit;
}

// Fetch individual name columns
$stmt = $conn->prepare("
    SELECT id, first_name, middle_name, last_name, email 
    FROM users 
    WHERE department_id = ? 
      AND can_be_evaluated = 1 
      AND status = 'Active'
");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    // Build full name dynamically
    $fullName = $row['first_name'];
    if (!empty($row['middle_name'])) {
        $fullName .= ' ' . $row['middle_name'];
    }
    $fullName .= ' ' . $row['last_name'];

    $users[] = [
        'id' => $row['id'],
        'fullName' => $fullName,
        'email' => $row['email']
    ];
}

echo json_encode(['success' => true, 'users' => $users]);
