<?php
session_start();
require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$isSuperAdmin = ($_SESSION['role'] === 'Super Admin');
$userId = $_SESSION['user_id'];

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();

// --- DEPARTMENT ---
if (isset($_POST['department_id']) && $_POST['department_id'] != $currentUser['department_id']) {
    $changes['department_id'] = intval($_POST['department_id']);
}

// --- POSITION ---
if (isset($_POST['position_id']) && $_POST['position_id'] != $currentUser['position_id']) {
    $changes['position_id'] = intval($_POST['position_id']);
}

$stmt->close();

$changes = [];

/* ---------------------------
       NAME (Full Name)
-----------------------------*/
if (!empty($_POST['first_name']) && !empty($_POST['last_name'])) {

    $first = trim($_POST['first_name']);
    $middle = trim($_POST['middle_name'] ?? '');
    $last = trim($_POST['last_name']);

    $newFullName = trim("$first $middle $last");

    if ($newFullName !== $currentUser['fullName']) {
        $changes['fullName'] = $newFullName;
    }
}

/* ---------------------------
             EMAIL
-----------------------------*/
if (isset($_POST['email']) && $_POST['email'] !== $currentUser['email']) {
    $changes['email'] = trim($_POST['email']);
}

/* ---------------------------
        CONTACT NUMBER
-----------------------------*/
if (isset($_POST['contact_number']) && $_POST['contact_number'] !== $currentUser['contact_number']) {
    $changes['contact_number'] = trim($_POST['contact_number']);
}

/* ---------------------------
            PASSWORD
-----------------------------*/
if (!empty($_POST['password'])) {
    $changes['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

/* ---------------------------
         PROFILE PHOTO
-----------------------------*/
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['tmp_name'] !== '') {

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
        header("Location: profile-settings.php?error=invalid_file_type");
        exit();
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/assets/uploads/profile_photos/";
    $publicPath = "/assets/uploads/profile_photos/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('profile_', true) . "." . $ext;

    $serverPath = $uploadDir . $fileName;
    $dbPath     = $publicPath . $fileName;

    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $serverPath)) {
        $changes['profile_photo'] = $dbPath;
    } else {
        header("Location: profile-settings.php?error=upload_failed");
        exit();
    }
}

/* ---------------------------
 DEPARTMENT & POSITION (IDs)
-----------------------------*/
if (isset($_POST['department']) && $_POST['department'] != $currentUser['department_id']) {
    $changes['department_id'] = intval($_POST['department']);
}

if (isset($_POST['position']) && $_POST['position'] != $currentUser['position_id']) {
    $changes['position_id'] = intval($_POST['position']);
}

/* ------------------------------------------------
   HANDLE SUPER ADMIN BYPASS OR NORMAL USER REQUEST
--------------------------------------------------*/
if (!empty($changes)) {

    if ($isSuperAdmin) {

        // Build dynamic UPDATE query
        $updateParts = [];
        $params = [];
        $types = '';

        foreach ($changes as $col => $val) {
            $updateParts[] = "$col = ?";
            $params[] = $val;
            $types .= 's';
        }

        $params[] = $userId;
        $types .= 'i';

        $sql = "UPDATE users SET " . implode(", ", $updateParts) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        log_action($conn, $userId, "Super Admin instant update", null, $changes, "Super Admin updated own profile");

        header("Location: profile-settings.php?success=superadmin_updated");
        exit();

    } else {
        // Normal User → Save as change request
        $stmt = $conn->prepare("
            INSERT INTO change_requests (user_id, changes, status, requested_at)
            VALUES (?, ?, 'pending', NOW())
        ");

        $changesJson = json_encode($changes);
        $stmt->bind_param("is", $userId, $changesJson);
        $stmt->execute();
        $stmt->close();

        log_action($conn, $userId, "Requested profile changes", null, $changes, "User requested changes");

        header("Location: profile-settings.php?success=pending_approval");
        exit();
    }
}

header("Location: profile-settings.php?success=no_changes");
exit();

?>
