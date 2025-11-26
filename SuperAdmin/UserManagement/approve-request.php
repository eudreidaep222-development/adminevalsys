<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php'; // Centralized logging helper

// Friendly field labels
$fieldLabels = [
    "first_name"    => "First Name",
    "middle_name"   => "Middle Name",
    "last_name"     => "Last Name",
    "email"         => "Email",
    "contact_number"=> "Contact Number",
    "department"    => "Department",
    "domain"        => "Domain",
    "position"      => "Position",
    "profile_photo" => "Profile Photo"
];

// Handle approve action
if (isset($_GET['approve_id'])) {
    $requestId = intval($_GET['approve_id']);

    // Fetch the change request
    $stmt = $conn->prepare("SELECT * FROM change_requests WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if ($request) {
        $changes = json_decode($request['changes'], true);

        // --- FIX 1: REMOVED the block that combined names into 'fullName' ---
        // We want to update 'first_name', 'middle_name', 'last_name' columns directly.

        $updateFields = [];
        $updateValues = [];
        $types = "";

        foreach ($changes as $field => $value) {
            if ($field === "password") continue; // Skip password in display/update here if handled separately
            $updateFields[] = "$field = ?";
            $updateValues[] = $value;
            $types .= "s";
        }

        if (!empty($updateFields)) {
            $updateValues[] = $request['user_id'];
            $types .= "i";

            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$updateValues);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Mark request approved
        $stmt = $conn->prepare("UPDATE change_requests SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $stmt->close();

        // LOG ACTION
        log_action($conn, $_SESSION['user_id'], 'Approve Request', "Approved profile changes for User ID: " . $request['user_id'], $request['user_id']);
    }

    header('Location: approve-request.php');
    exit();
}

// Handle reject action
if (isset($_GET['reject_id'])) {
    $requestId = intval($_GET['reject_id']);

    $stmt = $conn->prepare("UPDATE change_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    if ($stmt->execute()) {
        // LOG ACTION
        // We need to fetch the user_id first to log accurately
        $check = $conn->query("SELECT user_id FROM change_requests WHERE id = $requestId");
        $targetId = ($check && $check->num_rows > 0) ? $check->fetch_assoc()['user_id'] : null;
        
        log_action($conn, $_SESSION['user_id'], 'Reject Request', "Rejected profile changes for Request ID: $requestId", $targetId);
    }
    $stmt->close();

    header('Location: approve-request.php');
    exit();
}

// --- FIX 2: UPDATED SELECT QUERY ---
// Replaced u.fullName with CONCAT logic
$stmt = $conn->prepare("
    SELECT cr.id, cr.user_id, cr.changes, cr.status, 
           CONCAT(u.first_name, ' ', IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(u.middle_name, ' '), ''), u.last_name) AS fullName
    FROM change_requests cr
    JOIN users u ON cr.user_id = u.id
    WHERE cr.status = 'pending'
");
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Approve Requests</title>

    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        table td {
            word-break: break-word;
            white-space: normal;
            vertical-align: top;
        }
        .changes-column {
            max-width: 300px;
            word-wrap: break-word;
        }
        .changes-column img {
            display: block;
            margin-top: 5px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 1px solid #ccc;
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
                        <h3 class="page-title">Approve Requests</h3>
                    </div>
                </div>
            </div>

            <div class="card card-table comman-shadow">
                <div class="card-body">
                    <h5 class="card-title">Pending Change Requests</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th class="changes-column">Proposed Changes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($requests as $row): ?>
                                <?php $changes = json_decode($row['changes'], true); ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['fullName']) ?></td>
                                    <td class="changes-column">
                                        <?php
                                        foreach ($changes as $field => $value) {
                                            if ($field === "password") continue;
                                            $label = $fieldLabels[$field] ?? $field;
                                            if ($field === "profile_photo") {
                                                echo "<b>$label:</b><br><img src='" . htmlspecialchars($value) . "' alt='Profile Photo'><br>";
                                            } else {
                                                echo "<b>$label:</b> " . htmlspecialchars($value) . "<br>";
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><?= ucfirst($row['status']) ?></td>
                                    <td>
                                        <a href="?approve_id=<?= $row['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                                        <a href="?reject_id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($requests)): ?>
                                <tr><td colspan="5" class="text-center">No pending requests</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>