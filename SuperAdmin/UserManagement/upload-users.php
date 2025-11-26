<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

$errorMessage = null;
$successMessage = null;
$rowErrors = []; // Capture errors per row

// --- Fetch Dropdown Data ---
$departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
$positions   = $conn->query("SELECT id, position_name FROM positions ORDER BY position_name ASC"); 
$domains     = $conn->query("SELECT id, domain_name FROM domains ORDER BY domain_name ASC");

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =========================================================
    // 1. CSV BULK UPLOAD LOGIC (INTEGRATED)
    // =========================================================
    if (isset($_FILES['userFile'])) {
        if ($_FILES['userFile']['error'] === 0) {
            $file = fopen($_FILES['userFile']['tmp_name'], 'r');
            if ($file === false) {
                $errorMessage = "Could not open uploaded file.";
            } else {
                $header = fgetcsv($file);
                if ($header === false) {
                    $errorMessage = "CSV file is empty or unreadable.";
                } else {
                    $expectedHeader = [
                        'first_name','middle_name','last_name','email','Password','role','status','can_be_evaluated',
                        'temp_password_required','account_locked','archived','profile_photo','department_id','position_id',
                        'domain_id','contact_number','otp_code','otp_expiration','otp_verified'
                    ];

                    $normalize = function($arr) {
                        return array_map(function($h) {
                            $h = preg_replace('/\x{FEFF}|\x{200B}|\x{00A0}/u', '', $h);
                            return strtolower(trim($h));
                        }, $arr);
                    };

                    if ($normalize($header) !== $normalize($expectedHeader)) {
                        $errorMessage = "<strong>CSV Header Mismatch.</strong><br>Expected: " . implode(", ", $expectedHeader);
                    } else {
                        $uploadedCount = 0;
                        $rowNumber = 1;

                        while (($row = fgetcsv($file)) !== FALSE) {
                            $rowNumber++;
                            // ... Data Retrieval ...
                            $firstName = trim($row[0] ?? '');
                            $middleName = trim($row[1] ?? '');
                            $lastName = trim($row[2] ?? '');
                            $email = trim($row[3] ?? '');
                            $rawPassword = trim($row[4] ?? '');

                            if (empty($firstName) || empty($lastName) || empty($email) || empty($rawPassword)) {
                                $rowErrors[] = "Row $rowNumber: Missing First, Last Name, Email, or Password.";
                                continue;
                            }

                            $role = trim($row[5] ?? '');
                            $status = trim($row[6] ?? '');
                            $can_be_evaluated = (int)($row[7] ?? 0);
                            $temp_pass = (int)($row[8] ?? 1); 
                            $locked = (int)($row[9] ?? 0);
                            $archived = (int)($row[10] ?? 0);
                            $photo = trim($row[11] ?? '');
                            $dept_id = (!empty($row[12])) ? (int)$row[12] : NULL;
                            $pos_id = (!empty($row[13])) ? (int)$row[13] : NULL;
                            $dom_id = (!empty($row[14])) ? (int)$row[14] : NULL;
                            $contact = trim($row[15] ?? '');
                            $otp_code = !empty($row[16]) ? trim($row[16]) : NULL;
                            $otp_exp = !empty($row[17]) ? trim($row[17]) : NULL;
                            $otp_ver = !empty($row[18]) ? trim($row[18]) : 'no';

                            $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);

                            $stmt = $conn->prepare("
                                INSERT INTO users 
                                (first_name,middle_name,last_name,email,password,role,status,can_be_evaluated,temp_password_required,
                                account_locked,archived,profile_photo,department_id,position_id,domain_id,contact_number,otp_code,otp_expiration,otp_verified)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");

                            $stmt->bind_param("sssssssiiiissiiiss", $firstName, $middleName, $lastName, $email, $hashedPassword, $role, $status, $can_be_evaluated, $temp_pass, $locked, $archived, $photo, $dept_id, $pos_id, $dom_id, $contact, $otp_code, $otp_exp, $otp_ver);
                            
                            try {
                                if ($stmt->execute()) { 
                                    $uploadedCount++; 
                                    // --- LOG ACTION: Bulk Entry ---
                                    $newUserId = $conn->insert_id;
                                    log_action($conn, $_SESSION['user_id'], 'Create User (Bulk)', "Created user via CSV: $firstName $lastName ($email)", $newUserId);
                                } else { 
                                    $rowErrors[] = "Row $rowNumber ($email): " . $stmt->error; 
                                }
                            } catch (Exception $e) { 
                                $rowErrors[] = "Row $rowNumber ($email): " . $e->getMessage(); 
                            }
                            $stmt->close();
                        }

                        if ($uploadedCount > 0) {
                            $successMessage = "$uploadedCount users uploaded successfully.";
                            // --- LOG ACTION: Bulk Summary ---
                            log_action($conn, $_SESSION['user_id'], 'Bulk Upload', "Completed bulk upload of $uploadedCount users.");
                            
                            if (!empty($rowErrors)) {
                                $successMessage .= " <br><strong>However, some rows failed:</strong><br>" . implode("<br>", array_slice($rowErrors, 0, 5));
                            }
                        } else {
                            $errorMessage = "<strong>No valid users found.</strong>";
                            if (!empty($rowErrors)) $errorMessage .= "<br><br><strong>Details:</strong><br>" . implode("<br>", array_slice($rowErrors, 0, 10));
                        }
                    }
                }
                fclose($file);
            }
        }
    }

    // =========================================================
    // 2. SINGLE USER CREATION LOGIC (INTEGRATED)
    // =========================================================
    elseif (isset($_POST['create_single_user'])) {
        $firstName = trim($_POST['first_name']);
        $middleName = trim($_POST['middle_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $rawPassword = trim($_POST['password']);
        $status = $_POST['status'];
        $can_be_evaluated = (int)$_POST['can_be_evaluated'];
        $contact = trim($_POST['contact_number']);
        $dept_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : NULL;
        $pos_id = !empty($_POST['position_id']) ? (int)$_POST['position_id'] : NULL;
        $dom_id = !empty($_POST['domain_id']) ? (int)$_POST['domain_id'] : NULL;
        
        // Force temp_password_required to 1 (Yes)
        $temp_pass = 1;

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($rawPassword) || empty($role)) {
            $errorMessage = "All required fields (*) must be filled.";
        } 
        // Strict Phone Number Validation (+639XXXXXXXXX)
        elseif (!preg_match('/^\+639\d{9}$/', $contact)) {
            $errorMessage = "Invalid phone format. Must start with +639 followed by 9 digits.";
        }
        else {
            // Check duplicate email
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errorMessage = "Email ($email) already exists.";
            } else {
                $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("
                    INSERT INTO users 
                    (first_name,middle_name,last_name,email,password,role,status,can_be_evaluated,temp_password_required,
                    department_id,position_id,domain_id,contact_number,otp_verified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'no')
                ");

                $stmt->bind_param(
                    "sssssssiiiiss",
                    $firstName, $middleName, $lastName, $email, $hashedPassword, $role, $status, 
                    $can_be_evaluated, $temp_pass, $dept_id, $pos_id, $dom_id, $contact
                );
                
                if ($stmt->execute()) {
                    $newUserId = $conn->insert_id;
                    
                    // --- LOG ACTION: Single User ---
                    $desc = "Created single user: $firstName $lastName ($role)";
                    log_action($conn, $_SESSION['user_id'], 'Create User', $desc, $newUserId);
                    
                    $successMessage = "User $firstName $lastName created successfully!";
                } else {
                    $errorMessage = "Database Error: " . $stmt->error;
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upload/Create Users</title>
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
                <h3 class="page-title">Manage Users</h3>
            </div>

            <div class="card card-table comman-shadow mb-4">
                <div class="card-body">
                    <h5 class="card-title">Bulk Upload (CSV)</h5>
                    <a href="user_template.csv" download class="btn btn-info mb-3">Download Template</a>
                    <form id="csvUploadForm" action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="userFile" accept=".csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload CSV</button>
                    </form>
                </div>
            </div>

            <div class="card card-table comman-shadow">
                <div class="card-body">
                    <h5 class="card-title">Create Single User</h5>
                    <form id="singleUserForm" action="" method="POST">
                        <input type="hidden" name="create_single_user" value="1">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" name="role" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Administrator">Administrator</option>
                                    <option value="Faculty">Faculty</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Can Be Evaluated</label>
                                <select class="form-select" name="can_be_evaluated" required>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department_id">
                                    <option value="">-- Select Department --</option>
                                    <?php while($row = $departments->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['department_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position</label>
                                <select class="form-select" name="position_id">
                                    <option value="">-- Select Position --</option>
                                    <?php while($row = $positions->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['position_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Domain</label>
                                <select class="form-select" name="domain_id">
                                    <option value="">-- Select Domain --</option>
                                    <?php while($row = $domains->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['domain_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_number" placeholder="+639XXXXXXXXX" maxlength="13" required>
                                <small class="text-muted">Format: +639XXXXXXXXX</small>
                            </div>
                            
                        </div>
                        <button type="submit" class="btn btn-success mt-3">Create User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
<?php if($errorMessage): ?>
    toastr.error(`<?= $errorMessage ?>`, 'Error', {closeButton: true, progressBar: true});
<?php endif; ?>
<?php if($successMessage): ?>
    toastr.success(`<?= $successMessage ?>`, 'Success', {closeButton: true, progressBar: true});
<?php endif; ?>

// ====================================================
// STRICT PHONE VALIDATION (+639...)
// ====================================================
const phoneInput = document.querySelector('input[name="contact_number"]');

// 1. Prevent non-numeric keys (Allow only + at start, then digits)
phoneInput.addEventListener('keypress', function(e) {
    if (e.which >= 48 && e.which <= 57) return;
    if (e.which === 43 && this.value.length === 0) return;
    e.preventDefault();
    toastr.warning('Only numbers allowed (after +639)', 'Invalid Input', {timeOut: 2000});
});

// 2. Real-time cleanup & Enforcement
phoneInput.addEventListener('input', function(e) {
    let val = this.value;
    if (!val.startsWith('+63')) {
        let clean = val.replace(/[^0-9]/g, '');
        if (clean.startsWith('09')) clean = clean.substring(1); 
        if (clean.startsWith('63')) clean = clean.substring(2); 
        if (clean.length > 0) this.value = '+63' + clean;
    }
});

// 3. Final Validation on Submit
document.getElementById('singleUserForm').addEventListener('submit', function(e) {
    const phone = phoneInput.value.trim();
    const regex = /^\+639\d{9}$/;
    if(!regex.test(phone)){
        e.preventDefault();
        toastr.error('Invalid phone number. Must be in format: +639XXXXXXXXX', 'Validation Error', {closeButton:true, progressBar:true});
        phoneInput.focus();
    }
});
</script>
</body>
</html>