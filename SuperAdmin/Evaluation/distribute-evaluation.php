<?php
session_start();
include '../../1database_conn/connection.php';

// Authorization Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin'){
    header("Location: ../../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Work Performance Distribution</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/plugins/datatables/datatables.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
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
                    <h3 class="page-title">Evaluation Distribution & Tracking (Version 2)</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../Dashboard/home.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Distribute Evaluation</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h5 class="card-title">Assign New Evaluation Form (Step 0)</h5></div>
                    <div class="card-body">
                        <form id="assignEvaluationForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="userSelect">Select Administrator (Evaluatee)</label>
                                        <select id="userSelect" name="user_id" class="form-control select" required>
                                            <option value="">-- Select Administrator --</option>
                                            <?php
                                            // Fetch Administrators using CONCAT_WS
                                            $users = $conn->query("SELECT id, CONCAT_WS(' ', first_name, middle_name, last_name) AS fullName FROM users WHERE role = 'Administrator' ORDER BY last_name ASC");
                                            if($users){
                                                while($user = $users->fetch_assoc()){
                                                    echo "<option value='{$user['id']}'>{$user['fullName']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="formSelect">Select Approved Version 2 Form</label>
                                        <select id="formSelect" name="form_id" class="form-control select" required>
                                            <option value="">-- Select Form --</option>
                                            <?php
                                            // Fetch Version 2 Forms
                                            $forms = $conn->query("SELECT id, form_name FROM evaluation_forms WHERE versions = 2");
                                            if($forms){
                                                while($form = $forms->fetch_assoc()){
                                                    echo "<option value='{$form['id']}'>{$form['form_name']}</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="form_version" value="2">
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-paper-plane"></i> Assign Form</button>
                            </div>
                        </form>
                        <div id="assignResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table" id="formPreviewCard" style="display:none;">
                    <div class="card-header">
                        <h4 class="card-title"><i class="bi bi-eye-fill me-2"></i> Live Form Preview: <span id="previewFormName"></span></h4>
                    </div>
                    <div class="card-body">
                        <div id="formPreviewArea" class="p-3 border rounded bg-light">
                            <p class="text-muted text-center">Select an evaluation form above to load its structure for preview.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-header">
                        <h4 class="card-title">Evaluation Status Tracker</h4>
                        <p class="card-text text-muted small">Monitor the 4-step workflow: Pending Criteria Fill-up, Needs Approval 1, Self-Evaluation, and Needs Final Approval.</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-center mb-0 datatable" id="trackingTable">
                                <thead>
                                    <tr>
                                        <th>Evaluatee</th>
                                        <th>Evaluation Form</th>
                                        <th>Date Assigned</th>
                                        <th>Current Status</th>
                                        <th class="text-end">Action / Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Fetch all active assignments for tracking
                                $tracking_sql = "SELECT 
                                                    ae.id AS assignment_id, 
                                                    CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name) AS fullName,
                                                    ef.form_name,
                                                    ae.assigned_date,
                                                    ae.status
                                                FROM assigned_evaluations ae 
                                                JOIN users u ON ae.evaluatee_user_id = u.id
                                                JOIN evaluation_forms ef ON ae.evaluation_form_id = ef.id
                                                WHERE ae.status NOT IN ('Completed', 'Archived')
                                                ORDER BY ae.assigned_date DESC";

                                $tracking_result = $conn->query($tracking_sql);
                                if($tracking_result && $tracking_result->num_rows > 0){
                                    while($row = $tracking_result->fetch_assoc()){
                                        $status_badge = '';
                                        $action_button = '';

                                        // Status Mapping for 4-Step Process
                                        switch ($row['status']) {
                                            case 'Pending':
                                                $status_badge = '<span class="badge bg-secondary">Step 1: Criteria Fill-up</span>';
                                                $action_button = '<button class="btn btn-sm btn-outline-secondary" disabled>Waiting</button>';
                                                break;
                                            case 'Needs Approval 1':
                                                $status_badge = '<span class="badge bg-warning">Step 2: Admin Criteria Review</span>';
                                                $action_button = '<a href="review_criteria.php?id='.$row['assignment_id'].'" class="btn btn-sm btn-outline-info">Review & Approve</a>';
                                                break;
                                            case 'Self-Evaluation':
                                                $status_badge = '<span class="badge bg-primary">Step 3: Self-Evaluation</span>';
                                                $action_button = '<button class="btn btn-sm btn-outline-secondary" disabled>In Progress</button>';
                                                break;
                                            case 'Needs Final Approval':
                                                $status_badge = '<span class="badge bg-danger">Step 4: Final Score Approval</span>';
                                                $action_button = '<a href="final_review.php?id='.$row['assignment_id'].'" class="btn btn-sm btn-outline-success">Finalize & Score</a>';
                                                break;
                                            default:
                                                $status_badge = '<span class="badge bg-light text-dark">Unknown</span>';
                                                $action_button = '';
                                        }

                                        echo "<tr>
                                            <td><h2 class='table-avatar'><a href='javascript:void(0);' class='avatar avatar-sm me-2'><i class='fas fa-user-tie text-secondary'></i></a><a href='javascript:void(0);'>".htmlspecialchars($row['fullName'])."</a></h2></td>
                                            <td>".htmlspecialchars($row['form_name'])."</td>
                                            <td>".date('M d, Y', strtotime($row['assigned_date']))."</td>
                                            <td>{$status_badge}</td>
                                            <td class='text-end'>{$action_button}</td>
                                        </tr>";
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">No active evaluations currently being processed.</td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <?php include '../../Layout Components/footer.php'; ?>
</div>
</div>

<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../../assets/plugins/datatables/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
$(document).ready(function() {

    // 1. Initialize Select2
    if($('#userSelect').length > 0) {
        $('#userSelect').select2({ width: '100%' });
    }
    if($('#formSelect').length > 0) {
        $('#formSelect').select2({ width: '100%' });
    }

    // 2. Initialize DataTable for Tracking Table
    if(!$.fn.DataTable.isDataTable('#trackingTable')){
        $('#trackingTable').DataTable({
            "bFilter": true,
            "bInfo": true,
            "ordering": true,
            "language": { "search": "Search Evaluations:", "paginate": { "next":"Next","previous":"Previous" } }
        });
    }

    // 3. Feather icons
    if(typeof feather !== 'undefined') feather.replace();

    // 4. Live Preview Logic
    $('#formSelect').on('change', function(){
        var formId = $(this).val();
        var formName = $('#formSelect option:selected').text();
        
        if (formId) {
            $('#previewFormName').text(formName.replace('-- Select Form --', ''));
            $('#formPreviewCard').show();
            $('#formPreviewArea').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading form preview...</p>');

            fetch('fetch_form_preview.php?form_id=' + formId)
            .then(res => res.json())
            .then(data => {
                if(data.success && data.html_content){
                    $('#formPreviewArea').html(data.html_content);
                } else {
                    $('#formPreviewArea').html('<p class="text-danger text-center">Error loading form content: ' + (data.error || 'Unknown error') + '</p>');
                }
            })
            .catch(err => {
                console.error("Preview Fetch Error:", err);
                $('#formPreviewArea').html('<p class="text-danger text-center">A network or server error occurred while fetching the form.</p>');
            });

        } else {
            $('#formPreviewArea').html('<p class="text-muted text-center">Select an evaluation form above to load its structure for preview.</p>');
            $('#formPreviewCard').hide();
        }
    });

    // 5. Handle AJAX Form Submission (Step 0)
    $('#assignEvaluationForm').on('submit', function(e){
        e.preventDefault();
        var formData = new FormData(this);

        fetch('manage_forms.php', {
            method: 'POST', 
            body: formData
        })
        .then(res => {
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return res.json();
            } else {
                return res.text().then(text => { throw new Error(text) });
            }
        })
        .then(data => {
            if(data.success){
                toastr.success(data.message);
                $('#assignEvaluationForm')[0].reset();
                $('#userSelect, #formSelect').val('').trigger('change');
                setTimeout(() => {
                    window.location.reload(); 
                }, 1000); 
            } else {
                toastr.error(data.error);
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            toastr.error("Server Error: " + err.message.substring(0, 100)); 
        });
    });

});
</script>

</body>
</html>