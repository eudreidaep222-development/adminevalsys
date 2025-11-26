<?php
include '../../1database_conn/connection.php';
session_start();

// Ensure evaluator is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../logout.php");
  exit;
}

// assignment_id passed from dashboard
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
$evaluatee = null;

if ($assignment_id > 0) {
  $stmt = $conn->prepare("
    SELECT u.fullName, d.department_name, ea.id AS assignment_id
    FROM evaluation_assignments ea
    INNER JOIN users u ON ea.evaluatee_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE ea.id = ? AND ea.evaluator_id = ?
  ");
  $stmt->bind_param("ii", $assignment_id, $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $result->num_rows > 0) {
    $evaluatee = $result->fetch_assoc();
  }
  $stmt->close();
}

// Fetch academic list for Step 1
$academics = $conn->query("SELECT id, semester, school_year, is_default 
                           FROM academic_list ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
  <title>Evaluation</title>
  <link rel="shortcut icon" href="../../assets/img/logo-small.png">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../../assets/plugins/twitter-bootstrap-wizard/form-wizard.css">
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
      <div class="row align-items-center">
        <div class="col">
          <h3 class="page-title">Evaluation</h3>
          <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Evaluation</li>
          </ul>
        </div>
      </div>
    </div>

  <!-- Evaluatee Info -->
  <?php if ($evaluatee): ?>
    <div class="alert alert-info">
      <strong>Evaluatee:</strong> <?= htmlspecialchars($evaluatee['fullName']); ?><br>
      <strong>Department:</strong> <?= htmlspecialchars($evaluatee['department_name'] ?? 'N/A'); ?>
    </div>
  <?php else: ?>
    <div class="alert alert-danger">
      Invalid evaluation assignment. Please go back to dashboard.
    </div>
  <?php endif; ?>

    <div class="row">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title mb-0">Evaluation Wizard</h4>
          </div>
          <div class="card-body">
            <div id="progrss-wizard" class="twitter-bs-wizard">

              <!-- Wizard Header -->
              <div class="wizard-header sticky-top bg-white shadow-xs py-2" style="z-index: 1030;">
                <ul class="twitter-bs-wizard-nav nav nav-pills nav-justified">
                  <li class="nav-item"><a href="#step1" class="nav-link active" data-toggle="tab"><div class="step-icon"><i class="fas fa-calendar"></i></div></a></li>
                  <li class="nav-item"><a href="#step2" class="nav-link" data-toggle="tab"><div class="step-icon"><i class="fas fa-file-alt"></i></div></a></li>
                  <li class="nav-item"><a href="#step3" class="nav-link" data-toggle="tab"><div class="step-icon"><i class="fas fa-tasks"></i></div></a></li>
                  <li class="nav-item"><a href="#step4" class="nav-link" data-toggle="tab"><div class="step-icon"><i class="fas fa-check"></i></div></a></li>
                </ul>
                <div id="bar" class="progress mt-2">
                  <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"></div>
                </div>
              </div>
              
              <!-- Tab Content -->
              <div class="tab-content twitter-bs-wizard-tab-content">

                <!-- Step 1: Academic -->
                <div class="tab-pane active" id="step1">
                  <h5>Select Academic Year & Semester</h5>
                  <div class="mb-3">
                    <label class="form-label">Academic Year / Semester</label>
                    <select id="academicSelect" class="form-control" required>
                        <option value="" disabled>-- Select Academic Period --</option>
                        <?php 
                        // Reset pointer in case $academics was used previously
                        if ($academics && $academics->num_rows > 0) {
                            $academics->data_seek(0);
                        }
                        while($a = $academics->fetch_assoc()): 
                            // Check if this semester is the active one
                            $isActive = ($a['is_active'] == 1) ? 'selected' : '';
                        ?>
                        <option value="<?= $a['id']; ?>" <?= $isActive; ?>>
                            <?= htmlspecialchars($a['semester'].' - '.$a['school_year']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                  <ul class="pager wizard twitter-bs-wizard-pager-link">
                    <li class="next"><a href="javascript:void(0);" class="btn btn-primary">Next</a></li>
                  </ul>
                </div>

                <!-- Step 2: Form -->
                <div class="tab-pane" id="step2">
                  <h5>Select Evaluation Form</h5>
                  <div class="mb-3">
                    <label class="form-label">Evaluation Form</label>
                    <select id="formSelect" class="form-control" required disabled>
                      <option value="" disabled selected>-- Select a Form --</option>
                    </select>
                  </div>
                  <ul class="pager wizard twitter-bs-wizard-pager-link">
                    <li class="previous"><a href="javascript:void(0);" class="btn btn-secondary">Previous</a></li>
                    <li class="next"><a href="javascript:void(0);" class="btn btn-primary">Next</a></li>
                  </ul>
                </div>

                <!-- Step 3: Criteria -->
                <div class="tab-pane" id="step3">
                  <h5>Evaluation Criteria</h5>
                  <div id="criteriaContainer" class="mt-3"></div>
                  <ul class="pager wizard twitter-bs-wizard-pager-link">
                    <li class="previous"><a href="javascript:void(0);" class="btn btn-secondary">Previous</a></li>
                    <li class="next"><a href="javascript:void(0);" class="btn btn-primary">Next</a></li>
                  </ul>
                </div>

                <!-- Step 4: Review -->
                <div class="tab-pane" id="step4">
                  <h5>Review & Submit</h5>
                  <div class="row">
                    <div class="col-md-12">
                      <div class="card sticky-preview">
                        <div class="card-header"><h5 class="card-title">Form Preview</h5></div>
                        <div class="card-body">
                          <div id="previewContent"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <ul class="pager wizard twitter-bs-wizard-pager-link mt-3">
                    <li class="previous"><a href="javascript:void(0);" class="btn btn-secondary">Previous</a></li>
                    <li class="float-end"><button id="submitEvaluation" class="btn btn-success">Submit Evaluation</button></li>
                  </ul>
                </div>

              </div><!-- end tab-content -->

            </div><!-- end wizard -->
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include '../../Layout Components/footer.php'; ?>
</div>
</div>

<!-- Scripts -->
<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
<script src="../../assets/plugins/twitter-bootstrap-wizard/form-wizard.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script>
$(function(){
  // Apply a global configuration to all toastr calls
  toastr.options = {
    positionClass: 'toast-bottom-right',
    closeButton: false,
    tapToDismiss: false,
    rtl: $('html').attr('data-textdirection') === 'rtl'
  };

  // ---------- Step 1: Load forms for selected academic ----------
  $('#academicSelect').on('change', function(){
    let academicId = $(this).val();
    let $formSelect = $('#formSelect');
    $formSelect
      .prop('disabled', true)
      .html('<option value="" tabindex="-1" disabled selected>-- Select a Form --</option>');
    $('#criteriaContainer').empty();

    if(academicId){
      $.getJSON('load_forms.php', {academic_id: academicId}, function(res){
        if(res.success && res.forms.length){
          $.each(res.forms, function(_, f){
            $formSelect.append(`<option value="${f.id}">${f.form_name}</option>`);
          });
          $formSelect.prop('disabled', false);
        } else {
          toastr.warning('No forms available for this academic period');
        }
        buildStep4Preview();
      }).fail(function(){
        toastr.error('Failed to load forms');
      });
    } else {
      buildStep4Preview();
    }
  });

  // ---------- Step 2: Load criteria HTML for selected form ----------
  $('#formSelect').on('change', function(){
    let formId = $(this).val();
    $('#criteriaContainer').empty();

    if(formId){
      $.get('load_form_criteria.php',{form_id:formId}, function(html){
        $('#criteriaContainer').html(html);
        // After criteria HTML loaded, attach any needed behaviors and refresh preview
        buildStep4Preview();
      }).fail(function(){
        toastr.error('Failed to load criteria');
      });
    } else {
      buildStep4Preview();
    }
  });

  // ---------- Step 3: Q/E/T input sanitization & A calc ----------
  $(document).on('input change', '.q-input, .e-input, .t-input', function(){
    let $row = $(this).closest('tr');

    // sanitize to digits, collapse leading zeros, default to 0
    let sanitized = String(this.value).replace(/[^0-9]/g, '');
    sanitized = sanitized.replace(/^0+(?=\d)/, '');
    let value = sanitized === '' ? 0 : parseInt(sanitized, 10);
    if (isNaN(value)) value = 0;
    if(value > 5) value = 5;

    this.value = value;

    // calc A in the table row
    let q = parseInt($row.find('.q-input').val(),10) || 0;
    let e = parseInt($row.find('.e-input').val(),10) || 0;
    let t = parseInt($row.find('.t-input').val(),10) || 0;
    let a = Math.round((q + e + t) / 3);
    $row.find('.a-input').val(a);

    // refresh preview live
    buildStep4Preview();
  });

// ---------- Build Step 4 Preview (Fixed & Labeled) ----------
function buildStep4Preview(){
    const academicText = $('#academicSelect option:selected').text() || "N/A";
    const formText = $('#formSelect option:selected').text() || "N/A";
    const previewContent = document.getElementById("previewContent");

    // 1. Header Info
    let html = `
      <div class="mb-3">
          <h5><strong>Academic Period:</strong> ${escapeHtml(academicText)}</h5>
          <h5><strong>Form:</strong> ${escapeHtml(formText)}</h5>
      </div>
      <hr/>
    `;

    // 2. Check for Content
    if($('#criteriaContainer').children().length === 0){
      html += '<div class="alert alert-info">No criteria loaded yet.</div>';
      previewContent.innerHTML = html;
      return;
    }

    // 3. Loop Categories
    $('#criteriaContainer .card').each(function(){
        const headerText = $(this).find('.card-header').text().trim();
        const $tableBody = $(this).find('tbody');
        
        // Detect Version: If we find radio buttons, it's Version 1.
        const isVersion1 = $tableBody.find('input[type="radio"]').length > 0;

        html += `<div class="mb-4">
                    <h5 class="text-primary">${escapeHtml(headerText)}</h5>`;

        if (isVersion1) {
            // --- VERSION 1: Radio Buttons (Qualitative) ---
            html += `
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped">
                    <thead class="bg-light text-center align-middle">
                        <tr>
                            <th style="width: 40%; text-align: left;">Criterion</th>
                            <th style="width: 12%;">1<br><small class="text-muted">Strongly Disagree</small></th>
                            <th style="width: 12%;">2<br><small class="text-muted">Disagree</small></th>
                            <th style="width: 12%;">3<br><small class="text-muted">Neutral</small></th>
                            <th style="width: 12%;">4<br><small class="text-muted">Agree</small></th>
                            <th style="width: 12%;">5<br><small class="text-muted">Strongly Agree</small></th>
                        </tr>
                    </thead>
                    <tbody>`;

            $tableBody.find('tr').each(function(){
                // Fix: Read text directly from the first cell
                const criterionText = $(this).find('td').eq(0).text().trim();
                const rating = $(this).find('input[type="radio"]:checked').val();
                
                // Helper to create checkmarks in the right column
                const getCell = (val) => {
                    return rating == val 
                        ? `<td class="text-center bg-success text-white fw-bold"><i class="fa fa-check"></i></td>` 
                        : `<td class="text-center text-muted">-</td>`;
                };

                html += `<tr>
                            <td>${escapeHtml(criterionText)}</td>
                            ${getCell(1)}
                            ${getCell(2)}
                            ${getCell(3)}
                            ${getCell(4)}
                            ${getCell(5)}
                         </tr>`;
            });
            html += `</tbody></table></div>`;

        } else {
            // --- VERSION 2: Q/E/T/A (Quantitative) ---
            html += `
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="bg-light align-middle">
                        <tr>
                            <th rowspan="2">Output</th>
                            <th rowspan="2">Success Indicator</th>
                            <th rowspan="2">Actual Accomplishment</th>
                            <th colspan="4" class="text-center">Rating</th>
                        </tr>
                        <tr>
                            <th class="text-center" style="width:40px;">Q</th>
                            <th class="text-center" style="width:40px;">E</th>
                            <th class="text-center" style="width:40px;">T</th>
                            <th class="text-center" style="width:40px;">A</th>
                        </tr>
                    </thead>
                    <tbody>`;

            $tableBody.find('tr').each(function(){
                // Try to read textarea vals (if inputs) OR text (if static)
                let output = $(this).find('textarea').eq(0).val(); 
                if(!output) output = $(this).find('td').eq(0).text(); // Fallback for text mode

                let success = $(this).find('textarea').eq(1).val();
                if(!success) success = $(this).find('td').eq(1).text();

                let accom = $(this).find('textarea').eq(2).val(); 
                if(!accom) accom = $(this).find('td').eq(2).text(); // Fallback if taking mode doesn't have input here

                // Ratings
                const q = $(this).find('.q-input').val() || '-';
                const e = $(this).find('.e-input').val() || '-';
                const t = $(this).find('.t-input').val() || '-';
                const a = $(this).find('.a-input').val() || '-';

                html += `<tr>
                            <td>${escapeHtml(output)}</td>
                            <td>${escapeHtml(success)}</td>
                            <td>${escapeHtml(accom)}</td>
                            <td class="text-center">${q}</td>
                            <td class="text-center">${e}</td>
                            <td class="text-center">${t}</td>
                            <td class="text-center fw-bold bg-light">${a}</td>
                         </tr>`;
            });
            html += `</tbody></table></div>`;
        }

        html += `</div>`;
    });

    previewContent.innerHTML = html;
}
  // simple HTML escape to avoid accidental HTML injection in preview
  function escapeHtml(str){
    if(!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // ---------- Bind live update triggers ----------
  // academic/form change
  $('#academicSelect, #formSelect').on('change', buildStep4Preview);

  // inputs inside the criteria area (also catches dynamically loaded HTML)
  $(document).on('input change', '#criteriaContainer textarea, #criteriaContainer input', buildStep4Preview);

  // refresh when Step 4 tab is shown (bootstrap)
  $('a[data-toggle="tab"][href="#step4"]').on('shown.bs.tab', buildStep4Preview);

  // also refresh right after criteria loaded by AJAX (handled in load callback above)

  // ---------- initial auto-load if academic pre-selected ----------
  if($('#academicSelect').val()){
    $('#academicSelect').trigger('change');
  }

  // Validation for Next Step button
// Validation for Next Step button
$(document).on('click', '.twitter-bs-wizard-pager-link .next', function(e){
    // Don't prevent default immediately, let wizard handle it unless we return false
    
    let $active = $('.tab-content .tab-pane.active');
    let id = $active.attr('id');

    // Step 1 Validation
    if(id === 'step1'){
        if(!$('#academicSelect').val()){
            toastr.warning('Please select an Academic Period before proceeding.');
            return false;
        }
    } 
    // Step 2 Validation
    else if(id === 'step2'){
        if(!$('#formSelect').val()){
            toastr.warning('Please select an Evaluation Form before proceeding.');
            return false;
        }
    } 
    // Step 3 Validation (CRITERIA)
    else if(id === 'step3'){
        let invalid = false;
        let message = 'Please answer all questions before proceeding.';

        $active.find('tbody tr').each(function(){
            const $row = $(this);

            // 1. Check for Version 2 Inputs (Q/E/T)
            if ($row.find('.q-input').length > 0) {
                let q = $row.find('.q-input').val();
                let eVal = $row.find('.e-input').val();
                let tVal = $row.find('.t-input').val();

                if(q === '' || eVal === '' || tVal === '' ||
                   isNaN(q) || isNaN(eVal) || isNaN(tVal) ||
                   q < 0 || q > 5 || eVal < 0 || eVal > 5 || tVal < 0 || tVal > 5){
                    invalid = true;
                    message = 'Please complete all rating fields (0-5).';
                    return false; // break loop
                }
            }
            // 2. Check for Version 1 Inputs (Radio Buttons)
            else if ($row.find('input[type="radio"]').length > 0) {
                // Check if ANY radio button in this group is checked
                if ($row.find('input[type="radio"]:checked').length === 0) {
                    invalid = true;
                    message = 'Please select a rating for all criteria.';
                    return false; // break loop
                }
            }
        });

        if(invalid){
            toastr.warning(message);
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }
  });

// Intercept nav tab clicks (prevent skipping)
  $('.twitter-bs-wizard-nav a').on('click', function(e){
    let target = $(this).attr('href'); // e.g. #step2
    let allow = true;

    // STEP 2 requires Academic Period
    if(target === '#step2'){
      if(!$('#academicSelect').val()){
        toastr.warning('Please select an Academic Period before proceeding.');
        allow = false;
      }
    }

    // STEP 3 requires Academic Period + Form
    else if(target === '#step3'){
      if(!$('#academicSelect').val()){
        toastr.warning('Please select an Academic Period first.');
        allow = false;
      } else if(!$('#formSelect').val()){
        toastr.warning('Please select an Evaluation Form before proceeding.');
        allow = false;
      }
    }

    // STEP 4 requires Academic Period + Form + valid Criteria
    else if(target === '#step4'){
      if(!$('#academicSelect').val()){
        toastr.warning('Please select an Academic Period first.');
        allow = false;
      } else if(!$('#formSelect').val()){
        toastr.warning('Please select an Evaluation Form before proceeding.');
        allow = false;
      } else {
        let invalid = false;
        $('#step3').find('tr').each(function(){
          if($(this).find('.q-input').length === 0) return true; // skip headers

          let q = $(this).find('.q-input').val();
          let eVal = $(this).find('.e-input').val();
          let tVal = $(this).find('.t-input').val();

          if(q === '' || eVal === '' || tVal === '' ||
             isNaN(q) || isNaN(eVal) || isNaN(tVal) ||
             q < 0 || q > 5 || eVal < 0 || eVal > 5 || tVal < 0 || tVal > 5){
            invalid = true;
            return false; // break loop
          }
        });

        if(invalid){
          toastr.warning('Please complete all criteria fields (values 0–5) before proceeding.');
          allow = false;
        }
      }
    }

    // Stop tab change if not allowed
    if(!allow){
      e.preventDefault();
      return false;
    }
  });

  // Handle Submit Button
$('#submitEvaluation').on('click', function () {
    const academicId = $('#academicSelect').val();
    const formId = $('#formSelect').val();
    // Ensure assignmentId is printed as a number or 0 if missing
    const assignmentId = <?= $assignment_id ? $assignment_id : 0 ?>;

    let criteria = [];
    let missingAnswers = false;

    // Iterate through all rows in the table
    $('#criteriaContainer tbody tr').each(function(){
        const $row = $(this);
        
        // Check 1: Is it a Version 2 Row? (Has .q-input)
        if($row.find('.q-input').length > 0) {
            criteria.push({
                // For V2, we usually send the text values plus the ratings
                output: $row.find('textarea').eq(0).val(),
                success_indicator: $row.find('textarea').eq(1).val(),
                accomplishment: $row.find('textarea').eq(2).val(),
                q: $row.find('.q-input').val(),
                e: $row.find('.e-input').val(),
                t: $row.find('.t-input').val(),
                a: $row.find('.a-input').val(),
                // Try to find an ID if it exists in a hidden input, otherwise backend matches by order
                id: $row.find('input[type="hidden"]').val() 
            });
        }
        // Check 2: Is it a Version 1 Row? (Has Radio Buttons)
        else if ($row.find('input[type="radio"]').length > 0) {
            const $checked = $row.find('input[type="radio"]:checked');
            
            if($checked.length === 0) {
                missingAnswers = true;
                return false; // Stop loop, show error
            }

            // Extract Question ID from the name "criterion_123"
            const nameAttr = $row.find('input[type="radio"]').attr('name'); 
            const questionId = nameAttr ? nameAttr.split('_')[1] : null;

            criteria.push({
                question_id: questionId, // Send the ID so backend knows which question this is
                rating: $checked.val(),
                // Fallback: send the text just in case backend needs it
                output: $row.find('td').first().text().trim() 
            });
        }
    });

    // Validation Checks
    if (missingAnswers) {
        toastr.warning('Please answer all questions before submitting.');
        return;
    }

    if (criteria.length === 0) {
        toastr.error('No data found. Please ensure the form is loaded.');
        return;
    }

    // Disable button to prevent double-submit
    const $btn = $(this);
    $btn.prop('disabled', true).text('Submitting...');

    // Send to Server
    $.post('submit_evaluation.php', {
        assignment_id: assignmentId,
        form_id: formId,
        academic_id: academicId,
        criteria: criteria
    }, function(res){
        if(res.success){
            toastr.success(res.message);
            // Redirect after success
            setTimeout(()=> window.location.href = "home.php", 1500);
        } else {
            toastr.error(res.message || "An error occurred");
            $btn.prop('disabled', false).text('Submit Evaluation');
        }
    }, 'json')
    .fail(function(){
        toastr.error('Server connection failed.');
        $btn.prop('disabled', false).text('Submit Evaluation');
    });
});

});
</script>



</body>
</html>