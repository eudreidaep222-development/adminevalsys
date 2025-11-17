<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}
require_once '../../1database_conn/connection.php';

/* === 1. Fetch Audit Logs (with editor + target) === */
$audit_query = "
    SELECT al.*, 
           editor.fullName AS editorName,
           target.fullName AS targetName
    FROM audit_logs al
    LEFT JOIN users editor ON al.user_id = editor.id
    LEFT JOIN users target ON al.target_id = target.id
    ORDER BY al.log_time DESC
";
$audit_result = $conn->query($audit_query);

/* === 2. Fetch Edit Requests === */
$edit_query = "
    SELECT er.*, u.fullName AS requesterName, admin.fullName AS reviewerName
    FROM edit_requests er
    JOIN users u ON er.user_id = u.id
    LEFT JOIN users admin ON er.reviewed_by = admin.id
    ORDER BY er.request_time DESC
";
$edit_result = $conn->query($edit_query);


/* === 3. Fetch Login Attempts === */
$login_query = "
    SELECT la.*, u.fullName 
    FROM login_attempts la
    LEFT JOIN users u ON la.user_id = u.id
    ORDER BY la.attempt_time DESC
";
$login_result = $conn->query($login_query);
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
  <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
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
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#audit">Audit Logs</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit">Edit Requests</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#login">Login Attempts</button>
        </li>
      </ul>

      <div class="tab-content mt-3">
        <!-- === Audit Logs Tab === -->
        <div class="tab-pane fade show active" id="audit">
          <div class="card card-table comman-shadow">
            <div class="card-body">
              <h5 class="card-title">Audit Logs</h5>
              <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>Editor</th>
                      <th>Target</th>
                      <th>Action</th>
                      <th>Details</th>
                      <th>Date/Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1; if ($audit_result && $audit_result->num_rows > 0): ?>
                        <?php while ($row = $audit_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['editorName'] ?? 'System') ?></td>
                                <td><?= htmlspecialchars($row['targetName'] ?? '—') ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($row['action']) ?></span></td>
                                <td>
                                    <button 
                                        class="btn btn-sm btn-secondary view-desc-btn" 
                                        data-editor="<?= htmlspecialchars($row['editorName'] ?? 'System') ?>"
                                        data-target="<?= htmlspecialchars($row['targetName'] ?? '—') ?>"
                                        data-description="<?= htmlspecialchars($row['description']) ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#descModal">
                                        View
                                    </button>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($row['log_time'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No audit logs available.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- === Edit Requests Tab === -->
        <div class="tab-pane fade" id="edit">
          <div class="card card-table comman-shadow">
            <div class="card-body">
              <h5 class="card-title">Edit Requests</h5>
              <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>User</th>
                      <th>Request Type</th>
                      <th>Status</th>
                      <th>Date/Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1; if ($edit_result && $edit_result->num_rows > 0): ?>
                        <?php while ($row = $edit_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['requesterName']) ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($row['request_type']) ?></span></td>
                                <td>
                                    <?php 
                                        switch ($row['status']) {
                                            case 'approved':
                                                echo '<span class="badge bg-success">Approved</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="badge bg-danger">Rejected</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-warning">Pending</span>';
                                                break;
                                        }
                                    ?>
                                </td>
                                <td>
                                    <?= date('M d, Y h:i A', strtotime($row['request_time'])) ?>
                                    <?php if (!empty($row['review_time'])): ?>
                                        <br><small class="text-muted">Reviewed by <?= htmlspecialchars($row['reviewerName'] ?? 'Admin') ?> at <?= date('M d, Y h:i A', strtotime($row['review_time'])) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No edit requests available.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- === Login Attempts Tab === -->
        <div class="tab-pane fade" id="login">
          <div class="card card-table comman-shadow">
            <div class="card-body">
              <h5 class="card-title">Login Attempts</h5>
              <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>#</th>
                      <th>User</th>
                      <th>IP Address</th>
                      <th>Status</th>
                      <th>Date/Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i=1; if ($login_result && $login_result->num_rows > 0): ?>
                      <?php while ($row = $login_result->fetch_assoc()): ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= htmlspecialchars($row['fullName'] ?? 'Unknown User') ?></td>
                          <td><?= htmlspecialchars($row['ip_address']) ?></td>
                          <td>
                            <?php if ($row['status'] === 'success'): ?>
                              <span class="badge bg-success">Success</span>
                            <?php else: ?>
                              <span class="badge bg-danger">Failed</span>
                            <?php endif; ?>
                          </td>
                          <td><?= date('M d, Y h:i A', strtotime($row['attempt_time'])) ?></td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="5" class="text-center">No login attempts recorded.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div> <!-- End Tab Content -->
    </div>
  </div>
</div>

<!-- Modal for Viewing Descriptions -->
<div class="modal fade" id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="descModalLabel">Log Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Editor:</strong> <span id="modalEditor"></span></p>
        <p><strong>Target:</strong> <span id="modalTarget"></span></p>
        <p><strong>Description:</strong></p>
        <div class="alert alert-secondary" id="modalDescription"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Viewing Edit Request Details -->
<div class="modal fade" id="editRequestModal" tabindex="-1" aria-labelledby="editRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRequestModalLabel">Edit Request Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Requester:</strong> <span id="modalRequester"></span></p>
        <p><strong>Request Type:</strong> <span id="modalRequestType"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
        <p><strong>Reviewed by:</strong> <span id="modalReviewer"></span></p>
        <p><strong>Review Time:</strong> <span id="modalReviewTime"></span></p>
        <p><strong>Request Details:</strong></p>
        <div class="alert alert-secondary" id="modalRequestDetails"></div>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
  document.querySelectorAll('.view-desc-btn').forEach(button => {
    button.addEventListener('click', function () {
      document.getElementById('modalEditor').textContent = this.getAttribute('data-editor');
      document.getElementById('modalTarget').textContent = this.getAttribute('data-target');
      document.getElementById('modalDescription').textContent = this.getAttribute('data-description') || 'No description provided.';
    });
  });
</script>

<script>
  document.querySelectorAll('.view-edit-btn').forEach(button => {
    button.addEventListener('click', function () {
        const data = JSON.parse(this.getAttribute('data-request'));
        document.getElementById('modalRequester').textContent = data.requesterName;
        document.getElementById('modalRequestType').textContent = data.request_type;
        document.getElementById('modalStatus').textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        document.getElementById('modalReviewer').textContent = data.reviewerName || '—';
        document.getElementById('modalReviewTime').textContent = data.review_time ? new Date(data.review_time).toLocaleString() : '—';
        document.getElementById('modalRequestDetails').textContent = data.description || 'No details provided.';
    });
});
</script>

</body>
</html>
