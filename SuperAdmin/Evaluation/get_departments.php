<?php
require_once '../../1database_conn/connection.php';

header('Content-Type: application/json; charset=utf-8');

$query = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
$departments = $query->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'departments' => $departments]);
