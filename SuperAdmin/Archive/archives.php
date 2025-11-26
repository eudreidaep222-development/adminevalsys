<?php
session_start();
require_once '../../1database_conn/connection.php';

if (!isset($_SESSION['role']) || trim($_SESSION['role']) !== 'Super Admin') {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Archives & Restoration</title>
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body>
<div class="main-wrapper">
    <?php include '../../Layout Components/header.php'; ?>
    <?php include '../../Layout Components/sidebar.php'; ?>

    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col">
                        <h3 class="page-title">System Archives</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Archives</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Archived Semesters</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-center">
                                    <thead>
                                        <tr>
                                            <th>School Year</th>
                                            <th>Semester</th>
                                            <th>Date Archived</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Fetch ONLY archived semesters
                                        $sql = "SELECT * FROM academic_list WHERE is_archived = 1 ORDER BY id DESC";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0):
                                            while ($row = $result->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['school_year']) ?></td>
                                            <td><?= htmlspecialchars($row['semester']) ?></td>
                                            <td><?= date('M d, Y', strtotime($row['updated_at'] ?? $row['created_at'])) ?></td> <td class="text-end">
                                                <a href="../reports/reports.php?school_year=<?= $row['school_year'] ?>&semester=<?= $row['semester'] ?>" class="btn btn-sm btn-info me-2" target="_blank">
                                                    <i class="fas fa-eye"></i> View Data
                                                </a>

                                                <button class="btn btn-sm btn-warning" onclick="restoreSemester(<?= $row['id'] ?>)">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center">No archives found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
function restoreSemester(id) {
    if(confirm('Are you sure you want to restore this semester? It will reappear in the main dashboard.')) {
        $.post('../../1database_conn/archive_handler.php', { // Adjust path to handler
            action: 'restore',
            id: id
        }, function(res) {
            if(res.success) {
                toastr.success(res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(res.message);
            }
        }, 'json');
    }
}
</script>
</body>
</html>