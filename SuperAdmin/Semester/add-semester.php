<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Add Semester</title>

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
                            <h3 class="page-title">Add Semester</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="semester.php">Semester</a></li>
                                <li class="breadcrumb-item active">Add Semester</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php include '../../1database_conn/connection.php';
                if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                    $id = intval($_GET['id']);
                    $qry = $conn->query("SELECT * FROM academic_list WHERE id = $id");
                    if ($qry && $qry->num_rows > 0) {
                        $data = $qry->fetch_array();
                        foreach ($data as $k => $v) {
                            $$k = $v;
                        }
                    }
                }
                ?>

            <div class="row">
                <!-- Left Side: Form -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <form id="semester-form">
                                <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
                                <h5 class="card-title">Semester Details</h5>
                                <div class="row">
                                    <!-- School Year Dropdown -->
                                    <div class="col-sm-6 form-group dropdown-animated">
                                        <label>Year <span class="text-danger">*</span></label>
                                        <div class="select-wrapper">
                                            <select name="school_year" id="school_year" class="form-control" required>
                                                <option value="" tabindex="-1" disabled selected>-- Select Academic Period --</option>
                                                <?php
                                                $years = [];

                                                // Fetch existing years
                                                $res = $conn->query("SELECT DISTINCT school_year FROM academic_list");
                                                if ($res && $res->num_rows > 0) {
                                                    while ($row = $res->fetch_assoc()) {
                                                        $years[] = trim($row['school_year']);
                                                    }
                                                }

                                                // Extract start years
                                                $startYears = [];
                                                foreach ($years as $yr) {
                                                    if (preg_match('/^(\d{4})-(\d{4})$/', $yr, $m)) {
                                                        $startYears[] = (int)$m[1];
                                                    }
                                                }

                                                // If DB has no years, start at current year
                                                if (empty($startYears)) {
                                                    $minYear = (int)date("Y");
                                                    $maxYear = $minYear;
                                                } else {
                                                    $minYear = min($startYears);
                                                    $maxYear = max($startYears);
                                                }

                                                // Generate continuous sequence from minYear → maxYear+5
                                                $allYears = [];
                                                for ($y = $minYear; $y <= $maxYear + 5; $y++) {
                                                    $allYears[] = $y . '-' . ($y + 1);
                                                }

                                                // Deduplicate + merge with DB (safety)
                                                $allYears = array_unique(array_merge($years, $allYears));

                                                // Sort ascending
                                                usort($allYears, function($a, $b) {
                                                    preg_match('/^(\d{4})/', $a, $ma);
                                                    preg_match('/^(\d{4})/', $b, $mb);
                                                    return ((int)$ma[1]) <=> ((int)$mb[1]);
                                                });

                                                // Output options
                                                foreach ($allYears as $yr) {
                                                    $safe = htmlspecialchars($yr);
                                                    $selected = (isset($school_year) && $school_year == $yr) ? 'selected' : '';
                                                    echo "<option value=\"{$safe}\" {$selected}>{$safe}</option>";
                                                }
                                                ?>

                                            </select>
                                            <span class="arrow">&#9662;</span>
                                        </div>
                                    </div>  
                                    <!-- Semester Dropdown -->
                                    <div class="col-sm-6 form-group dropdown-animated">
                                        <label>Semester <span class="text-danger">*</span></label>
                                        <div class="select-wrapper">
                                            <select name="semester" id="semester" class="form-control" required>
                                                <option value="" tabindex="-1" disabled selected>-- Select Semester --</option>
                                                <option value="1" <?php echo (isset($semester) && $semester == 1) ? 'selected' : ''; ?>>1</option>
                                                <option value="2" <?php echo (isset($semester) && $semester == 2) ? 'selected' : ''; ?>>2</option>
                                                <option value="3" <?php echo (isset($semester) && $semester == 3) ? 'selected' : ''; ?>>3</option>
                                                <option value="4" <?php echo (isset($semester) && $semester == 4) ? 'selected' : ''; ?>>4</option>
                                            </select>
                                            <span class="arrow">&#9662;</span>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 form-group">
                                        <label>Semester Start Date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" required value="<?php echo isset($start_date) ? $start_date : '' ?>">
                                    </div>
                                    <div class="col-sm-6 form-group">
                                        <label>Semester End Date <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" required value="<?php echo isset($end_date) ? $end_date : '' ?>">
                                    </div>

                                    <?php if (isset($status)): ?>
                                    <div class="col-sm-12 form-group">
                                        <label>Status</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Pending</option>
                                            <option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Started</option>
                                            <option value="2" <?php echo isset($status) && $status == 2 ? 'selected' : '' ?>>Closed</option>
                                        </select>
                                    </div>
                                    <?php endif; ?>

                                    <div class="col-sm-12 text-end">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Live Preview -->
                <div class="col-md-6">
                    <div id="semester-preview">
                        <div class="alert alert-info">
                            <em>Preview will appear here...</em>
                        </div>
                    </div>
                </div>
            </div>

            </div>
            <?php include '../../Layout Components/footer.php'; ?>
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
function validateDates() {
    let start = document.getElementById('start_date').value;
    let end = document.getElementById('end_date').value;

    if (start && end) {
        if (moment(end).isSameOrBefore(moment(start))) {
            toastr.error("End date must be greater than Start date", "Invalid Dates");
            document.getElementById('end_date').value = ""; // reset invalid input
            return false;
        }
    }
    return true;
}

// Re-bind validation on input
['start_date','end_date'].forEach(id => {
    let el = document.getElementById(id);
    if(el){
        el.addEventListener('change', function() {
            validateDates();
            updatePreview();
        });
        el.addEventListener('input', function() {
            validateDates();
            updatePreview();
        });
    }
});

// Also check on submit
$('#semester-form').submit(function(e){
    e.preventDefault();

    if (!validateDates()) {
        return; // stop submission if invalid
    }

    start_load();
    const formData = $(this).serialize() + '&action=save_academic';
    $.ajax({
        url: 'ajax.php',
        method: 'POST',
        data: formData,
        success: function(resp){
            if(resp == 1){
                toastr.success("Semester saved successfully", "Success");
                setTimeout(() => location.reload(), 1500);
            } else if(resp == 2){
                toastr.error("Duplicate semester detected", "Error");
                end_load();
            } else {
                toastr.error("Unexpected error: " + resp, "Error");
                end_load();
            }
        },
        error: function(xhr, status, error) {
            toastr.error("AJAX error: " + error, "Error");
            end_load();
        }
    });
});

function updatePreview() {
    let year = document.getElementById('school_year').value;
    let semester = document.getElementById('semester').value;
    let start = document.getElementById('start_date').value;
    let end = document.getElementById('end_date').value;
    let status = document.getElementById('status') ? document.getElementById('status').value : null;

    let semText = {
        1: "1",
        2: "2",
        3: "3",
        4: "4"
    };

    if (!year && !semester && !start && !end) {
        document.getElementById('semester-preview').innerHTML = `
            <div class="alert alert-info"><em>Preview will appear here...</em></div>`;
        return;
    }

    let badge = "";
    if (status !== null) {
        if (status == 0) badge = '<span class="badge bg-secondary">Pending</span>';
        if (status == 1) badge = '<span class="badge bg-success">Started</span>';
        if (status == 2) badge = '<span class="badge bg-warning text-dark">Closed</span>';
    }

    let html = `
        <div class="d-flex justify-content-center">
            <div class="alert alert-success mt-2 mb-3" style="width: fit-content;">
                <strong>S.Y. ${year || ''}</strong> &nbsp;
                <strong>Sem:</strong> ${semText[semester] || ''} &nbsp;
                ${start ? `<strong>${moment(start).format("MMMM DD, YYYY")}</strong>` : ''} -
                ${end ? `<strong>${moment(end).format("MMMM DD, YYYY")}</strong>` : ''} &nbsp;
                <strong>Status:</strong> ${badge}
            </div>
        </div>
    `;
    document.getElementById('semester-preview').innerHTML = html;
}

// bind events
['school_year','semester','start_date','end_date','status'].forEach(id=>{
    let el = document.getElementById(id);
    if(el){
        el.addEventListener('change', updatePreview);
        el.addEventListener('input', updatePreview);
    }
});

// run once at load
updatePreview();

document.querySelectorAll('.select-wrapper select').forEach(select => {
    const wrapper = select.parentElement;

    // When the select gains focus (user clicks it), set open
    select.addEventListener('focus', () => {
        wrapper.classList.add('open');
    });

    // When user changes value, keep it open until blur
    select.addEventListener('change', () => {
        wrapper.classList.add('open');
    });

    // When the select loses focus, remove open
    select.addEventListener('blur', () => {
        wrapper.classList.remove('open');
    });

    // Optional: clicking again toggles
    select.addEventListener('click', () => {
        wrapper.classList.toggle('open');
    });
});


</script>

</body>
</html>

