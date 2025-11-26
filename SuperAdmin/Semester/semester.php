<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php");
    exit();
}

include '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

// -------- CSRF TOKEN --------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// -------- POST: Archive/Restore/Edit action --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(400);
        die('Invalid CSRF token.');
    }

    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) die('Invalid semester id.');

    // Fetch old record
    $stmt = $conn->prepare("SELECT * FROM academic_list WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$old) die('Semester not found.');

    // safe values
    $userId = $_SESSION['id'] ?? 0;
    $schoolYear = $old['school_year'] ?? '';
    $semesterName = $old['semester'] ?? '';

    switch($action){
        case 'archive_semester':
            if (isset($old['is_default']) && $old['is_default']==1) die('Cannot archive default semester.');
            $stmt = $conn->prepare("UPDATE academic_list SET is_archived=1 WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $stmt->close();

            log_action($conn, $userId, 'Archive Semester', $id, [], "Semester archived: $schoolYear - $semesterName");
            echo 'archived';
            exit;

        case 'restore_semester':
            $stmt = $conn->prepare("UPDATE academic_list SET is_archived=0 WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $stmt->close();

            log_action($conn, $userId, 'Restore Semester', $id, [], "Semester restored: $schoolYear - $semesterName");
            echo 'restored';
            exit;

        case 'edit_semester':
            $school_year = trim($_POST['school_year'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 0; // Added status

            // Validate required fields
            if (!$school_year || !$semester || !$start_date || !$end_date) {
                http_response_code(400);
                die('All fields are required.');
            }

            // Validate date order
            if (strtotime($start_date) > strtotime($end_date)) {
                http_response_code(400);
                die('Start date cannot be after end date.');
            }

            // Update query including status
            $stmt = $conn->prepare("UPDATE academic_list SET school_year=?, semester=?, start_date=?, end_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssii", $school_year, $semester, $start_date, $end_date, $status, $id);

            if ($stmt->execute()) {
                log_action($conn, $userId, 'Edit Semester', $id, [], "Semester updated: $school_year - $semester, Status: $status");
                echo 'updated';
            } else {
                echo 'Database error';
            }

            $stmt->close();
            exit;

        case 'make_default':

            // 1. Remove default + deactivate all semesters
            $conn->query("UPDATE academic_list SET 
                is_default = 0,
                is_active = 0,
                status = 2
            ");

            // 2. Set selected semester as default + active + started
            $stmt = $conn->prepare("
                UPDATE academic_list 
                SET 
                    is_default = 1,
                    is_active = 1,
                    status = 1
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            log_action(
                $conn,
                $userId,
                'Set Default Semester',
                $id,
                [],
                "Set as system default, activated, and evaluation started: $schoolYear - $semesterName"
            );

            echo 'default_updated';
            exit;


        default:
            http_response_code(400);
            echo 'Invalid action';
            exit;
    }
}

// Handle edit semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_semester') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(400);
        die('Invalid CSRF token.');
    }

    $id = (int)($_POST['id'] ?? 0);
    $school_year = trim($_POST['school_year'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if ($id <= 0 || !$school_year || !$semester || !$start_date || !$end_date) {
        http_response_code(400);
        die('All fields are required.');
    }

    if (strtotime($start_date) > strtotime($end_date)) {
        http_response_code(400);
        die('Start date cannot be after end date.');
    }

    $stmt = $conn->prepare("UPDATE academic_list SET school_year=?, semester=?, start_date=?, end_date=? WHERE id=?");
    $stmt->bind_param("ssssi",$school_year,$semester,$start_date,$end_date,$id);
    if($stmt->execute()){
        log_action($conn,$_SESSION['id'],'Edit Semester',$id,[],"Semester updated: $school_year - $semester");
        echo 'updated';
    } else {
        echo 'Database error';
    }
    $stmt->close();
    exit;
}

// -------- FILTERS & PAGINATION --------
$search = $_GET['search'] ?? '';
$archive_filter = $_GET['archive_filter'] ?? '';
$page = max((int)($_GET['page'] ?? 1),1);
$limit = 10;
$offset = ($page-1)*$limit;

// Build WHERE clause
$where = [];
$params = [];
$types = '';

if($search){
    $where[] = "(school_year LIKE ? OR semester LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if($archive_filter==='archived'){
    $where[] = "is_archived=1";
} elseif($archive_filter===''){
    $where[] = "is_archived=0";
}

$where_sql = $where ? "WHERE ".implode(' AND ',$where) : "";

// Get total
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM academic_list $where_sql");
if($params){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users/$limit);
$stmt->close();

// Fetch semesters
$stmt = $conn->prepare("SELECT * FROM academic_list $where_sql ORDER BY school_year DESC, semester DESC LIMIT ?, ?");

// merge params
$all_params = $params ?: [];
$all_types = $types ?: '';
$all_params[] = $offset;
$all_params[] = $limit;
$all_types .= 'ii';

// bind dynamically
$stmt->bind_param($all_types, ...$all_params);

$stmt->execute();
$semesters = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Semesters</title>

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
                        <h3 class="page-title">Semesters</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item active">Semesters</li>
                        </ul>
                    </div>
                    <div class="col-auto">
                        <form class="row g-2" method="get">
                            <div class="col-auto">
                                <input type="text" class="form-control" name="search" placeholder="Search year or semester" value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="archive_filter">
                                    <option value="">Active Semesters</option>
                                    <option value="archived" <?= $archive_filter==='archived' ? 'selected':'' ?>>Archived Semesters</option>
                                    <option value="all" <?= $archive_filter==='all' ? 'selected':'' ?>>All Semesters</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Filter</button>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Clear</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card card-table comman-shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover align-middle" id="semesterTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th class="text-center">System Default</th>
                                    <th class="text-center">Evaluation Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = $offset + 1;
                                if($semesters && $semesters->num_rows>0):
                                    while($row = $semesters->fetch_assoc()):
                                ?>
                                    <tr class="<?= $row['is_archived'] ? 'table-secondary' : '' ?>" id="semesterRow<?= $row['id'] ?>">
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($row['school_year']) ?></td>
                                        <td><?= htmlspecialchars($row['semester']) ?></td>
                                        <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                                        <td>
                                            <?php if ($row['is_archived']): ?>
                                                <span class="badge bg-dark">Archived</span>

                                            <?php elseif ($row['is_active'] == 1): ?>
                                                <span class="badge bg-success">Active</span>

                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($row['is_default'] == 1): ?>
                                                <button class="btn btn-primary btn-sm"><i class="fas fa-cog me-2"></i>Yes</button>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm make_default" data-id="<?= $row['id'] ?>"><i class="fas fa-cog me-2"></i>No</button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            if($row['status'] == 0):
                                                echo '<span class="badge bg-warning">Pending</span>';
                                            elseif($row['status'] == 1):
                                                echo '<span class="badge bg-success">Started</span>';
                                            else:
                                                echo '<span class="badge bg-secondary">Closed</span>';
                                            endif;
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($row['is_archived']): ?>
                                                <button class="btn btn-sm btn-success restore_semester" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-undo"></i> Restore</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-primary edit_semester" 
                                                        data-id="<?= $row['id'] ?>"
                                                        data-year="<?= htmlspecialchars($row['school_year']) ?>"
                                                        data-semester="<?= htmlspecialchars($row['semester']) ?>"
                                                        data-start="<?= $row['start_date'] ?>"
                                                        data-end="<?= $row['end_date'] ?>"><i class="fas fa-pencil-alt me-2"></i>
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-warning archive_semester" data-id="<?= $row['id'] ?>">
                                                    <i class="fas fa-archive me-2"></i>Archive
                                                </button>                                            
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No semesters found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if($total_pages>1): ?>
                        <nav>
                            <ul class="pagination justify-content-end">
                                <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>max($page-1,1)])) ?>">Previous</a>
                                </li>
                                <?php for($p=1;$p<=$total_pages;$p++): ?>
                                    <li class="page-item <?= $p==$page?'active':'' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>min($page+1,$total_pages)])) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Archive/Restore Modal -->
<div class="modal fade" id="archive_restore_modal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="archive_restore_title">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="archive_restore_message"></div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="archive_restore_confirm" class="btn btn-warning">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Semester Modal -->
<div class="modal fade" id="edit_semester_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSemesterForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_semester_id">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <!-- School Year Dropdown -->
                    <div class="mb-2 dropdown-animated">
                        <label>School Year</label>
                        <div class="select-wrapper">
                            <select name="school_year" id="edit_school_year" class="form-control" required>
                                <option value="" disabled selected>-- Select Academic Period --</option>
                                <?php
                                $years = [];
                                $res = $conn->query("SELECT DISTINCT school_year FROM academic_list");
                                if ($res && $res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        $years[] = trim($row['school_year']);
                                    }
                                }
                                $startYears = [];
                                foreach ($years as $yr) {
                                    if (preg_match('/^(\d{4})-(\d{4})$/', $yr, $m)) {
                                        $startYears[] = (int)$m[1];
                                    }
                                }
                                $minYear = empty($startYears) ? date("Y") : min($startYears);
                                $maxYear = empty($startYears) ? date("Y") : max($startYears);
                                $allYears = [];
                                for ($y=$minYear; $y <= $maxYear+5; $y++) {
                                    $allYears[] = $y . '-' . ($y+1);
                                }
                                $allYears = array_unique(array_merge($years, $allYears));
                                usort($allYears, function($a,$b){
                                    preg_match('/^(\d{4})/',$a,$ma);
                                    preg_match('/^(\d{4})/',$b,$mb);
                                    return ((int)$ma[1]) <=> ((int)$mb[1]);
                                });
                                foreach($allYears as $yr){
                                    echo "<option value=\"$yr\">$yr</option>";
                                }
                                ?>
                            </select>
                            <span class="arrow">&#9662;</span>
                        </div>
                    </div>

                    <!-- Semester Dropdown -->
                    <div class="mb-2 dropdown-animated">
                        <label>Semester</label>
                        <div class="select-wrapper">
                            <select name="semester" id="edit_semester" class="form-control" required>
                                <option value="" disabled selected>-- Select Semester --</option>
                                <?php for($s=1;$s<=4;$s++): ?>
                                    <option value="<?= $s ?>"><?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="arrow">&#9662;</span>
                        </div>
                    </div>

                    <!-- Start/End Dates -->
                    <div class="mb-2">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                    </div>

                    <!-- Evaluation Status -->
                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="0">Pending</option>
                            <option value="1">Started</option>
                            <option value="2">Closed</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
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



<!-- jQuery first -->
<script src="../../assets/js/jquery-3.6.0.min.js"></script>

<!-- Moment.js (for datetimepicker) -->
<script src="../../assets/plugins/moment/moment.min.js"></script>

<!-- Bootstrap JS bundle (includes Popper) -->
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap Datetimepicker (depends on jQuery & Moment) -->
<script src="../../assets/js/bootstrap-datetimepicker.min.js"></script>

<!-- Optional plugins -->
<script src="../../assets/js/feather.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/datatables/datatables.min.js"></script>

<!-- Toastr for notifications -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Your custom script -->
<script src="../../assets/js/script.js"></script>
<script src="https://kit.fontawesome.com/YOUR_KIT_CODE.js" crossorigin="anonymous"></script>

<script>
$(document).ready(function(){

    // Initialize DataTable
    $('#semesterTable').DataTable({
        searching: false
    }); 

// Helper function: Open Archive/Restore Modal
function openModal(action, id, semester, year){
    const modal = new bootstrap.Modal($('#archive_restore_modal'));
    const title = $('#archive_restore_title');
    const message = $('#archive_restore_message');
    const confirmBtn = $('#archive_restore_confirm');

    // Colored labels and values
    const schoolYearLabel = '<span style="color:#008000;"><strong>School Year</strong></span>';
    const semesterLabel = '<span style="color:#008000;"><strong>Semester</strong></span>';
    const schoolYearValue = `<span style="color:#ffdb19;"><strong>${year}</strong></span>`;
    const semesterValue = `<span style="color:#ffdb19;"><strong>${semester}</strong></span>`;

    if(action === 'archive_semester'){
        title.text('Archive Semester');
        message.html(`Are you sure you want to archive the ${schoolYearLabel} ${schoolYearValue}, ${semesterLabel} ${semesterValue}? This will hide it from the dashboard but can be restored later.`);
        confirmBtn.removeClass('btn-success').addClass('btn-warning').text('Archive');
    } else {
        title.text('Restore Semester');
        message.html(`Do you want to restore the ${schoolYearLabel} ${schoolYearValue}, ${semesterLabel} ${semesterValue}?`);
        confirmBtn.removeClass('btn-warning').addClass('btn-success').text('Restore');
    }

    confirmBtn.off('click').on('click', function(){
        $.post('semester.php', {
            id: id,
            action: action,
            csrf_token: '<?= $csrf_token ?>'
        }, function(resp){
            resp = resp.trim();
            if(resp === 'archived' || resp === 'restored'){
                toastr.success(`Semester ${resp} successfully`, 'Success');
                // Instead of removing the row, reload the page like make_default
                setTimeout(()=> location.reload(), 1000);
            } else {
                toastr.error('Action failed: ' + resp,'Error');
            }
            modal.hide();
        }).fail(function(xhr){
            toastr.error('AJAX Error: ' + xhr.responseText,'Error');
        });
    });

    modal.show();
}

    // Open Edit Modal (delegated)
    $(document).on('click', '.edit_semester', function(){
        const btn = $(this);
        $('#edit_semester_id').val(btn.data('id'));
        $('#edit_school_year').val(btn.data('year'));
        $('#edit_semester').val(btn.data('semester'));
        $('#edit_start_date').val(btn.data('start'));
        $('#edit_end_date').val(btn.data('end'));

        // Show Edit Modal
        new bootstrap.Modal($('#edit_semester_modal')).show();
    });

    // Edit form submit
    $(document).on('submit', '#editSemesterForm', function(e){
        e.preventDefault();

        const start = $('#edit_start_date').val();
        const end = $('#edit_end_date').val();
        if(start && end && moment(end).isSameOrBefore(moment(start))){
            toastr.error("End date must be greater than Start date","Invalid Dates");
            return;
        }

        const formData = $(this).serialize() + '&action=edit_semester';
        $.post('semester.php', formData, function(resp){
            resp = resp.trim();
            if(resp === 'updated'){
                toastr.success('Semester updated successfully','Success');
                setTimeout(()=> location.reload(), 1000);
            } else {
                toastr.error('Update failed: ' + resp,'Error');
            }
        }).fail(function(xhr){
            toastr.error('AJAX Error: ' + xhr.responseText,'Error');
        });
    });

    // Archive button
    $(document).on('click', '.archive_semester', function(){
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const semester = row.find('td:nth-child(3)').text();
        const year = row.find('td:nth-child(2)').text();
        openModal('archive_semester', id, semester, year);
    });

    // Restore button
    $(document).on('click', '.restore_semester', function(){
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const semester = row.find('td:nth-child(3)').text();
        const year = row.find('td:nth-child(2)').text();
        openModal('restore_semester', id, semester, year);
    });

    // Optional: select-wrapper behavior
    $('.select-wrapper select').each(function(){
        const wrapper = $(this).parent();
        $(this).on('focus click change', () => wrapper.addClass('open'));
        $(this).on('blur', () => wrapper.removeClass('open'));
    });

});

$(document).on('click', '.make_default', function() {
    const id = $(this).data('id');
    $('#confirm_modal .modal-body').html("Make this semester the system default?");
    const modal = new bootstrap.Modal($('#confirm_modal'));
    modal.show();

    $('#confirm_modal #confirm').off('click').on('click', function(){
        modal.hide();
        $.post('semester.php', {
            action: 'make_default',
            id: id,
            csrf_token: '<?= $csrf_token ?>'
        }, function(resp){
            resp = resp.trim();
            if(resp === 'default_updated'){
                toastr.success("System default updated successfully");
                setTimeout(()=> location.reload(), 1000);
            } else {
                toastr.error("Failed to update default semester: " + resp);
            }
        }).fail(function(xhr){
            toastr.error("AJAX error: " + xhr.responseText);
        });
    });
});




</script>



</body>
</html>
