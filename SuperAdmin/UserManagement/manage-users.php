<?php
session_start(); // Always first!
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php'; // Centralized logging helper

// -------- CSRF TOKEN (generate if missing) --------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// -------- Search / Filters --------
$search         = isset($_GET['search']) ? trim($_GET['search']) : '';
$department_filter = isset($_GET['department_filter']) ? trim($_GET['department_filter']):'';
$role_filter    = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : '';
$status_filter  = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$archive_filter = isset($_GET['archive_filter']) ? trim($_GET['archive_filter']) : ''; // New archive filter

// -------- Pagination --------
$limit  = 10;
$page   = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// -------- POST: Handle different actions --------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// --- CSRF check ---
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(400);
    die('Invalid CSRF token.');
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

// Only block actions that actually need a user id
if (in_array($action, ['update_user','change_password','archive_user','restore_user'], true) && $id <= 0) {
    die('Invalid user id.');
}

// ✅ Only load $old for actions that need it
$old = null;
if (in_array($action, ['update_user','change_password','archive_user','restore_user'], true)) {
    $original = $conn->prepare(
        "SELECT id, CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) AS fullName, email, role, status FROM users 
         FROM users WHERE id=? LIMIT 1"
    );
    $original->bind_param("i", $id);
    $original->execute();
    $original_result = $original->get_result();
    $old = $original_result->fetch_assoc();
    $original->close();

    if (!$old) {
        die('User not found.');
    }
}


    switch ($action) {
        case 'update_user':
            // --- Sanitize / Validate inputs ---
            $name   = trim($_POST['fullName'] ?? '');
            $email  = trim($_POST['email'] ?? '');
            $role   = trim($_POST['role'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : $old['department_id'];

            if ($name === '' || $email === '') {
                die('Full name and email are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                die('Invalid email address.');
            }

            $allowedRoles   = ['Super Admin', 'Administrator', 'Faculty'];
            $allowedStatus  = ['Active', 'Inactive', 'Pending'];

            if (!in_array($role, $allowedRoles, true)) {
                die('Invalid role.');
            }
            if (!in_array($status, $allowedStatus, true)) {
                die('Invalid status.');
            }

            // --- Update ---
            $stmt = $conn->prepare("UPDATE users 
                            SET fullName=?, email=?, role=?, status=?, department_id=? 
                            WHERE id=?");
            $stmt->bind_param("ssssii", $name, $email, $role, $status, $department_id, $id);
            if (!$stmt->execute()) {
              die("Update failed: " . $stmt->error);
            }
            $stmt->close();

            // --- Build change list for logging ---
            $changes = [];
            if ($old['fullName'] !== $name) {
                $changes[] = "Full Name: '{$old['fullName']}' → '{$name}'";
            }
            if ($old['email'] !== $email) {
                $changes[] = "Email: '{$old['email']}' → '{$email}'";
            }
            if ($old['role'] !== $role) {
                $changes[] = "Role: '{$old['role']}' → '{$role}'";
            }
            if ($old['status'] !== $status) {
                $changes[] = "Status: '{$old['status']}' → '{$status}'";
            }
            if (isset($old['department_id']) && $old['department_id'] !== $department_id) {
                $changes[] = "Department changed to ID: {$department_id}";
            }

            // --- Improved audit log ---
            if (!empty($changes)) {
                $action_name = (count($changes) === 1 && str_starts_with($changes[0], "Status:"))
                    ? "Update Status"
                    : "Updated User";

                log_action(
                    $conn,
                    $_SESSION['id'],   // editor
                    $action_name,           // action
                    $id,                    // affected user
                    $changes,               // changes array
                    implode("; ", $changes) // description (nice readable list)
                );
            }

            header("Location: manage-users.php?message=updated");
            exit;

        case 'change_password':
            $new_password = trim($_POST['new_password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            if ($new_password === '' || $confirm_password === '') {
                die('Password fields are required.');
            }
            if ($new_password !== $confirm_password) {
                die('Passwords do not match.');
            }
            if (strlen($new_password) < 6) {
                die('Password must be at least 6 characters long.');
            }

            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $hashed_password, $id);
            if (!$stmt->execute()) {
                die("Password update failed: " . $stmt->error);
            }
            $stmt->close();

            // Log the password change
            log_action(
                $conn,
                $_SESSION['id'],
                'Change Password',
                $id,
                [],
                "Password changed for user: {$old['fullName']} ({$old['email']})"
            );

            header("Location: manage-users.php?message=password_changed");
            exit;

      case 'archive_user':
        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $id = intval($_POST['id']); // ✅ USE the same key you validated

            // Archive the user (soft delete)
            $stmt = $conn->prepare("UPDATE users SET archived=1, status='Inactive' WHERE id=?");
            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                die("Archive failed: " . $stmt->error);
            }
            $stmt->close();

            // Log the archive action
            log_action(
                $conn,
                $_SESSION['id'],
                'Archive User',
                $id,
                [],
                "User archived: {$old['fullName']} ({$old['email']})"
            );

            header("Location: manage-users.php?message=archived");
            exit;
        } else {
            die("Invalid user id.");
        }

        case 'restore_user':
            // Restore the user from archive
            $stmt = $conn->prepare("UPDATE users SET archived=0, status='Active' WHERE id=?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                die("Restore failed: " . $stmt->error);
            }
            $stmt->close();

            // Log the restore action
            log_action(
                $conn,
                $_SESSION['id'],
                'Restore User',
                $id,
                [],
                "User restored from archive: {$old['fullName']} ({$old['email']})"
            );

            header("Location: manage-users.php?message=restored");
            exit;

        default:
            die('Invalid action.');
    }
}

// -------- Build Query Conditions --------
$conditions = [];
$params     = [];
$types      = '';

if ($search !== '') {
    $conditions[] = "(fullName LIKE ? OR email LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($role_filter !== '') {
    $conditions[] = "role = ?";
    $params[] = $role_filter;
    $types   .= 's';
}
if ($status_filter !== '') {
    $conditions[] = "status = ?";
    $params[] = $status_filter;
    $types   .= 's';
}
if ($department_filter !== '') {
    $conditions[] = "users.department_id = ?";
    $params[] = $department_filter;
    $types   .= 'i';
}
if ($archive_filter !== '') {
    $conditions[] = "archived = ?";
    $params[] = ($archive_filter === 'archived') ? 1 : 0;
    $types   .= 'i';
} else {
    // By default, show only non-archived users
    $conditions[] = "archived = 0";  // no placeholder here
}

$whereSQL = count($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';

// -------- Count for pagination --------
$count_sql = "SELECT COUNT(*) 
              FROM users
              LEFT JOIN departments ON users.department_id = departments.id
              $whereSQL";

$stmt = $conn->prepare($count_sql);

if (!$stmt) {
    die("Prepare failed (COUNT): (" . $conn->errno . ") " . $conn->error);
}

// Only bind if we actually have params
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();

// -------- Base SQL --------
$sql = "SELECT 
            users.id,
            CONCAT(users.first_name, ' ',
                   COALESCE(CONCAT(users.middle_name, ' '), ''),
                   users.last_name) AS fullName,
            users.email,
            users.role,
            departments.department_name AS department,
            users.status,
            users.archived,
            users.created_at
        FROM users
        LEFT JOIN departments ON users.department_id = departments.id
        $whereSQL
        ORDER BY users.created_at DESC, users.id DESC
        LIMIT ?, ?";

// -------- Prepare --------
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed (SELECT): (" . $conn->errno . ") " . $conn->error);
}

// -------- Add LIMIT params --------
// Make sure $params and $types exist for $whereSQL
// Example:
// $params = []; $types = '';
$params2 = $params ?? []; // copy base params or empty array
$types2  = $types ?? '';  // copy base types or empty string

// Add types for LIMIT (always integers)
$types2 .= 'ii';
$params2[] = $offset;  // e.g., 0
$params2[] = $limit;   // e.g., 10

// -------- Bind params --------
if ($params2) {
    // dynamically bind params
    $stmt->bind_param($types2, ...$params2);
}

// -------- Execute --------
$stmt->execute();

// -------- Get results --------
$users = $stmt->get_result();

// -------- Close statement --------
$stmt->close();



// -------- Pagination helpers --------
$total_pages = max((int)ceil($total_users / $limit), 1);
// Helper for safe output
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Manage Users</title>

    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
                        <h3 class="page-title">Manage Users</h3>
                        <?php 
                        $message = $_GET['message'] ?? '';
                        switch($message):
                        case 'updated': ?>
                            <div class="alert alert-success alert-dismissible fade show mt-2 mb-0 alert-auto-dismiss">
                                User updated successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php break;
                        case 'password_changed': ?>
                            <div class="alert alert-success alert-dismissible fade show mt-2 mb-0 alert-auto-dismiss">
                                Password changed successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php break;
                        case 'archived': ?>
                            <div class="alert alert-warning alert-dismissible fade show mt-2 mb-0 alert-auto-dismiss">
                                User archived successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php break;
                        case 'restored': ?>
                            <div class="alert alert-info alert-dismissible fade show mt-2 mb-0 alert-auto-dismiss">
                                User restored successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php break;
                    endswitch; ?>
                    </div>
                    <div class="col-auto">
                        <form class="row g-2" method="get" action="">
                            <div class="col-auto">
                                <input type="text" class="form-control" name="search" placeholder="Search name or email" value="<?= e($search) ?>">
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="role_filter">
                                    <option value="">All Roles</option>
                                    <?php foreach (['Super Admin','Administrator','Faculty'] as $r): ?>
                                        <option value="<?= e($r) ?>" <?= $role_filter===$r ? 'selected':'' ?>><?= e($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="status_filter">
                                    <option value="">All Status</option>
                                    <?php foreach (['Active','Inactive','Pending'] as $s): ?>
                                        <option value="<?= e($s) ?>" <?= $status_filter===$s ? 'selected':'' ?>><?= e($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto flex-grow-1">
                              <select class="form-select filter-select" name="department_filter">
                                <option value="">All Departments</option>
                                <?php
                                // Fetch departments dynamically
                                $departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                                if ($departments && $departments->num_rows > 0):
                                    while ($dept = $departments->fetch_assoc()):
                                ?>
                                    <option value="<?= e($dept['id']) ?>" <?= (isset($_GET['department_filter']) && $_GET['department_filter'] == $dept['id']) ? 'selected' : '' ?>>
                                      <?= e($dept['department_name']) ?>
                                    </option>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                              </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select" name="archive_filter">
                                    <option value="">Active Users</option>
                                    <option value="archived" <?= $archive_filter==='archived' ? 'selected':'' ?>>Archived Users</option>
                                    <option value="all" <?= $archive_filter==='all' ? 'selected':'' ?>>All Users</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search me-1"></i>Filter</button>                                
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Clear Filter</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card card-table comman-shadow">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col">
                            <h5 class="card-title mb-0">User List</h5>
                            <small class="text-muted">
                                <?php
                                $from = ($total_users > 0) ? ($offset + 1) : 0;
                                $to   = min($offset + $limit, $total_users);
                                ?>
                                Showing <?= e($from) ?>–<?= e($to) ?> of <?= e($total_users) ?>
                            </small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover align-middle">
                            <thead class="table-light">
                              <tr>
                                <th class="index">#</th>
                                <th class="col-fullname">Full Name</th>
                                <th class="col-email">Email</th>
                                <th class="col-role">Role</th>
                                <th class="col-dept">Department</th>
                                <th>Status</th>
                                <th>Archive Status</th>
                                <th>Date Created</th>
                                <th class="actions">Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = $offset + 1;
                                if ($users && $users->num_rows > 0):
                                    while ($user = $users->fetch_assoc()):
                                ?>
                                    <tr class="<?= $user['archived'] ? 'table-secondary' : '' ?>" id="userRow<?= e($user['id']) ?>">
                                        <td><?= e($i++) ?></td>
                                        <td><?= e($user['fullName']) ?></td>
                                        <td><?= e($user['email']) ?></td>
                                        <td><span class="badge bg-info"><?= e($user['role']) ?></span></td>
                                        <td class="col-dept" title="<?= e($user['department'] ?? '') ?>">
                                          <?= e($user['department'] ?? '—') ?>
                                        </td>

                                        <td>
                                            <?php
                                            $status = (string)$user['status'];
                                            $statusClass = match($status) {
                                                'Active'   => 'bg-success',
                                                'Inactive' => 'bg-danger',
                                                'Pending'  => 'bg-warning text-dark',
                                                default    => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= e($statusClass) ?>"><?= e($status) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($user['archived']): ?>
                                                <span class="badge bg-dark">Archived</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $created = $user['created_at'] ?? '';
                                            echo $created ? e(date('M d, Y', strtotime($created))) : '<span class="text-muted">—</span>';
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-cog"></i> Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item openEditModal" href="#"
                                                           data-id="<?= e($user['id']) ?>"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#editUserModal">
                                                            <i class="fas fa-edit me-2"></i>Edit User
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item openPasswordModal" href="#"
                                                           data-id="<?= e($user['id']) ?>"
                                                           data-name="<?= e($user['fullName']) ?>"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#changePasswordModal">
                                                            <i class="fas fa-key me-2"></i>Change Password
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php if ($user['archived']): ?>
                                                        <li>
                                                            <a class="dropdown-item text-info openRestoreModal" href="#"
                                                            data-id="<?= e($user['id']) ?>"
                                                            data-action="restore_user"
                                                            data-name="<?= e($user['fullName']) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveRestoreModal">
                                                              <i class="fas fa-undo me-2"></i>Restore User
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item text-danger openDeleteModal" href="#"
                                                              data-id="<?= e($user['id']) ?>"
                                                              data-action="delete_user"
                                                              data-name="<?= e($user['fullName']) ?>"
                                                              data-bs-toggle="modal"
                                                              data-bs-target="#deleteUserModal">
                                                              <i class="fas fa-trash me-2"></i>Delete User
                                                            </a>
                                                        </li>
                                                    <?php else: ?>
                                                        <li>
                                                            <a class="dropdown-item text-warning openArchiveModal" href="#"
                                                            data-id="<?= e($user['id']) ?>"
                                                            data-action="archive_user"
                                                            data-name="<?= e($user['fullName']) ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#archiveRestoreModal">
                                                              <i class="fas fa-archive me-2"></i>Archive User
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="User pagination">
                        <ul class="pagination justify-content-end">
                            <?php
                            // keep filters in links
                            $base_qs = http_build_query([
                                'search' => $search,
                                'role_filter' => $role_filter,
                                'status_filter' => $status_filter,
                                'archive_filter' => $archive_filter,
                            ]);
                            ?>
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= $base_qs ?>&page=<?= max($page-1,1) ?>">Previous</a>
                            </li>
                            <?php for ($p=1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= $base_qs ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= $base_qs ?>&page=<?= min($page+1,$total_pages) ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===========================
     Edit User Modal (single)
=========================== -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" id="closeModalBtn" data-bs-dismiss="modal"></button>
      </div>

      <form id="editUserForm" method="POST" action="manage-users.php" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="id" id="editUserId">

        <div class="modal-body">
          <div class="row g-3">
            <!-- First Name -->
            <div class="col-md-4">
              <label class="form-label">First Name</label>
              <input type="text" class="form-control" name="first_name" id="editFirstName" required>
            </div>

            <!-- Middle Name -->
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input type="text" class="form-control" name="middle_name" id="editMiddleName">
            </div>

            <!-- Last Name -->
            <div class="col-md-4">
              <label class="form-label">Last Name</label>
              <input type="text" class="form-control" name="last_name" id="editLastName" required>
            </div>

            <!-- Email -->
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" id="editEmail" required>
            </div>

            <!-- Role -->
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select class="form-select" name="role" id="editRole" required>
                <?php foreach (['Super Admin','Administrator','Faculty'] as $r): ?>
                  <option value="<?= e($r) ?>"><?= e($r) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Status -->
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="editStatus" required>
                <?php foreach (['Active','Inactive','Pending'] as $s): ?>
                  <option value="<?= e($s) ?>"><?= e($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Department -->
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <select class="form-select" name="department_id" id="editDepartment">
                <option value="">-- Select Department --</option>

                <?php
                $departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                while ($dept = $departments->fetch_assoc()):
                ?>
                  <option value="<?= e($dept['id']) ?>" 
                    <?= (isset($user['department_id']) && $user['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                    <?= e($dept['department_name']) ?>
                  </option>
                <?php endwhile; ?>

                <option value="new">+ Add New Department</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <div class="text-muted small">
            <i class="fas fa-info-circle me-1"></i> Make changes and click “Save changes”.
          </div>
          <div>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- ===========================
     Change Password Modal
=========================== -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="changePasswordForm" method="POST" action="manage-users.php" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="id" id="passwordUserId">

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Changing password for: <strong><span id="passwordUserName"></span></strong>
            </div>

            <div class="mb-3">
                <label for="newPassword" class="form-label">New Password</label>
                <input type="password" class="form-control" name="new_password" id="newPassword" required minlength="6">
                <div class="form-text">Password must be at least 6 characters long.</div>
            </div>

            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required minlength="6">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===========================
     Archive/Restore Modal
=========================== -->
<div class="modal fade" id="archiveRestoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="../Archive/archive-user.php">
        <div class="modal-header">
          <h5 class="modal-title" id="archiveRestoreTitle">Confirm Action</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="archiveRestoreMessage">Are you sure you want to perform this action?</p>
          <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
          <input type="hidden" name="action" id="archiveRestoreAction" value="">
          <input type="hidden" name="user_id" id="archiveRestoreId" value="">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="archiveRestoreBtn">Confirm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===========================
 Delete Modal
=========================== -->
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i> Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="deleteUserMessage"></p>
        <input type="hidden" id="deleteUserId">
        <!-- CSRF Token -->
        <input type="hidden" id="deleteCsrfToken" value="<?= e($csrf_token); ?>">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="deleteUserForm">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Hidden form for deletion -->
<form id="deleteUserForm"></form>


<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
// Load user into Edit modal
document.querySelectorAll('.openEditModal').forEach(button => {
    button.addEventListener('click', function () {
        const userId = this.getAttribute('data-id');

        fetch(`get-user.php?id=${encodeURIComponent(userId)}`)
            .then(res => {
                if (!res.ok) throw new Error('Failed to fetch user');
                return res.json();
            })
            .then(user => {
                document.getElementById('editUserId').value = user.id ?? '';
                document.getElementById('editFullName').value = user.fullName ?? '';
                document.getElementById('editEmail').value = user.email ?? '';
                document.getElementById('editRole').value = user.role ?? 'Faculty';
                document.getElementById('editStatus').value = user.status ?? 'Pending';
                document.getElementById('editDepartment').value = user.department_id ?? '';

            })
            .catch(() => {
                alert('Unable to load user details.');
            });
    });
});

// Populate Change Password modal
document.querySelectorAll('.openPasswordModal').forEach(a => {
    a.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const name = this.getAttribute('data-name') || '';
        document.getElementById('passwordUserId').value = id;
        document.getElementById('passwordUserName').textContent = name;
    });
});
</script>

<!-- Universal Confirmation Modal -->
<div class="modal fade" id="universalConfirmModal" tabindex="-1" aria-labelledby="universalConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border border-warning shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold" id="universalConfirmLabel">
          <i class="fas fa-exclamation-circle me-2"></i>
          <span id="confirmModalTitle">Confirm Action</span>
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

<style>
/* TABLE LAYOUT - Let browser auto-balance columns */
.table {
  width: 100%;
  table-layout: auto; /* allow natural sizing */
  font-size: 0.9rem;
  border-collapse: collapse;
}

/* Universal cell styling */
.table th,
.table td {
  padding: 0.6rem 0.75rem;
  vertical-align: middle;
  white-space: normal;  /* allow wrapping */
  word-break: break-word;
  overflow-wrap: anywhere;
}

/* Allow Department column to take more space dynamically */
.table th.col-dept,
.table td.col-dept {
  min-width: 260px;         /* ensure decent width for long names */
  max-width: 600px;         /* allow wider before wrapping */
  white-space: normal !important;
  word-break: break-word;
  overflow-wrap: anywhere;
}

/* Make Full Name and Email slightly flexible */
.table th.col-fullname, .table td.col-fullname { min-width: 150px; }
.table th.col-email, .table td.col-email { min-width: 180px; max-width: 400px; }

/* Keep Role and Actions compact */
.table th.col-role, .table td.col-role { width: 120px; text-align: center; }
.table th.actions, .table td.actions { width: 100px; text-align: center; white-space: nowrap; }

/* Responsive mobile: Stack rows into blocks */
@media (max-width: 992px) {
  .table thead { display: none; }
  .table, .table tbody, .table tr, .table td { display: block; width: 100%; }
  .table tr { margin-bottom: 0.9rem; border-bottom: 1px solid #ddd; }
  .table td {
    text-align: left;
    position: relative;
    padding-left: 45%;
  }
  .table td::before {
    content: attr(data-label);
    position: absolute;
    left: 1rem;
    top: 0.5rem;
    font-weight: 600;
    white-space: nowrap;
  }
}
</style>


<script>
document.addEventListener("DOMContentLoaded", function() {
  const alerts = document.querySelectorAll('.alert-auto-dismiss');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.classList.add('hide-alert'); // slide + fade
      setTimeout(() => alert.remove(), 600); // remove from DOM
    }, 3000); // 3 sec wait
  });
});
</script>

<!-- Archive Section -->

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modalEl = document.getElementById("archiveRestoreModal");
    const modal = new bootstrap.Modal(modalEl);
    const title = document.getElementById("archiveRestoreTitle");
    const message = document.getElementById("archiveRestoreMessage");
    const confirmBtn = document.getElementById("archiveRestoreBtn");

    const idInput = document.getElementById("archiveRestoreId");
    const actionInput = document.getElementById("archiveRestoreAction");

    function openArchiveRestore(action, id, name) {
        idInput.value = id;
        actionInput.value = action;

        if (action === "archive_user") {
            title.textContent = "Archive User";
            message.textContent = `Are you sure you want to archive ${name}?`;
            confirmBtn.textContent = "Archive";
            confirmBtn.classList.remove("btn-success");
            confirmBtn.classList.add("btn-warning");
        } else if (action === "restore_user") {
            title.textContent = "Restore User";
            message.textContent = `Do you want to restore ${name}?`;
            confirmBtn.textContent = "Restore";
            confirmBtn.classList.remove("btn-warning");
            confirmBtn.classList.add("btn-success");
        }

        modal.show();
    }

    document.querySelectorAll(".openArchiveModal, .openRestoreModal").forEach(btn => {
        btn.addEventListener("click", function () {
            const id = this.getAttribute("data-id");
            const action = this.getAttribute("data-action");
            const name = this.getAttribute("data-name");
            openArchiveRestore(action, id, name);
        });
    });

    confirmBtn.addEventListener("click", function () {
        const userId = idInput.value;
        const action = actionInput.value;
        const csrfToken = '<?php echo $csrf_token; ?>';

        fetch("../Archive/archive-user.php", { // adjust path as needed
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `user_id=${encodeURIComponent(userId)}&action=${encodeURIComponent(action)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === "success") {
                window.location.href = "manage-users.php";
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => {
            console.error("Request failed:", err);
            alert("Unexpected error occurred.");
        });
    });
});
</script>


<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Department</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="newDepartmentName" class="form-control" placeholder="Enter department name">
        <!-- CSRF Token -->
        <input type="hidden" id="csrfToken" value="<?= e($_SESSION['csrf_token']); ?>">
        <small class="text-muted">This will be saved and available in the list.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveDepartmentBtn">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
// Confirm-before-submit pattern + dirty-form guard for Edit modal
(function(){
  const form = document.getElementById('editUserForm');
  const closeBtn = document.getElementById('closeModalBtn');
  const editUserModalEl = document.getElementById('editUserModal');

  if (!form || !closeBtn || !editUserModalEl) return;

  const editUserModal = new bootstrap.Modal(editUserModalEl);
  let isFormDirty = false;

  // Detect form changes
  form.addEventListener('input', () => { isFormDirty = true; });

  // Close with unsaved changes warning
  closeBtn.addEventListener('click', function (e) {
    if (isFormDirty) {
      e.preventDefault();
      if (confirm("You have unsaved changes. Close without saving?")) {
        editUserModal.hide();
        isFormDirty = false;
      }
    }
  });
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById('editUserForm');
  const editUserModal = document.getElementById('editUserModal');
  const exitConfirmModal = new bootstrap.Modal(document.getElementById('exitEditConfirmModal'));
  const confirmExitBtn = document.getElementById('confirmExitEditBtn');

  let isDirty = false;
  let allowClose = false;

  form.addEventListener('input', () => { isDirty = true; });

  editUserModal.addEventListener('hide.bs.modal', function (e) {
    if (isDirty && !allowClose) {
      e.preventDefault(); // Stop closing
      exitConfirmModal.show();
    }
  });

  confirmExitBtn.addEventListener('click', () => {
    allowClose = true;
    bootstrap.Modal.getInstance(editUserModal).hide();
    exitConfirmModal.hide();
    isDirty = false;
    allowClose = false;
  });

  form.addEventListener('submit', () => {
    isDirty = false; // Reset after saving
  });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const csrfToken = '<?php echo $csrfToken; ?>'; // Add CSRF token
    const modalEl = document.getElementById("archiveRestoreModal");
    const modal = new bootstrap.Modal(modalEl);
    const actionInput = document.getElementById("archiveRestoreAction");
    const idInput = document.getElementById("archiveRestoreId");
    const title = document.getElementById("archiveRestoreTitle");
    const message = document.getElementById("archiveRestoreMessage");
    const confirmBtn = document.getElementById("archiveRestoreBtn");

    function openArchiveRestore(action, id, name) {
        idInput.value = id;
        actionInput.value = action;

        if (action === "archive_user") {
            title.textContent = "Archive User";
            message.textContent = `Are you sure you want to archive ${name}?`;
            confirmBtn.textContent = "Archive";
            confirmBtn.classList.remove("btn-success");
            confirmBtn.classList.add("btn-warning");
        } else if (action === "restore_user") {
            title.textContent = "Restore User";
            message.textContent = `Do you want to restore ${name}?`;
            confirmBtn.textContent = "Restore";
            confirmBtn.classList.remove("btn-warning");
            confirmBtn.classList.add("btn-success");
        }

        modal.show();
    }

    document.querySelectorAll(".archive-btn, .restore-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const id = this.getAttribute("data-id");
            const action = this.getAttribute("data-action");
            const name = this.getAttribute("data-name");
            openArchiveRestore(action, id, name);
        });
    });

    confirmBtn.addEventListener("click", function () {
        const userId = idInput.value;
        const action = actionInput.value;

        fetch("archive-user.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `user_id=${encodeURIComponent(userId)}&action=${encodeURIComponent(action)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === "success") {
                alert(res.message);

                // Update table row
                const row = document.getElementById("userRow" + userId);
                if (row) {
                    // Ensure your <td> have classes: status-cell and action-cell
                    const statusCell = row.querySelector(".status-cell");
                    const actionCell = row.querySelector(".action-cell");
                    if (statusCell) statusCell.textContent = res.new_status;
                    if (actionCell) actionCell.innerHTML = res.new_button_html;
                }

                modal.hide();
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => {
            console.error("Request failed:", err);
            alert("Unexpected error occurred.");
        });
    });
});
</script>

<!-- Delete Section -->

<!-- Add Delete Modal handlers -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // First part — populate modal with user info
    document.querySelectorAll(".openDeleteModal").forEach(btn => {
        btn.addEventListener("click", function () {
            const userId = this.getAttribute("data-id");
            const userName = this.getAttribute("data-name");
            document.getElementById("deleteUserId").value = userId;
            document.getElementById("deleteUserMessage").textContent =
                `Are you sure you want to delete ${userName}? This action cannot be undone.`;
        });
    });

    // Second part — delete form submit handler
    document.getElementById("deleteUserForm").addEventListener("submit", function (e) {
        e.preventDefault();

        const userId = document.getElementById("deleteUserId").value;
        if (!userId) {
            alert("Invalid user ID.");
            return;
        }

        const csrfToken = '<?= e($csrf_token) ?>';

        fetch("delete-user.php", { 
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `user_id=${encodeURIComponent(userId)}&csrf_token=${encodeURIComponent(csrfToken)}&action=delete_user`
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === "success") {
                const row = document.getElementById("userRow" + userId);
                if (row) {
                    row.remove();
                }
                bootstrap.Modal.getInstance(document.getElementById("deleteUserModal")).hide();
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => {
            console.error("Request failed:", err);
            alert("Unexpected error occurred.");
        });
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
  const modalEl = document.getElementById("universalConfirmModal");
  const modal = new bootstrap.Modal(modalEl);
  const title = document.getElementById("confirmModalTitle");
  const message = document.getElementById("confirmModalMessage");
  const confirmBtn = document.getElementById("universalConfirmBtn");

  let confirmAction = null; // will hold the function to run

  // Any button with [data-confirm] will trigger the modal
  document.querySelectorAll("[data-confirm]").forEach(btn => {
    btn.addEventListener("click", function (e) {
      e.preventDefault(); // prevent default immediately

      // set modal texts
      title.textContent = this.dataset.confirmTitle || "Confirm Action";
      message.textContent = this.dataset.confirmMessage || "Are you sure you want to proceed?";

      // assign action
      confirmAction = () => {
        if (this.dataset.confirmAction === "submit") {
          this.closest("form").submit();
        } else if (this.dataset.confirmAction === "link") {
          window.location.href = this.getAttribute("href");
        } else if (this.dataset.confirmAction === "function") {
          window[this.dataset.confirmFunction]?.();
        }
      };

      modal.show();
    });
  });

  // When "Yes, Proceed" is clicked → execute stored action
  confirmBtn.addEventListener("click", function () {
    if (confirmAction) confirmAction();
    modal.hide();
  });
});
</script>

<!-- Exit Confirmation Modal -->
<div class="modal fade" id="exitEditConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Unsaved Changes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>You have unsaved changes. Are you sure you want to close without saving?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmExitEditBtn" class="btn btn-warning">Discard Changes</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const deptSelect   = document.getElementById('editDepartment');
  const addDeptModal = new bootstrap.Modal(document.getElementById('addDepartmentModal'));
  const saveBtn      = document.getElementById('saveDepartmentBtn');
  const inputField   = document.getElementById('newDepartmentName');
  const csrfInput    = document.getElementById('csrfToken'); // ✅ get from DOM

  deptSelect.addEventListener('change', function () {
    if (this.value === 'new') {
      addDeptModal.show();
    }
  });

  saveBtn.addEventListener('click', function () {
    const newDept = inputField.value.trim();
    if (!newDept) return alert('Please enter a department name.');

    fetch('add-department.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `department_name=${encodeURIComponent(newDept)}&csrf_token=${encodeURIComponent(csrfInput.value)}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const option = document.createElement('option');
        option.value = data.id;
        option.textContent = newDept;
        option.selected = true;
        deptSelect.insertBefore(option, deptSelect.querySelector('option[value="new"]'));
        addDeptModal.hide();
        inputField.value = '';
      } else {
        alert('Failed to add department: ' + data.message);
      }
    })
    .catch(() => alert('Unexpected error occurred.'));
  });
});
</script>


</body>
</html>