<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function isSuperAdmin() {
    return $_SESSION['user_role'] === 'Super Admin';
}

function isAdministrator() {
    return $_SESSION['user_role'] === 'Administrator';
}

function isFaculty() {
    return $_SESSION['user_role'] === 'Faculty';
}

function redirectByRole() {
    if (!isAuthenticated()) {
        header("Location: /index.php");
        exit();
    }

    switch ($_SESSION['user_role']) {
        case 'Super Admin':
            header("Location: /SuperAdmin/Dashboard/home.php");
            break;
        case 'Administrator':
            header("Location: /Admin/Dashboard/home.php");
            break;
        case 'Faculty':
            header("Location: /Faculty/Dashboard/home.php");
            break;
        default:
            header("Location: /index.php");
    }
    exit();
}
