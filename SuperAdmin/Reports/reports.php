<?php
session_start();
include '../../1database_conn/connection.php';

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

$toastr = null;
if (isset($_SESSION['toastr'])) {
    $toastr = $_SESSION['toastr'];
    unset($_SESSION['toastr']);
}

// 1. INPUTS
$dept_filter  = $_GET['dept_filter'] ?? '';
$school_year  = $_GET['school_year'] ?? '';
$semester     = $_GET['semester'] ?? '';
$sort_order   = $_GET['sort_order'] ?? ''; // New Filter

$schoolYears = [];
$semesters   = [];

// Get School Years
$syResult = $conn->query("SELECT DISTINCT school_year FROM academic_list ORDER BY school_year DESC");
if ($syResult) {
    while ($row = $syResult->fetch_assoc()) {
        $schoolYears[] = $row['school_year'];
    }
}

// Get Semesters
if ($school_year) {
    $stmt = $conn->prepare("SELECT DISTINCT semester FROM academic_list WHERE school_year=? ORDER BY id ASC");
    $stmt->bind_param("s", $school_year);
    $stmt->execute();
    $semResult = $stmt->get_result();
} else {
    $semResult = $conn->query("SELECT DISTINCT semester FROM academic_list ORDER BY id ASC");
}

while ($row = $semResult->fetch_assoc()) {
    $semesters[] = $row['semester'];
}

// 2. HELPER FUNCTION: Get Rating Description
function getRatingDescription($score) {
    if (!$score) return 'N/A';
    if ($score >= 4.50) return 'Outstanding (O)';
    if ($score >= 3.50) return 'Very Satisfactory (VS)';
    if ($score >= 2.50) return 'Satisfactory (S)';
    if ($score >= 1.50) return 'Unsatisfactory (US)';
    return 'Poor (P)';
}

// 3. MAIN QUERY
$sql = "SELECT 
            u.id AS admin_id,
            CONCAT(
                u.first_name, ' ',
                IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(u.middle_name, ' '), ''),
                u.last_name
            ) AS admin_name,
            d.department_name,
            ea.id AS archive_id,
            ea.avg_score,
            ea.file_path,
            ea.date_generated,
            ea.date_sent
        FROM users u
        JOIN departments d ON d.id = u.department_id
        JOIN evaluation_assignments eas ON eas.evaluatee_id = u.id
        LEFT JOIN evaluation_archives ea 
            ON ea.administrator_id = u.id 
           AND ea.school_year = ?
           AND ea.semester = ?
        WHERE eas.status = 'completed'";

if ($dept_filter) {
    $sql .= " AND u.department_id = '$dept_filter'";
}

$sql .= " GROUP BY u.id";

// 4. SORTING LOGIC
switch ($sort_order) {
    case 'score_desc':
        $sql .= " ORDER BY ea.avg_score DESC";
        break;
    case 'score_asc':
        $sql .= " ORDER BY ea.avg_score ASC";
        break;
    default:
        $sql .= " ORDER BY admin_name ASC";
        break;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $school_year, $semester);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports Module</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
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
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title">Evaluation Reports</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">School Year</label>
                        <select name="school_year" class="form-select" onchange="this.form.submit()">
                            <option value="">Choose...</option>
                            <?php foreach ($schoolYears as $sy): ?>
                                <option value="<?= htmlspecialchars($sy) ?>" <?= ($school_year == $sy) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sy) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select">
                            <option value="">Choose...</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= htmlspecialchars($sem) ?>" <?= ($semester == $sem) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sem) ?><?= is_numeric($sem) ? ($sem == 1 ? 'st Sem' : 'nd Sem') : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="dept_filter" class="form-select">
                            <option value="">All Departments</option>
                            <?php
                            $depts = $conn->query("SELECT * FROM departments ORDER BY department_name ASC");
                            while ($d = $depts->fetch_assoc()):
                                $sel = $dept_filter == $d['id'] ? 'selected' : '';
                            ?>
                                <option value="<?= $d['id'] ?>" <?= $sel ?>><?= $d['department_name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Sort Score</label>
                        <select name="sort_order" class="form-select">
                            <option value="">Default (Name)</option>
                            <option value="score_desc" <?= $sort_order == 'score_desc' ? 'selected' : '' ?>>Highest to Lowest</option>
                            <option value="score_asc" <?= $sort_order == 'score_asc' ? 'selected' : '' ?>>Lowest to Highest</option>
                        </select>
                    </div>

                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Administrator</th>
                                        <th>Department</th>
                                        <th>Calculated Score</th>
                                        <th>Rating</th> <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <h2 class="table-avatar">
                                                <a href="#"><?= $row['admin_name'] ?></a>
                                            </h2>
                                        </td>
                                        <td><?= $row['department_name'] ?></td>
                                        <td>
                                            <?php 
                                            if ($row['avg_score']) {
                                                echo '<span class="badge bg-primary" style="font-size:1em;">'.number_format($row['avg_score'], 2).'</span>';
                                            } else {
                                                echo '<span class="text-muted">--</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?= getRatingDescription($row['avg_score']) ?></small>
                                        </td>

                                        <td>
                                            <?php
                                            if ($row['date_sent']) {
                                                echo '<span class="badge bg-success">Sent</span>';
                                            } elseif ($row['date_generated']) {
                                                echo '<span class="badge bg-warning text-dark">Generated</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary">Pending</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end"> 
                                            <form action="generate_pdf.php" method="POST" target="_blank" style="display:inline;">
                                                <input type="hidden" name="admin_id" value="<?= $row['admin_id'] ?>">
                                                <input type="hidden" name="school_year" value="<?= $school_year ?>">
                                                <input type="hidden" name="semester" value="<?= $semester ?>">
                                                
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-check-pdf" title="Generate Secure PDF">
                                                    <i class="fas fa-file-pdf"></i> <?= $row['file_path'] ? 'Regenerate PDF' : 'Generate PDF' ?>
                                                </button>
                                            </form>

                                            <form action="send_email.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="admin_id" value="<?= $row['admin_id'] ?>">
                                                <input type="hidden" name="school_year" value="<?= $school_year ?>">
                                                <input type="hidden" name="semester" value="<?= $semester ?>">
                                                
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success btn-send-email" 
                                                        title="Send Email"
                                                        data-generated="<?= !empty($row['file_path']) ? '1' : '0' ?>">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">No evaluations found.</td></tr>
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

<?php include '../../Layout Components/footer.php'; ?>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$(document).ready(function() {
    toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right", "timeOut": "5000" };
    <?php if ($toastr): ?>
        var type = "<?= $toastr['type'] ?>";
        var message = "<?= addslashes($toastr['message']) ?>";
        if (type === 'success') toastr.success(message);
        else if (type === 'error') toastr.error(message);
        else if (type === 'warning') toastr.warning(message);
    <?php endif; ?>
});
</script>

<script>
$(document).ready(function() {
    // Intercept clicks on the PDF button to check validity first
    $('.btn-check-pdf').on('click', function(e) {
        e.preventDefault(); 
        
        var $btn = $(this);
        var $form = $btn.closest('form');
        var formData = $form.serialize() + '&check_only=1'; // Send the pre-check signal

        $btn.prop('disabled', true); // Prevent double clicks

        $.post('generate_pdf.php', formData, function(response) {
            $btn.prop('disabled', false);

            if (response.status === 'success') {
                // If check passes, submit the form normally to download file
                $form[0].submit(); 
                toastr.success("Downloading Report...");
            } else {
                // If check fails, show Toastr warning WITHOUT opening a new tab
                toastr.warning(response.message, "Validation Failed");
            }
        }, 'json')
        .fail(function() {
            $btn.prop('disabled', false);
            toastr.error("Server connection error.");
        });
    });
});
</script>

<script>
$(document).ready(function() {
    
    // 1. Logic for Generate PDF (Existing)
    $('.btn-check-pdf').on('click', function(e) {
        // ... (Your existing code for PDF generation check) ...
        e.preventDefault(); 
        var $btn = $(this);
        var $form = $btn.closest('form');
        var formData = $form.serialize() + '&check_only=1';

        $btn.prop('disabled', true);

        $.post('generate_pdf.php', formData, function(response) {
            $btn.prop('disabled', false);
            if (response.status === 'success') {
                $form[0].submit(); 
                toastr.success("Downloading Report...");
                
                // OPTIONAL: Update the "Send Email" button in this row to allow sending immediately
                // without refreshing, implies the user now "Has" the report.
                var $row = $btn.closest('tr');
                $row.find('.btn-send-email').attr('data-generated', '1');
                
            } else {
                toastr.warning(response.message, "Validation Failed");
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false);
            toastr.error("Server connection error.");
        });
    });

    // 2. NEW LOGIC: Send Email Button
    $('.btn-send-email').on('click', function(e) {
        e.preventDefault(); // Stop form from submitting
        
        var isGenerated = $(this).attr('data-generated');

        if (isGenerated === '0' || isGenerated === '') {
            // CONDITION NOT MET: Show Warning
            toastr.warning("Please generate the PDF report first to verify its contents before sending.", "Action Required");
        } else {
            // CONDITION MET: Ask for confirmation then submit
            if (confirm('Are you sure you want to email this report to the user?')) {
                $(this).closest('form').submit();
            }
        }
    });

});
</script>

</body>
</html>