<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Evaluation Assignments</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #evaluatee-list { max-height:180px; overflow-y:auto; }
        #evaluator-list { max-height:220px; overflow-y:auto; }
        #selected-evaluators { max-height:150px; overflow-y:auto; }
    </style>
</head>
<body>
<div class="main-wrapper">
<?php
session_start();
include '../../Layout Components/header.php';
include '../../Layout Components/sidebar.php';
require_once '../../1database_conn/connection.php'; // Ensure connection is here

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

$academic_id = null;
$q = "SELECT id FROM academic_list WHERE is_default = 1 LIMIT 1";
$r = $conn->query($q);
if ($r && $r->num_rows > 0) {
    $row = $r->fetch_assoc();
    $academic_id = $row['id']; // This should grab ID 3
}

// Fallback: If no default, try the 'is_active' one
if (!$academic_id) {
    $q2 = "SELECT id FROM academic_list WHERE is_active = 1 LIMIT 1";
    $r2 = $conn->query($q2);
    if ($r2 && $r2->num_rows > 0) {
        $row2 = $r2->fetch_assoc();
        $academic_id = $row2['id'];
    }
}
?>
<div class="page-wrapper">
    <div class="content container-fluid">

        <div class="page-header">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Evaluation Assignment</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="evaluation_list.php">Evaluation</a></li>
                        <li class="breadcrumb-item active">Assign Evaluators</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Step 1 and Selected Evaluators + Form Selection -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Step 1: Select Evaluatee</h5>
                        <label>Department:</label>
                        <select id="evaluatee-dept" class="form-control mb-2">
                            <option value="">-- Select Department --</option>
                        </select>
                        <div id="evaluatee-list" class="border p-2">
                            <p class="text-muted">Select a department first</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Selected Evaluator Info</h5>
                        <ul id="selected-evaluators" class="list-group mb-2"></ul>
                        <label>Form:</label>
                        <select id="form-select" class="form-control">
                            <option value="">-- Select Form --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Evaluators -->
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Step 2: Select Evaluators</h5>
                        <label>Department:</label>
                        <select id="evaluator-dept" class="form-control mb-2">
                            <option value="">-- Select Department --</option>
                        </select>
                        <div id="evaluator-list" class="border p-2">
                            <p class="text-muted">Select a department first</p>
                        </div>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="select-all-evaluators">
                            <label class="form-check-label" for="select-all-evaluators">Select All Evaluators</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-3">
            <button id="assign-btn" class="btn btn-primary">Assign Evaluators</button>
        </div>
    </div>

    <?php include '../../Layout Components/footer.php'; ?>
</div>
</div>

<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/feather.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function(){
    let evaluateeUsers = [], evaluatorUsers = [];
    let selectedEvaluatee = null;
    let selectedEvaluators = [];
    let evaluatorMap = {};
    const academic_id = <?= json_encode($academic_id) ?>;

    // Load departments
    $.getJSON("get_departments.php", res => {
        if(res.success){
            let options = res.departments.map(d => `<option value="${d.id}">${d.department_name}</option>`);
            $("#evaluatee-dept, #evaluator-dept").append(options);
        }
    });

    // Load forms
    $.getJSON("get_forms.php", { academic_id })
      .done(res => {
          if(Array.isArray(res)){
              let options = res.map(f => `<option value="${f.id}">${f.form_name}</option>`);
              $("#form-select").append(options);
          }
      });

    // Evaluatee list
    $("#evaluatee-dept").on("change", function(){
        const dept_id = $(this).val();
        $("#evaluatee-list").html('<p class="text-muted">Loading...</p>');
        $.getJSON("get_users_by_department.php", { department_id: dept_id }, res => {
            evaluateeUsers = res.users || [];
            let html = evaluateeUsers.map(u => {
                const checked = (selectedEvaluatee === u.id) ? 'checked' : '';
                return `<div class="form-check">
                            <input type="radio" name="evaluatee-radio" class="form-check-input evaluatee-radio" data-id="${u.id}" ${checked}>
                            <label class="form-check-label">${u.fullName} (${u.email})</label>
                        </div>`;
            }).join('');
            $("#evaluatee-list").html(html || '<p class="text-muted">No users found</p>');
            updateEvaluatorCheckboxes();
        });
    });

    // Evaluator list
    $("#evaluator-dept").on("change", function(){
        const dept_id = $(this).val();
        $("#evaluator-list").html('<p class="text-muted">Loading...</p>');
        $.getJSON("get_users_by_department.php", { department_id: dept_id }, res => {
            evaluatorUsers = res.users || [];
            evaluatorUsers.forEach(u => evaluatorMap[u.id] = u);
            updateEvaluatorCheckboxes();
        });
    });

    // Update evaluator checkboxes
    function updateEvaluatorCheckboxes(){
        let html = evaluatorUsers.map(u => {
            const isSelected = selectedEvaluators.includes(u.id);
            const isEvaluatee = (selectedEvaluatee === u.id);
            return `<div class="form-check">
                        <input type="checkbox" class="form-check-input evaluator-checkbox" data-id="${u.id}" ${isSelected ? 'checked' : ''} ${isEvaluatee ? 'disabled' : ''}>
                        <label class="form-check-label">${u.fullName} (${u.email})</label>
                    </div>`;
        }).join('') || '<p class="text-muted">No users found</p>';
        $("#evaluator-list").html(html);
        updateSelectedEvaluatorsPanel();
    }

    // Evaluatee selection
    $(document).on('change', '.evaluatee-radio', function(){
        selectedEvaluatee = $(this).data('id');

        // Remove evaluatee from selectedEvaluators if present
        if(selectedEvaluators.includes(selectedEvaluatee)){
            selectedEvaluators = selectedEvaluators.filter(id => id !== selectedEvaluatee);
            toastr.warning("Evaluatee cannot be selected as an evaluator. They have been removed from the evaluator list.");
        }

        updateEvaluatorCheckboxes();
    });

    // Evaluator selection
    $(document).on('change', '.evaluator-checkbox', function(){
        const id = $(this).data('id');

        // Prevent selecting the evaluatee
        if(id === selectedEvaluatee && this.checked){
            $(this).prop("checked", false);
            toastr.warning("Cannot select the evaluatee as an evaluator.");
            return;
        }

        if(this.checked && !selectedEvaluators.includes(id)) selectedEvaluators.push(id);
        if(!this.checked) selectedEvaluators = selectedEvaluators.filter(x => x !== id);
        updateSelectedEvaluatorsPanel();
    });

    // Select all evaluators
    $("#select-all-evaluators").on("change", function(){
        const checked = this.checked;
        $(".evaluator-checkbox:not(:disabled)").each(function(){
            $(this).prop("checked", checked).trigger("change");
        });
    });

    function updateSelectedEvaluatorsPanel(){
        const html = selectedEvaluators.map(id => {
            const user = evaluatorMap[id] || {};
            return `<li class="list-group-item">${user.fullName || 'Unknown'} (${user.email || ''})</li>`;
        }).join('');
        $("#selected-evaluators").html(html);
    }
    // Assign button
    $("#assign-btn").on("click", function(){
        // Validations
        if(!selectedEvaluatee) {
            toastr.error("Please select an Evaluatee.");
            return;
        }
        if(selectedEvaluators.length === 0) {
            toastr.error("Please select at least one Evaluator.");
            return;
        }
        
        const selectedForm = $("#form-select").val();
        if(!selectedForm){
            toastr.error("Please select a Form.");
            return;
        }

        // --- THE FIX: Updated Payload Keys ---
        $.ajax({
            url: "assign_evaluators.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                evaluatee_id: selectedEvaluatee,   // Key must match PHP
                evaluator_ids: selectedEvaluators, // Key must match PHP
                form_id: selectedForm,             // Key must match PHP
                academic_id: academic_id           // Using the ID we fetched in Step 1
            }),
            success: function(res){
                if(res.success){
                    toastr.success(res.message);
                    setTimeout(()=>location.reload(), 1500);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", error);
                toastr.error("Server connection failed.");
            }
        });
    });
});
</script>
</body>
</html>
