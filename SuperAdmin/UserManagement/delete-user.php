<?php
session_start();
require_once __DIR__ . '/../../1database_conn/connection.php';
require_once __DIR__ . '/../../helper/log_action.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(["status" => "error", "message" => "Invalid CSRF token."]);
    exit;
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid user ID."]);
    exit;
}

// Delete the user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Log the action
    $actor_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    log_action(
        $conn,
        $actor_id,
        "delete",
        $user_id,
        null,
        "User deleted (id {$user_id})"
    );

    echo json_encode([
        "status" => "success",
        "message" => "User successfully deleted.",
        "removed_row_id" => $user_id
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Database deletion failed."]);
}

$stmt->close();
$conn->close();
