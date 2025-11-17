<?php
require_once '../../1database_conn/connection.php';

header('Content-Type: application/json; charset=utf-8');

$dept_id = $_GET['dept_id'] ?? null;

if (!$dept_id) {
    echo json_encode(['success' => false, 'message' => 'No department selected.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, fullName, email FROM users WHERE department_id = ? AND can_be_evaluated = 1 AND status = 'Active'");
$stmt->bind_param("i", $dept_id);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'users' => $users]);
