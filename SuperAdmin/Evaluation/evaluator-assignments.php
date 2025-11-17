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
</head>

<body>
    <div class="main-wrapper">
        <?php
        include '../../Layout Components/header.php';
        include '../../Layout Components/sidebar.php';

        // ✅ Fetch active academic_id from default semester
        $academic_id = $defaultSemester['id'] ?? null;
        ?>
        <div class="page-wrapper">
            <div class="content container-fluid">

                <!-- Page Header -->
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

                <!-- Evaluation Assignment Card -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="evaluation-assign-form">
                                    <h5 class="card-title"><span>Evaluation Management</span></h5>
                                    <div class="row">

                                        <!-- Department Dropdown -->
                                        <div class="col-md-6 form-group">
                                            <label for="department">Select Department <span class="text-danger">*</span></label>
                                            <select id="department" class="form-control" required>
                                                <option value="">-- Select Department --</option>
                                            </select>
                                        </div>

                                        <!-- Evaluation Form Dropdown -->
                                        <div class="col-md-6 form-group">
                                            <label for="form-select">Select Evaluation Form <span class="text-danger">*</span></label>
                                            <select id="form-select" class="form-control" required>
                                                <option value="">-- Select Evaluation Form --</option>
                                            </select>
                                        </div>

                                        <!-- Evaluator Dropdown -->
                                        <div class="col-md-6 form-group">
                                            <label for="evaluator-select">Select Evaluator <span class="text-danger">*</span></label>
                                            <select id="evaluator-select" class="form-control" required>
                                                <option value="">-- Select a Department First --</option>
                                            </select>
                                        </div>

                                        <!-- Evaluatee Checkboxes -->
                                        <div class="col-md-12 form-group mt-3">
                                            <label>Select Evaluatee(s) <span class="text-danger">*</span></label>
                                            <div id="evaluatee-list" class="border p-2 rounded" style="max-height:200px; overflow-y:auto;">
                                                <p class="text-muted">-- Select a Department First --</p>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 text-end mt-3">
                                            <button type="submit" class="btn btn-primary">Assign Evaluators</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
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
    <script src="../../assets/js/script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function () {
    const academic_id = <?= json_encode($academic_id) ?>;

    // ✅ Load departments
    $.getJSON("get_departments.php", function (res) {
        if (res.success) {
            $("#department").append(
                res.departments.map(d => `<option value="${d.id}">${d.department_name}</option>`)
            );
        }
    });

    // ✅ Load forms for current academic_id
    $.getJSON("get_forms.php", { academic_id }, function (forms) {
        if (forms.length > 0) {
            $("#form-select").append(
                forms.map(f => `<option value="${f.id}">${f.form_name}</option>`)
            );
        }
    });

    let deptUsers = [];

    // When department changes
    $("#department").on("change", function () {
        const dept_id = $(this).val();
        $("#evaluator-select").html('<option value="">-- Select a Department First --</option>');
        $("#evaluatee-list").html('<p class="text-muted">-- Select a Department First --</p>');

        if (!dept_id) return;

        $.getJSON("get_users_by_department.php", { dept_id }, function (res) {
            if (res.success && res.users.length > 0) {
                deptUsers = res.users;

                // Build evaluator dropdown
                let evaluatorOptions = '<option value="">-- Select Evaluator --</option>';
                deptUsers.forEach(u => {
                    evaluatorOptions += `<option value="${u.id}">${u.fullName} (${u.email})</option>`;
                });
                $("#evaluator-select").html(evaluatorOptions);
            } else {
                $("#evaluator-select").html('<option value="">No users found</option>');
                $("#evaluatee-list").html('<p class="text-muted">No users found</p>');
            }
        });
    });

    // When evaluator changes, rebuild evaluatee list excluding evaluator
    $("#evaluator-select").on("change", function () {
        const evaluatorId = $(this).val();
        if (deptUsers.length === 0) return;

        let evaluateeHtml = "";
        deptUsers.forEach(u => {
            if (u.id != evaluatorId) {
                evaluateeHtml += `
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="evaluatees[]" value="${u.id}">
                    <label class="form-check-label">${u.fullName} (${u.email})</label>
                  </div>`;
            }
        });

        $("#evaluatee-list").html(evaluateeHtml || '<p class="text-muted">No evaluatees available</p>');
    });

    // Submit assignment
// Submit assignment
$("#evaluation-assign-form").on("submit", function (e) {
    e.preventDefault();

    const dept_id     = $("#department").val();
    const evaluator_id = $("#evaluator-select").val();
    const form_id     = $("#form-select").val();
    const evaluatees  = $("input[name='evaluatees[]']:checked")
                          .map(function(){ return this.value; }).get();

    if (!dept_id || !evaluator_id || !form_id || evaluatees.length === 0) {
        toastr.error("Please fill in all required fields.");
        return;
    }

    $.ajax({
        url: "assign_evaluators.php",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({ 
            department_id: dept_id,   // ✅ send department_id
            admin_id: evaluator_id, 
            ratees: evaluatees, 
            form_id: form_id, 
            academic_id: academic_id 
        }),
        success: function (res) {
            if (res.success) {
                toastr.success(res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(res.message);
            }
        }
    });
});

});
</script>

</body>
</html>
