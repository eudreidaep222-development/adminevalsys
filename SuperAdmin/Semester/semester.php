<?php
// session_start();
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
//     header('Location: ../../index.php');
//     exit();
// }

include '../../1database_conn/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0" />
    <title>Semester Management</title>

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

        <?php
        include '../../Layout Components/header.php';
        include '../../Layout Components/sidebar.php';
        ?>

    <div class="page-wrapper">
        <div class="content container-fluid">

            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="page-title">Semester</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Semester</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                
                <div class="col-sm-12">
                    
                    <div class="card card-table">
                        <div class="card-header">
                            <div class="col-auto ms-auto text-end">
                                <a href="add-semester.php" class="btn btn-primary new_semester">
                                    <i class="fas fa-plus me-1"></i> Add New
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table datatable table-striped" id="semesterTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Year</th>
                                        <th>Semester</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th class="text-center">System Default</th>
                                        <th class="text-center">Evaluation Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $qry = $conn->query("SELECT * FROM academic_list ORDER BY school_year DESC, semester DESC");
                                    while($row = $qry->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo $i++ ?></td>
                                            <td><?php echo $row['school_year'] ?></td>
                                            <td><?php echo $row['semester'] ?></td>
                                            <td><?php echo date("M d, Y", strtotime($row['start_date'])) ?></td>
                                            <td><?php echo date("M d, Y", strtotime($row['end_date'])) ?></td>
                                            <td class="text-center">
                                                <?php if($row['is_default'] == 0): ?>
                                                    <button class="btn btn-secondary btn-sm make_default" data-id="<?php echo $row['id'] ?>">No</button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary btn-sm">Yes</button>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($row['status'] == 0): ?>
                                                    <span class="badge bg-secondary">Pending</span>
                                                <?php elseif($row['status'] == 1): ?>
                                                    <span class="badge bg-success">Started</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Closed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-info edit_semester" data-id="<?php echo $row['id'] ?>"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-sm btn-danger delete_semester" data-id="<?php echo $row['id'] ?>"><i class="fas fa-trash"></i></button>
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
        <?php include '../../Layout Components/footer.php'; ?>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirm_modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center"></div>
            <div class="modal-footer justify-content-center">
                <button type="button" id="confirm" class="btn btn-primary">Yes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="edit_modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-semester-form">
                    <input type="hidden" name="id" id="edit-semester-id">
                    <div class="mb-3">
                        <label for="edit-year" class="form-label">Year</label>
                        <input type="text" class="form-control" id="edit-school-year" name="school_year" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-semester" class="form-label">Semester</label>
                        <input type="number" class="form-control" id="edit-semester" name="semester" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="edit-start-date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="edit-end-date" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-status" class="form-label">Status</label>
                        <select class="form-select" id="edit-status" name="status">
                            <option value="0">Pending</option>
                            <option value="1">Started</option>
                            <option value="2">Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="../../assets/plugins/moment/moment.min.js"></script>
<script src="../../assets/js/bootstrap-datetimepicker.min.js"></script>
<script src="../../assets/js/alert-toast.js"></script>
<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/feather.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/datatables/datatables.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function() {
    $('#semesterTable').DataTable();

    // Open Add Semester Modal
    $('.new_semester').click(function() {
        uni_modal("Add New Semester", "add-semester.php");
    });

    // Edit Semester
    $('.edit_semester').click(function() {
        const semesterId = $(this).data('id');
        $.ajax({
            url: 'ajax.php',
            method: 'GET',
            data: { action: 'get_academic', id: semesterId },
            success: function(data) {
                const semesterData = JSON.parse(data);
                $('#edit-semester-id').val(semesterData.id);
                $('#edit-school-year').val(semesterData.school_year);
                $('#edit-semester').val(semesterData.semester);
                $('#edit-start-date').val(semesterData.start_date);
                $('#edit-end-date').val(semesterData.end_date);
                $('#edit-status').val(semesterData.status);
                $('#edit_modal').modal('show');
            }
        });
    });

    // Delete Semester
    $('.delete_semester').click(function() {
        _conf("Are you sure you want to delete this semester?", "delete_semester", [$(this).data('id')]);
    });

    // Make Default
    $('.make_default').click(function() {
        _conf("Make this semester the system default?", "make_default", [$(this).data('id')]);
    });

    // Edit Form Submit
    $('#edit-semester-form').submit(function(e) {
        e.preventDefault();
        start_load();
        const formData = $(this).serialize() + '&action=save_academic';
        $.ajax({
            url: 'ajax.php',
            method: 'POST',
            data: formData,
            success: function(resp) {
                if(resp == 1){
                    toastr.success("Semester updated successfully");
                    setTimeout(()=> location.reload(), 1500);
                } else if(resp == 2){
                    toastr.error("Duplicate semester detected");
                    end_load();
                } else {
                    toastr.error("Unexpected error: "+resp);
                    end_load();
                }
            },
            error: function(xhr, status, error){
                toastr.error("AJAX error: " + error);
                end_load();
            }
        });
    });
});

// Confirm modal helper
function _conf(msg, func, params = []) {
    $('#confirm_modal .modal-body').html(msg);
    $('#confirm_modal').modal('show');
    $('#confirm_modal #confirm').off().click(function(){
        $('#confirm_modal').modal('hide');
        $('#confirm_modal').one('hidden.bs.modal', function() {
            window[func](...params);
        });
    });
}

// Delete function
function delete_semester(id){
    start_load();
    $.post('ajax.php?action=delete_academic', {id: id}, function(resp){
        if(resp==1){
            toastr.success("Semester deleted successfully");
            setTimeout(()=> location.reload(),1500);
        }
    });
}

// Make default function
function make_default(id){
    start_load();
    $.post('ajax.php?action=make_default', {id: id}, function(resp){
        if(resp==1){
            toastr.success("Default semester updated");
            setTimeout(()=> location.reload(),1500);
        }
    });
}
</script>
</body>
</html>
