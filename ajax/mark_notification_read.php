<?php
session_start();
include '../../1database_conn/connection.php';

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

echo json_encode(['success' => true]);
