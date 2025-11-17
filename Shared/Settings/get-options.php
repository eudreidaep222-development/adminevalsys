<?php
require_once "../../1database_conn/connection.php";

// Fetch domains
$domains = $conn->query("SELECT id, domain_name FROM domains ORDER BY domain_name ASC");

// Fetch departments
$departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");

// Fetch positions (they are linked to domains)
$positions = $conn->query("SELECT id, position_name, domain_id FROM positions ORDER BY position_name ASC");

$data = [
    "domains" => $domains->fetch_all(MYSQLI_ASSOC),
    "departments" => $departments->fetch_all(MYSQLI_ASSOC),
    "positions" => $positions->fetch_all(MYSQLI_ASSOC)
];

header("Content-Type: application/json");
echo json_encode($data);
exit;
