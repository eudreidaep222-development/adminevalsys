<?php
// archive_handler.php
session_start();
require_once '../1database_conn/connection.php'; // Adjust path as needed

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

if ($action === 'archive') {
    // 1. ARCHIVE: Set flag to 1 and ensure it's not active
    $stmt = $conn->prepare("UPDATE academic_list SET is_archived = 1, is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Semester successfully archived.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }

} elseif ($action === 'restore') {
    // 2. RESTORE: Set flag back to 0
    // Note: We do NOT set is_active = 1 automatically to prevent conflicts with the current active semester.
    $stmt = $conn->prepare("UPDATE academic_list SET is_archived = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Semester restored to the main list.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>