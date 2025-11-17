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
    <?php exit; ?>
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
                      <option value="" disabled selected>-- Select Academic Period --</option>
                      <?php while($a = $academics->fetch_assoc()): ?>
                        <option value="<?= $a['id']; ?>"><?= htmlspecialchars($a['semester'].' - '.$a['school_year']); ?></option>
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

  // ---------- Build Step 4 preview (category card style) ----------
  function buildStep4Preview(){
    const academicText = $('#academicSelect option:selected').text() || "N/A";
    const formText = $('#formSelect option:selected').text() || "N/A";
    const previewContent = document.getElementById("previewContent");

    // Basic header
    let html = `
      <h5><strong>Academic Period:</strong> ${escapeHtml(academicText)}</h5>
      <h5><strong>Form:</strong> ${escapeHtml(formText)}</h5>
      <hr/>
    `;

    // If there are no categories loaded, show message
    if($('#criteriaContainer').children().length === 0){
      html += '<div class="alert alert-info">No criteria loaded yet.</div>';
      previewContent.innerHTML = html;
      return;
    }

    // The server renders each category as a .card in #criteriaContainer
    $('#criteriaContainer .card').each(function(){
      // read header strong text (format: "CategoryName (XX%)" as load_form_criteria.php emits)
      const headerText = $(this).find('.card-header').text().trim();
      // find the table inside this card
      const $table = $(this).find('table').first();

      html += `<div class="mb-3"><h4>${escapeHtml(headerText)}</h4>`;
      html += `<div class="table-responsive"><table class="table table-bordered"><thead>
                <tr>
                  <th>Output</th><th>Success Indicator</th><th>Actual Accomplishment</th>
                  <th style="width:50px;">Q</th><th style="width:50px;">E</th><th style="width:50px;">T</th><th style="width:50px;">A</th>
                </tr></thead><tbody>`;

      $table.find('tbody tr').each(function(){
        // find textareas and inputs in the same order as in load_form_criteria.php
        const $cells = $(this).find('textarea, input');

        // protect against missing cells (use empty string or 0)
        const output = $cells.eq(0).val() || '';
        const successIndicator = $cells.eq(1).val() || '';
        const accomplishment = $cells.eq(2).val() || '';
        const q = parseInt($cells.eq(3).val(),10) || 0;
        const e = parseInt($cells.eq(4).val(),10) || 0;
        const t = parseInt($cells.eq(5).val(),10) || 0;
        const a = Math.round((q + e + t) / 3);

        html += `<tr>
                  <td>${escapeHtml(output)}</td>
                  <td>${escapeHtml(successIndicator)}</td>
                  <td>${escapeHtml(accomplishment)}</td>
                  <td>${q}</td><td>${e}</td><td>${t}</td><td>${a}</td>
                </tr>`;
      });

      html += `</tbody></table></div></div>`;
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
  $(document).on('click', '.twitter-bs-wizard-pager-link .next', function(e){
    e.preventDefault();
    let $active = $('.tab-content .tab-pane.active');
    let id = $active.attr('id');

    if(id === 'step1'){
      if(!$('#academicSelect').val()){
        toastr.warning('Please select an Academic Period before proceeding.');
        return false;
      }
    } 
    else if(id === 'step2'){
      if(!$('#formSelect').val()){
        toastr.warning('Please select an Evaluation Form before proceeding.');
        return false;
      }
    } 
    else if(id === 'step3'){
      let invalid = false;
      $active.find('tr').each(function(){
        if($(this).find('.q-input').length === 0) return true;

        let q = $(this).find('.q-input').val();
        let e = $(this).find('.e-input').val();
        let t = $(this).find('.t-input').val();

        if(q === '' || e === '' || t === ''){
          invalid = true; return false;
        }
        if(isNaN(q) || isNaN(e) || isNaN(t) ||
           q < 0 || q > 5 || e < 0 || e > 5 || t < 0 || t > 5){
          invalid = true; return false;
        }
      });

      if(invalid){
        toastr.warning('Please complete all criteria fields (values 0–5) before proceeding.');
        return false;
      }
    }

    // If passed validation, advance wizard
    if(typeof $('#progrss-wizard').bootstrapWizard === 'function'){
      $('#progrss-wizard').bootstrapWizard('next');
    } else {
      // fallback: move to next tab manually
      let nextId = $active.next('.tab-pane').attr('id');
      if(nextId){
        $(`a[href="#${nextId}"]`).tab('show');
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

  $('#submitEvaluation').on('click', function () {
    const academicId = $('#academicSelect').val();
    const formId = $('#formSelect').val();
    const assignmentId = <?= $assignment_id ?>;

    // Collect criteria data from step3
    let criteria = [];
    $('#criteriaContainer tbody tr').each(function(){
        if($(this).find('.q-input').length === 0) return true;

        criteria.push({
            output: $(this).find('textarea').eq(0).val(),
            success_indicator: $(this).find('textarea').eq(1).val(),
            accomplishment: $(this).find('textarea').eq(2).val(),
            q: $(this).find('.q-input').val(),
            e: $(this).find('.e-input').val(),
            t: $(this).find('.t-input').val(),
            a: $(this).find('.a-input').val()
        });
    });

    $.post('submit_evaluation.php', {
        assignment_id: assignmentId,
        form_id: formId,
        academic_id: academicId,
        criteria: criteria
    }, function(res){
        if(res.success){
            toastr.success(res.message);
            setTimeout(()=> window.location.href="home.php", 1500);
        } else {
            toastr.error(res.message);
        }
    }, 'json').fail(function(){
        toastr.error('Submission failed, please try again.');
    });
});


});
</script>



</body>
</html>