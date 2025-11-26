<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}
require_once '../../1database_conn/connection.php';

/* === 1. Handle Approve/Reject Actions === */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'], $_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $actionType = $_POST['action_type'] === 'approve' ? 'approved' : 'rejected';
    $adminId = $_SESSION['user_id'];

    // Update the request status
    $stmt = $conn->prepare("UPDATE edit_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $actionType, $requestId);
    $stmt->execute();

    // Log the decision in audit logs
    $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
    $logAction = ucfirst($actionType) . " Edit Request";
    $logDescription = "Super Admin {$_SESSION['fullName']} {$actionType} edit request ID {$requestId}";
    $logStmt->bind_param("iss", $adminId, $logAction, $logDescription);
    $logStmt->execute();

    // Redirect to avoid form resubmission
    header("Location: activity-log.php?tab=edit");
    exit();
}

/* === 2. Fetch Logs === */
$audit_result = $conn->query("
    SELECT al.*, u.fullName 
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.log_time DESC
");

$edit_result = $conn->query("
    SELECT er.*, u.fullName 
    FROM edit_requests er
    JOIN users u ON er.user_id = u.id
    ORDER BY er.request_time DESC
");

$login_result = $conn->query("
    SELECT la.*, u.fullName 
    FROM login_attempts la
    LEFT JOIN users u ON la.user_id = u.id
    ORDER BY la.attempt_time DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
  <title>Activity Logs</title>
  <link rel="shortcut icon" href="../../assets/img/logo-small.png">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
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
            <h3 class="page-title">Activity Logs</h3>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link <?= ($_GET['tab'] ?? '') !== 'edit' && ($_GET['tab'] ?? '') !== 'login' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#audit">Audit Logs</button></li>
        <li class="nav-item"><button class="nav-link <?= ($_GET['tab'] ?? '') === 'edit' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#edit">Edit Requests</button></li>
        <li class="nav-item"><button class="nav-link <?= ($_GET['tab'] ?? '') === 'login' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#login">Login Attempts</button></li>
      </ul>

      <div class="tab-content mt-3">
        <!-- === Audit Logs Tab === -->
        <div class="tab-pane fade <?= ($_GET['tab'] ?? '') !== 'edit' && ($_GET['tab'] ?? '') !== 'login' ? 'show active' : '' ?>" id="audit">
          <?php include 'partials/audit-table.php'; ?>
        </div>

        <!-- === Edit Requests Tab === -->
        <div class="tab-pane fade <?= ($_GET['tab'] ?? '') === 'edit' ? 'show active' : '' ?>" id="edit">
          <div class="card card-table comman-shadow">
            <div class="card-body">
              <h5 class="card-title">Edit Requests</h5>
              <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>User</th>
                      <th>Field</th>
                      <th>Old Value</th>
                      <th>New Value</th>
                      <th>Status</th>
                      <th>Request Time</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i=1; if ($edit_result && $edit_result->num_rows > 0): ?>
                      <?php while ($row = $edit_result->fetch_assoc()): ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= htmlspecialchars($row['fullName']) ?></td>
                          <td><?= htmlspecialchars($row['field']) ?></td>
                          <td><?= htmlspecialchars($row['old_value']) ?></td>
                          <td><?= htmlspecialchars($row['new_value']) ?></td>
                          <td>
                            <?php
                              $badgeClass = match($row['status']) {
                                'pending' => 'bg-warning',
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-secondary'
                              };
                              echo "<span class='badge $badgeClass'>".htmlspecialchars($row['status'])."</span>";
                            ?>
                          </td>
                          <td><?= date('M d, Y h:i A', strtotime($row['request_time'])) ?></td>
                          <td>
                            <?php if ($row['status'] === 'pending'): ?>
                              <button class="btn btn-success btn-sm" onclick="showConfirmModal('approve', <?= $row['id'] ?>)">Approve</button>
                              <button class="btn btn-danger btn-sm" onclick="showConfirmModal('reject', <?= $row['id'] ?>)">Reject</button>
                            <?php else: ?>
                              <em>N/A</em>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="8" class="text-center">No edit requests found.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- === Login Attempts Tab === -->
        <div class="tab-pane fade <?= ($_GET['tab'] ?? '') === 'login' ? 'show active' : '' ?>" id="login">
          <?php include 'partials/login-table.php'; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Universal Confirm Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border border-warning">
      <div class="modal-header bg-warning">
        <h5 class="modal-title fw-bold">Confirm Action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p id="confirmActionText">Are you sure you want to proceed?</p>
      </div>
      <div class="modal-footer justify-content-center">
        <form method="POST" id="confirmActionForm">
          <input type="hidden" name="request_id" id="confirmRequestId">
          <input type="hidden" name="action_type" id="confirmActionType">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Yes, Proceed</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function showConfirmModal(action, requestId) {
    document.getElementById('confirmActionType').value = action;
    document.getElementById('confirmRequestId').value = requestId;
    document.getElementById('confirmActionText').textContent =
        `Are you sure you want to ${action} this edit request?`;
    new bootstrap.Modal(document.getElementById('confirmActionModal')).show();
}
</script>
</body>
</html>
