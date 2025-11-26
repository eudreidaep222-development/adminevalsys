<?php
session_start();
require_once '../../1database_conn/connection.php';

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT file_name FROM evaluation_archives WHERE id=$id LIMIT 1");
    if($row = $result->fetch_assoc()) {
        $file = "../../backups/" . $row['file_name'];
        if(file_exists($file)) unlink($file);
        $conn->query("DELETE FROM evaluation_archives WHERE id=$id");
    }
    header("Location: database-backup.php");
}
?>
