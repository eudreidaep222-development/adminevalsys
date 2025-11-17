<?php
// add-department.php
session_start();
header('Content-Type: application/json');

// adjust path if your connection file is elsewhere
require_once '../../1database_conn/connection.php';

// Basic checks
$dept = trim($_POST['department_name'] ?? '');
$csrf = $_POST['csrf_token'] ?? '';

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

if ($dept === '') {
    echo json_encode(['success' => false, 'message' => 'Department name cannot be empty.']);
    exit;
}

// Prevent duplicates: case-insensitive
$stmt = $conn->prepare("SELECT id FROM departments WHERE LOWER(department_name) = LOWER(?) LIMIT 1");
$stmt->bind_param('s', $dept);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Department already exists.']);
    exit;
}
$stmt->close();

// Insert
$stmt = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('s', $dept);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
