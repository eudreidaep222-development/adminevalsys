<?php
include '../../1database_conn/connection.php';

$action = $_POST['action'] ?? $_GET['action'] ?? null;

if ($action === 'save_academic') {
    extract($_POST);

    $school_year = $conn->real_escape_string($school_year);
    $semester = $conn->real_escape_string($semester);
    $start_date = $conn->real_escape_string($start_date);
    $end_date = $conn->real_escape_string($end_date);
    $status = isset($status) ? $conn->real_escape_string($status) : null;

    $data = " school_year = '$school_year'";
    $data .= ", semester = '$semester'";
    $data .= ", start_date = '$start_date'";
    $data .= ", end_date = '$end_date'";
    if ($status !== null) {
        $data .= ", status = '$status'";
    }

    if (!empty($id) && is_numeric($id)) {
        // check duplicate for update
        $chk = $conn->query("SELECT * FROM academic_list WHERE school_year = '$school_year' AND semester = '$semester' AND id != $id")->num_rows;
        if ($chk > 0) {
            echo 2; // duplicate
            exit;
        }
        $save = $conn->query("UPDATE academic_list SET $data WHERE id = $id");
    } else {
        // check duplicate for insert
        $chk = $conn->query("SELECT * FROM academic_list WHERE school_year = '$school_year' AND semester = '$semester'")->num_rows;
        if ($chk > 0) {
            echo 2; // duplicate
            exit;
        }
        $save = $conn->query("INSERT INTO academic_list SET $data");
    }

    if ($save) {
        echo 1;
    } else {
        echo "DB Error: " . $conn->error;
    }
    exit;
}

if ($action === 'delete_academic') {
    $id = $_POST['id'];
    if (is_numeric($id)) {
        $del = $conn->query("DELETE FROM academic_list WHERE id = $id");
        if ($del) echo 1;
    }
    exit;
}

if ($action === 'make_default') {
    $id = $_POST['id'];
    if (is_numeric($id)) {
        $conn->query("UPDATE academic_list SET is_default = 0");
        $set = $conn->query("UPDATE academic_list SET is_default = 1 WHERE id = $id");
        if ($set) echo 1;
    }
    exit;
}

if ($action === 'get_academic') {
    $id = $_GET['id'];
    if (is_numeric($id)) {
        $qry = $conn->query("SELECT * FROM academic_list WHERE id = $id");
        if ($qry && $qry->num_rows > 0) {
            echo json_encode($qry->fetch_assoc());
        }
    }
    exit;
}
?>
