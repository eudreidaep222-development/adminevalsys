<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';
$isSuperAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'super admin';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT u.*, 
           d.department_name AS department, 
           p.position_name AS position, 
           dom.domain_name AS domain
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN positions p ON u.position_id = p.id
    LEFT JOIN domains dom ON p.domain_id = dom.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$firstName = $middleName = $lastName = "";
if (!empty($user['fullName'])) {
    $nameParts = explode(" ", $user['fullName']);
    $firstName = $nameParts[0] ?? "";
    $lastName = end($nameParts);
    if (count($nameParts) > 2) {
        $middleName = implode(" ", array_slice($nameParts, 1, -1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Profile Settings</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <style>
        .profile-header img:hover {
            cursor: pointer;
            opacity: 0.85;
            transition: 0.3s;
        }
        .badge-domain {
            background: linear-gradient(90deg,#4e73df,#1cc88a);
            color: #fff;
        }
        .badge-domain.empty {
            background: #dc3545 !important;
        }
    </style>
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
                        <h3 class="page-title"><i class="feather-user"></i> Profile Settings</h3>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- LEFT COLUMN -->
                <div class="col-lg-4 mb-3">
                    <div class="card shadow-sm text-center">
                        <div class="card-body">
                            <div class="profile-header mb-3">
                                <img src="<?= !empty($user['profile_photo']) ? htmlspecialchars($user['profile_photo']) : '/assets/img/user-placeholder.png' ?>" 
                                    class="rounded-circle shadow" style="width:120px; height:120px;">
                                <h4 class="mt-2"><?= htmlspecialchars($firstName . ' ' . $lastName) ?></h4>
                                <p class="text-muted"><?= htmlspecialchars($user['position'] ?? '') ?></p>
                                <span class="badge badge-domain <?= empty($user['domain']) ? 'empty' : '' ?>">
                                    <?= htmlspecialchars($user['domain'] ?? 'No Domain') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-3">
                        <div class="card-body">
                            <h6>Profile Overview</h6>
                            <ul class="list-unstyled mb-0">
                                <li><strong>Department:</strong> <?= htmlspecialchars($user['department'] ?? '-') ?></li>
                                <li><strong>Domain:</strong> <?= htmlspecialchars($user['domain'] ?? '-') ?></li>
                                <li><strong>Position:</strong> <?= htmlspecialchars($user['position'] ?? '-') ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form action="/Shared/Settings/profile-settings-save.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">

                                <!-- TABS -->
                                <ul class="nav nav-tabs mb-3">
                                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#personal">Personal Info</a></li>
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#job">Job Info</a></li>
                                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#security">Security</a></li>
                                </ul>

                                <div class="tab-content">
                                    <!-- PERSONAL INFO -->
                                    <div class="tab-pane fade show active" id="personal">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>First Name</label>
                                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($firstName) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Last Name</label>
                                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($lastName) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Middle Name</label>
                                                <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($middleName) ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Contact Number</label>
                                                <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label>Email</label>
                                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label>Profile Photo</label>
                                                <input type="file" name="profile_photo" accept="image/*" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- JOB INFO -->
                                    <div class="tab-pane fade" id="job">
                                        <div class="row">
                                            <div class="mb-3">
                                                <label for="domainSelect">Domain:</label>
                                                <select id="domainSelect" class="form-control">
                                                    <option value="">Select Domain</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="departmentSelect">Department:</label>
                                                <select id="departmentSelect" name="department_id" class="form-control" required>
                                                    <option value="">Select Department</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="positionSelect">Position:</label>
                                                <select id="positionSelect" name="position_id" class="form-control" required>
                                                    <option value="">Select Position</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SECURITY -->
                                    <div class="tab-pane fade" id="security">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Password <small>(leave blank to keep unchanged)</small></label>
                                                <input type="password" name="password" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                    <?php if (!$isSuperAdmin): ?>
                                        <small class="text-muted">Changes will be saved after admin approval.</small>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- row -->
        </div> <!-- container-fluid -->
    </div> <!-- page-wrapper -->
</div> <!-- main-wrapper -->

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
<?php if (isset($_GET['error'])): ?>
    let msg = '';
    switch("<?= $_GET['error'] ?>") {
        case 'invalid_file_type': msg = 'Invalid file type. Only JPG, PNG, WEBP allowed.'; break;
        case 'upload_failed': msg = 'Failed to upload profile photo. Please try again.'; break;
    }
    if(msg) toastr.error(msg);
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    toastr.success('Profile changes requested successfully!');
<?php endif; ?>
</script>

<script>
    feather.replace();

    // Domain badge color toggle
    const domainBadge = document.querySelector('.badge-domain');
    if (domainBadge.textContent.trim() === 'No Domain' || domainBadge.textContent.trim() === '') {
        domainBadge.classList.add('empty');
    }
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    fetch("get-options.php")
        .then(res => res.json())
        .then(data => {

            const domainSelect = document.getElementById("domainSelect");
            const deptSelect = document.getElementById("departmentSelect");
            const posSelect = document.getElementById("positionSelect");

            // Preload user's current values (sent from PHP)
            const currentDepartment = "<?= $user['department_id'] ?>";
            const currentPosition = "<?= $user['position_id'] ?>";

            // --- LOAD DOMAIN OPTIONS ---
            data.domains.forEach(d => {
                domainSelect.innerHTML += `<option value="${d.id}">${d.domain_name}</option>`;
            });

            // --- LOAD DEPARTMENTS ---
            data.departments.forEach(dep => {
                let selected = dep.id == currentDepartment ? "selected" : "";
                deptSelect.innerHTML += `<option value="${dep.id}" ${selected}>${dep.department_name}</option>`;
            });

            // --- INITIAL LOAD OF POSITIONS ---
            function loadPositions(filterDomain = null) {
                posSelect.innerHTML = `<option value="">Select Position</option>`;
                data.positions.forEach(p => {
                    if (filterDomain === null || p.domain_id == filterDomain) {
                        let selected = p.id == currentPosition ? "selected" : "";
                        posSelect.innerHTML += `<option value="${p.id}" ${selected}>${p.position_name}</option>`;
                    }
                });
            }

            // Load position with user’s current domain auto-selected
            const userPosition = data.positions.find(p => p.id == currentPosition);
            if (userPosition) {
                domainSelect.value = userPosition.domain_id;
                loadPositions(userPosition.domain_id);
            } else {
                loadPositions(); // fallback
            }

            // When user selects a domain
            domainSelect.addEventListener("change", function () {
                loadPositions(this.value);
            });
        });
});
</script>

</body>
</html>
