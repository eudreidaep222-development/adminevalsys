<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $status = 'active'; // default status
    $can_be_evaluated = ($role === 'Administrator') ? 1 : 0;

    if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password) || empty($role)) {
        $_SESSION['errorMessage'] = "All fields are required and must be valid.";
        header('Location: upload-users.php');
        exit();
    }

    // 🔹 Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['errorMessage'] = "The email '$email' is already registered. Please use another email.";
        $checkStmt->close();
        header('Location: upload-users.php');
        exit();
    }
    $checkStmt->close();

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        INSERT INTO users (fullName, email, password, role, status, can_be_evaluated)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssi", $fullName, $email, $hashedPassword, $role, $status, $can_be_evaluated);

    if ($stmt->execute()) {
        $newUserId = $stmt->insert_id;

        // Log action
        $description = "Created single user: $fullName ($role)";
        log_action($conn, $_SESSION['user_id'], 'Create User', $newUserId, null, $description);

        $_SESSION['successMessage'] = "User $fullName has been created successfully.";
    } else {
        $_SESSION['errorMessage'] = "Error creating user: " . $stmt->error;
    }

    $stmt->close();
    header('Location: upload-users.php');
    exit();
}
