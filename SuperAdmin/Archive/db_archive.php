<?php
require_once '../../1database_conn/connection.php';

$id = $_GET['id'];

$stmt = $conn->prepare("UPDATE evaluation_archives SET is_archived = 1 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: database-backup.php?status=archived");
exit();
