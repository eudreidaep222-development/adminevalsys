<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

$errorMessage = null;
$successMessage = null;

// Pull feedback messages from session (set by create-user.php or others)
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage'];
    unset($_SESSION['successMessage']); // clear after showing
}

if (isset($_SESSION['errorMessage'])) {
    $errorMessage = $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']); // clear after showing
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userFile'])) {
    if ($_FILES['userFile']['error'] === 0) {

        $file = fopen($_FILES['userFile']['tmp_name'], 'r');
        if ($file === false) {
            $errorMessage = "Could not open uploaded file.";
        } else {
            // Read and validate header
            $header = fgetcsv($file);
            if ($header === false) {
                $errorMessage = "CSV file is empty or unreadable.";
            } else {
                $expectedHeader = [
                  'fullName', 'email', 'password', 'role', 'status', 'can_be_evaluated',
                  'phone_number', 'department', 'position'
                ];
                    // Normalize both arrays (lowercase + trim + remove BOM everywhere)
                    $normalize = function($arr) {
                        return array_map(function($h) {
                            // remove BOM anywhere in string
                            $h = preg_replace('/\x{FEFF}|\x{200B}|\x{00A0}/u', '', $h);
                            return strtolower(trim($h));
                        }, $arr);
                    };

                    $normalizedHeader   = $normalize($header);
                    $normalizedExpected = $normalize($expectedHeader);

                    // Compare
                    if ($normalizedHeader !== $normalizedExpected) {
                        $errorMessage = "Invalid CSV format.<br>"
                            ."Expected: " . implode(", ", $expectedHeader) . "<br>"
                            ."Got: " . implode(", ", $header) . "<br>"
                            ."Normalized Got: " . implode(", ", $normalizedHeader);
                    } else {
                    $uploadedCount = 0;

                    while (($row = fgetcsv($file)) !== FALSE) {
                        $fullName = trim($row[0] ?? '');
                        $email = trim($row[1] ?? '');
                        $rawPassword = trim($row[2] ?? '');
                        $role = trim($row[3] ?? '');
                        $status = trim($row[4] ?? '');
                        $can_be_evaluated = isset($row[5]) ? (int)$row[5] : 0;
                        $phone_number = trim($row[6] ?? '');
                        $department   = trim($row[7] ?? '');
                        $position     = trim($row[8] ?? '');

// Add a flag for first login password change
$temp_password_required = 1; // always require change for uploaded users


                        if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($rawPassword)) {
                            continue; // skip invalid row
                        }

                        $password = password_hash($rawPassword, PASSWORD_BCRYPT);

                        $stmt = $conn->prepare("
                          INSERT INTO users 
                              (fullName, email, password, role, status, can_be_evaluated, phone_number, department, position, temp_password_required)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                      ");
                      $stmt->bind_param(
                          "sssssi ss si", 
                          $fullName, $email, $password, $role, $status, $can_be_evaluated, $phone_number, $department, $position, $temp_password_required
                      );


                        if ($stmt->execute()) {
                            $newUserId = $stmt->insert_id;
                            $uploadedCount++;

                            // Log each successful user creation
                            $description = "Uploaded new user via CSV: $fullName ($role)";
                            log_action($conn, $_SESSION['user_id'], 'CSV Upload User', $newUserId, null, $description);

                            // OTP for pending accounts
                            if (strtolower($status) === 'pending') {
                                $otp = rand(100000, 999999);
                                $otpStmt = $conn->prepare("
                                    INSERT INTO otp_verifications (user_id, otp_code, expires_at) 
                                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
                                ");
                                $otpStmt->bind_param("is", $newUserId, $otp);
                                $otpStmt->execute();
                                $otpStmt->close();

                                // Email OTP
                                $subject = "Your Verification Code";
                                $message = "Hello $fullName,\n\nYour OTP is: $otp\nThis code will expire in 10 minutes.\n\n- Admin Eval System";
                                $headers = "From: no-reply@yourdomain.com\r\n";
                                mail($email, $subject, $message, $headers);
                            }
                        } else {
                            error_log("CSV Upload Error for $email: " . $stmt->error);
                        }

                        $stmt->close();
                    } // while loop end

                    if ($uploadedCount > 0) {
                        $successMessage = "$uploadedCount users uploaded successfully. Pending accounts were sent OTP emails if applicable.";
                    } else {
                        $errorMessage = "No valid users were uploaded. Please check your CSV.";
                    }
                }
            }
            if ($file && is_resource($file)) {
                fclose($file);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Upload Users</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                        <h3 class="page-title">Upload Users</h3>
                    </div>
                </div>
            </div>

            <!-- Bulk Upload -->
            <div class="card card-table comman-shadow">
                <div class="card-body">
                    <h5 class="card-title">Bulk Upload User Data (CSV)</h5>
                    <a href="user_template.csv" download class="btn btn-info mb-3">Download CSV Template</a>
                    <form id="csvUploadForm" action="upload-users.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="userFile" class="form-label">Choose CSV File</label>
                            <input type="file" class="form-control" id="userFile" name="userFile" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            </div>

            <!-- Single User Creation -->
            <div class="card card-table comman-shadow mt-4">
                <div class="card-body">
                    <h5 class="card-title">Create Single User</h5>
                    <form id="singleUserForm" action="create-user.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="fullName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Faculty">Faculty</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Can Be Evaluated</label>
                            <select class="form-select" name="can_be_evaluated" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Phone Number</label>
                          <input type="text" class="form-control" name="phone_number">
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Department</label>
                          <input type="text" class="form-control" name="department">
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Position</label>
                          <input type="text" class="form-control" name="position">
                        </div>
                        <button type="submit" class="btn btn-success">Create User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Universal Confirmation Modal -->
<div class="modal fade" id="universalConfirmModal" tabindex="-1" aria-labelledby="universalConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border border-warning shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold" id="universalConfirmLabel">
          <i class="fas fa-exclamation-circle me-2"></i><span id="confirmModalTitle">Confirm Action</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p class="fs-6 mb-0" id="confirmModalMessage">Are you sure you want to proceed with this action?</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="universalConfirmBtn" class="btn btn-warning">Yes, Proceed</button>
      </div>
    </div>
  </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="feedbackLabel">System Message</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center" id="feedbackBody"></div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>


<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script>
const universalModalEl = document.getElementById('universalConfirmModal');
const universalModal = new bootstrap.Modal(universalModalEl);
let confirmActionCallback = null;

// Universal modal helper
function showUniversalModal(title, message, onConfirmCallback) {
  document.getElementById('confirmModalTitle').textContent = title;
  document.getElementById('confirmModalMessage').textContent = message;
  confirmActionCallback = onConfirmCallback;
  universalModal.show();
}

// CSV Upload confirm
document.getElementById('csvUploadForm').addEventListener('submit', function(e) {
  e.preventDefault();
  showUniversalModal(
    "Confirm CSV Upload",
    "Are you sure you want to upload this CSV file?",
    () => { this.submit(); }
  );
});

// Single User Creation confirm
document.getElementById('singleUserForm').addEventListener('submit', function(e) {
  e.preventDefault();
  showUniversalModal(
    "Confirm User Creation",
    "Are you sure all the details entered are correct?",
    () => { this.submit(); }
  );
});

// Confirm button
document.getElementById('universalConfirmBtn').addEventListener('click', function () {
  if (typeof confirmActionCallback === 'function') {
    confirmActionCallback();
    universalModal.hide(); // properly close modal
  }
});
</script>

<?php if ($errorMessage): ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('feedbackBody').textContent = "<?= addslashes($errorMessage) ?>";
    var modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    modal.show();
  });
</script>
<?php endif; ?>

<?php if ($successMessage): ?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('feedbackBody').textContent = "<?= addslashes($successMessage) ?>";
    var modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    modal.show();
  });
</script>
<?php endif; ?>


</body>
</html>
