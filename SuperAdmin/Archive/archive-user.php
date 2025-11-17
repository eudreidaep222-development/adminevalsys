<?php
session_start();
require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// CSRF check (expects csrf_token in POST)
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(["status" => "error", "message" => "Invalid CSRF token."]);
    exit;
}

// accept either key name to be robust
$user_id = 0;
if (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
} elseif (isset($_POST['id'])) {
    $user_id = intval($_POST['id']);
}

$action = $_POST['action'] ?? '';

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid user ID."]);
    exit;
}

if ($action === "archive_user") {
    $stmt = $conn->prepare("UPDATE users SET archived = 1, status = 'Inactive' WHERE id = ?");
    $new_status = "Archived";
    $button_html = '
    <button class="btn btn-success restore-btn" data-id="'.$user_id.'" data-action="restore_user" data-name="">Restore</button>
    <button class="btn btn-danger delete-btn" data-id="'.$user_id.'" data-action="delete_user" data-name="">Delete</button>
    ';
    $log_action = 'archive';
} elseif ($action === "restore_user") {
    $stmt = $conn->prepare("UPDATE users SET archived = 0, status = 'Active' WHERE id = ?");
    $new_status = "Active";
    $button_html = '<button class="btn btn-warning archive-btn" data-id="'.$user_id.'" data-action="archive_user" data-name="">Archive</button>';
    $log_action = 'restore';
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
    exit;
}

$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // log it — standardize session ID usage (try both keys)
    $actor_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
    log_action(
        $conn,
        $actor_id,
        $log_action,
        $user_id,
        null,
        ($log_action === 'archive' ? "User archived (id {$user_id})" : "User restored (id {$user_id})")
    );

    echo json_encode([
        "status" => "success",
        "message" => "User successfully ". str_replace("_", " ", $action) .".",
        "new_status" => $new_status,
        "new_button_html" => $button_html
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Database update failed."]);
}

$stmt->close();
$conn->close();
