<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

// Database connection
require_once '../../1database_conn/connection.php';

// Define credentials for exec command
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "adminevalsys";

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $result = $conn->query("SELECT file_name FROM evaluation_archives WHERE id=$id LIMIT 1");
    if($row = $result->fetch_assoc()) {
        $file = "../../backups/" . $row['file_name'];

        // Make sure file exists
        if(file_exists($file)) {
            // Windows: adjust path to mysql.exe if needed
            $mysql = '"C:\\xampp\\mysql\\bin\\mysql.exe"';
            $command = "{$mysql} --user={$user} --password={$pass} --host={$host} {$dbname} < \"{$file}\"";
            
            exec($command, $output, $returnVar);

            if($returnVar === 0) {
                header("Location: database-backup.php?msg=restore_success");
                exit();
            } else {
                header("Location: database-backup.php?msg=restore_failed");
                exit();
            }
        } else {
            header("Location: database-backup.php?msg=file_missing");
            exit();
        }
    } else {
        header("Location: database-backup.php?msg=invalid_id");
        exit();
    }
}
?>
