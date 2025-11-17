<?php
require_once '../../1database_conn/connection.php';

header('Content-Type: application/json');


if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT id, fullName, email, role, status FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'User not found']);
} else {
    echo json_encode($result->fetch_assoc());
}
