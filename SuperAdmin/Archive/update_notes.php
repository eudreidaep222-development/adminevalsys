<?php
session_start();
require_once '../../1database_conn/connection.php';

if (!isset($_POST['id']) || !isset($_POST['notes'])) {
    die("Invalid request.");
}

$id = intval($_POST['id']);
$notes = trim($_POST['notes']);

$stmt = $conn->prepare("UPDATE evaluation_archives SET notes = ? WHERE id = ?");
$stmt->bind_param("si", $notes, $id);
$stmt->execute();

header("Location: database-backup.php?note=updated");
exit();
