<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

require_once '../../1database_conn/connection.php';

// Check for session messages (for displaying feedback)
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);


if (isset($_POST['archive_semester'])) {
    $department = $_POST['department'] ?? null;
    $school_year = $_POST['school_year'] ?? null;
    $semester = $_POST['semester'] ?? null;

    if (!$school_year || !$semester || $school_year === 'Choose...' || $semester === 'Choose...') {
        $_SESSION['error'] = "Please select both School Year and Semester before archiving.";
    } else {
        $deptCondition = $department && $department !== "Choose..." ? " AND er.department_id='$department'" : "";
        
        // --- ARCHIVING QUERY (CLEANED) ---
        // Ensure SQL is on one line and use CONCAT to get the full name
        $sql = "INSERT INTO evaluation_archives (administrator_id, administrator_name, department_id, school_year, semester, avg_score, date_archived) 
                SELECT er.administrator_id, CONCAT(u.first_name, ' ', IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(SUBSTRING(u.middle_name, 1, 1), '. '), ''), u.last_name), 
                er.department_id, er.school_year, er.semester, er.avg_score, NOW() 
                FROM evaluation_archives er 
                LEFT JOIN users u ON er.administrator_id = u.id 
                WHERE er.school_year='$school_year' AND er.semester='$semester' $deptCondition";

        if ($conn->query(trim($sql))) {
            $_SESSION['success'] = "Semester evaluations archived successfully.";
        } else {
            $_SESSION['error'] = "Archiving failed: " . $conn->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']); // PRG Pattern
    exit();
}

$departments = [];
$deptResult = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
if ($deptResult) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

$schoolYears = [];
$syResult = $conn->query("SELECT DISTINCT school_year FROM academic_list ORDER BY school_year DESC");
if ($syResult) {
    while ($row = $syResult->fetch_assoc()) {
        $schoolYears[] = $row['school_year'];
    }
}

$semesters = [];
$semResult = $conn->query("SELECT DISTINCT semester FROM academic_list ORDER BY id ASC");
if ($semResult) {
    while ($row = $semResult->fetch_assoc()) {
        $semesters[] = $row['semester'];
    }
}

$archivedRecords = [];
// --- RETRIEVAL QUERY (CLEANED - FIXES LINE 70 ERROR) ---
// Define SQL query on one line to prevent hidden characters, maintaining CONCAT logic.
$sql_archive_retrieval = "SELECT ea.id, ea.administrator_id, ea.department_id, ea.school_year, ea.semester, ea.avg_score, ea.date_archived, CONCAT(u.first_name, ' ', IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(SUBSTRING(u.middle_name, 1, 1), '. '), ''), u.last_name) AS admin_name, d.department_name FROM evaluation_archives ea LEFT JOIN users u ON ea.administrator_id = u.id LEFT JOIN departments d ON ea.department_id = d.id ORDER BY ea.date_archived DESC";

$archiveResult = $conn->query(trim($sql_archive_retrieval)); 

if ($archiveResult) {
    while ($row = $archiveResult->fetch_assoc()) {
        $archivedRecords[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Archive</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<div class="main-wrapper">
    <?php
    include '../../Layout Components/header.php';
    include '../../Layout Components/sidebar.php';
    ?>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Archiving & Retrieval Module</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Archiving</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Archiving and Retrieval Module</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                    </p>

                    <form method="post" class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select">
                                <option selected value="Choose...">Choose...</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['id']) ?>">
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">School Year</label>
                            <select name="school_year" class="form-select">
                                <option selected value="Choose...">Choose...</option>
                                <?php foreach($schoolYears as $sy): ?>
                                    <option value="<?= htmlspecialchars($sy) ?>"><?= htmlspecialchars($sy) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select">
                                <option selected value="Choose...">Choose...</option>
                                <?php foreach($semesters as $sem): ?>
                                    <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="archive_semester" class="btn btn-primary">Archive Semester</button>
                        </div>
                    </form>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Administrator</th>
                                <th>Department</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Avg. Score</th>
                                <th>Date Archived</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(count($archivedRecords) > 0): ?>
                            <?php foreach($archivedRecords as $index => $record): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($record['admin_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($record['department_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($record['school_year']) ?></td>
                                    <td><?= htmlspecialchars($record['semester']) ?></td>
                                    <td><?= htmlspecialchars($record['avg_score']) ?></td>
                                    <td><?= htmlspecialchars($record['date_archived']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No archived records found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <?php include '../../Layout Components/footer.php'; ?>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/feather.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/apexchart/apexcharts.min.js"></script>
<script src="../../assets/plugins/apexchart/chart-data.js"></script>
<script src="../../assets/plugins/simple-calendar/jquery.simple-calendar.js"></script>
<script src="../../assets/js/calander.js"></script>
<script src="../../assets/js/circle-progress.min.js"></script>
<script src="../../assets/js/script.js"></script>

</body>

</html>