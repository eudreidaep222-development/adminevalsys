<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php");
    exit();
}

include '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

// CSRF Token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Semester Archives</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
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
                <h3 class="page-title">Semester Archives</h3>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table datatable" id="semesterTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Year</th>
                                <th>Semester</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>System Default</th>
                                <th>Evaluation Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $i=1;
                        $qry = $conn->query("SELECT * FROM academic_list WHERE is_archived=1 ORDER BY school_year DESC, semester DESC");
                        while($row=$qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= $row['school_year'] ?></td>
                            <td><?= $row['semester'] ?></td>
                            <td><?= date("M d, Y", strtotime($row['start_date'])) ?></td>
                            <td><?= date("M d, Y", strtotime($row['end_date'])) ?></td>
                            <td><button class="btn btn-secondary btn-sm" disabled>No</button></td>
                            <td><span class="badge bg-secondary">Archived</span></td>
                            <td class="text-end">
                                <button class="btn btn-success btn-sm restore_semester" data-id="<?= $row['id'] ?>">Restore</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="archive_restore_modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="archive_restore_title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="archive_restore_form" action="semester.php">
                <div class="modal-body text-center" id="archive_restore_message"></div>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" id="archiveRestoreAction">
                <input type="hidden" name="id" id="archiveRestoreId">
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="archive_restore_confirm" class="btn btn-success">Restore</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/datatables/datatables.min.js"></script>
<script>
$(document).ready(function() {
    $('#semesterTable').DataTable();

    $('.restore_semester').click(function(){
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const schoolYear = row.find('td:nth-child(2)').text();
        const semester = row.find('td:nth-child(3)').text();
        openArchiveRestoreSemester('restore_semester', id, schoolYear, semester);
    });

    function openArchiveRestoreSemester(action, id, schoolYear, semester){
        const modal = new bootstrap.Modal(document.getElementById('archive_restore_modal'));
        const title = document.getElementById('archive_restore_title');
        const message = document.getElementById('archive_restore_message');
        const confirmBtn = document.getElementById('archive_restore_confirm');
        const actionInput = document.getElementById('archiveRestoreAction');
        const idInput = document.getElementById('archiveRestoreId');

        idInput.value = id;
        actionInput.value = action;

        if(action == 'restore_semester'){
            title.textContent = "Restore Semester";
            message.innerHTML = `Do you want to restore <strong>${semester}</strong> (${schoolYear})?`;
            confirmBtn.textContent = "Restore";
            confirmBtn.classList.remove("btn-warning");
            confirmBtn.classList.add("btn-success");
        }
        modal.show();
    }
});
</script>
</body>
</html>
