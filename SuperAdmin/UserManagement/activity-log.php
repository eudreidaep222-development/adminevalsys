<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}
require_once '../../1database_conn/connection.php';

// --- HELPER: Parse User Agent for Device Name ---
// --- HELPER: Parse User Agent for Device Name (Improved) ---
function getDeviceIconAndName($userAgent) {
    $userAgent = strtolower($userAgent);
    $icon = 'fa-desktop'; // Default icon
    $name = 'Desktop';    // Default name

    // 1. Check for Windows (Most common laptop)
    if (strpos($userAgent, 'windows') !== false) {
        $name = 'Windows PC';
        $icon = 'fa-microsoft';
    } 
    // 2. Check for Mac (Macbooks/iMacs)
    elseif (strpos($userAgent, 'macintosh') !== false) {
        $name = 'Mac';
        $icon = 'fa-apple';
    } 
    // 3. Check for iPhone/iPad
    elseif (strpos($userAgent, 'iphone') !== false || strpos($userAgent, 'ipad') !== false) {
        $name = 'iOS Device';
        $icon = 'fa-apple';
    } 
    // 4. Check for Android
    elseif (strpos($userAgent, 'android') !== false) {
        // Distinguish between Mobile Android and generic Android (Tablets/Chromebooks)
        if (strpos($userAgent, 'mobile') !== false) {
            $name = 'Android Mobile';
            $icon = 'fa-mobile-alt';
        } else {
            $name = 'Android Tablet/Chromebook';
            $icon = 'fa-tablet-alt';
        }
    } 
    // 5. Check for Linux (Ubuntu, Fedora, etc.)
    // Important: Android is based on Linux, so we check Linux LAST to avoid confusion.
    elseif (strpos($userAgent, 'linux') !== false) {
        $name = 'Linux Desktop';
        $icon = 'fa-linux';
    }

    return ['name' => $name, 'icon' => $icon];
}

// --- LOGIC: Determine Active Tab ---
// 1. Check URL 'tab' parameter first.
// 2. If not set, check if filters exist (implies login tab).
// 3. Default to 'audit'.
$urlTab = $_GET['tab'] ?? '';
if ($urlTab === 'audit' || $urlTab === 'edit' || $urlTab === 'login') {
    $activeTab = $urlTab;
} else {
    $activeTab = ((!empty($_GET['from']) || !empty($_GET['to']) || !empty($_GET['status'])) ? 'login' : 'audit');
}

/* === 1. Fetch Audit Logs === */
$audit_query = "
    SELECT 
        al.*,
        CONCAT(
            editor.first_name, 
            IF(editor.middle_name IS NOT NULL AND editor.middle_name != '', CONCAT(' ', editor.middle_name), ''), 
            ' ', editor.last_name
        ) AS editorName,
        CONCAT(
            target.first_name, 
            IF(target.middle_name IS NOT NULL AND target.middle_name != '', CONCAT(' ', target.middle_name), ''), 
            ' ', target.last_name
        ) AS targetName
    FROM audit_logs al
    LEFT JOIN users editor ON al.user_id = editor.id
    LEFT JOIN users target ON al.target_id = target.id
    ORDER BY al.log_time DESC
";
$audit_result = $conn->query($audit_query);
if (!$audit_result) {
    die("Audit Query Error: " . $conn->error);
}

/* === 2. Fetch Edit Requests === */
$edit_query = "
    SELECT er.*, 
           CONCAT(u.first_name, ' ', IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(u.middle_name, ' '), ''), u.last_name) AS requesterName, 
           CONCAT(admin.first_name, ' ', IF(admin.middle_name IS NOT NULL AND admin.middle_name != '', CONCAT(admin.middle_name, ' '), ''), admin.last_name) AS reviewerName
    FROM edit_requests er
    JOIN users u ON er.user_id = u.id
    LEFT JOIN users admin ON er.reviewed_by = admin.id
    ORDER BY er.request_time DESC
";
$edit_result = $conn->query($edit_query);
if (!$edit_result) {
    die("Edit Query Error: " . $conn->error);
}

/* === 3. Fetch Login Attempts === */
// 'WHERE 1' ensures we select ALL rows (Success + Fail) by default
$loginWhere = "1"; 
$fromDate = $_GET['from'] ?? '';
$toDate   = $_GET['to'] ?? '';
$statusFilter = $_GET['status'] ?? '';

if (!empty($fromDate)) {
    $loginWhere .= " AND la.attempt_time >= '" . $conn->real_escape_string($fromDate) . " 00:00:00'";
}
if (!empty($toDate)) {
    $loginWhere .= " AND la.attempt_time <= '" . $conn->real_escape_string($toDate) . " 23:59:59'";
}
if (!empty($statusFilter) && $statusFilter !== 'all') {
    if ($statusFilter === 'success') {
        $loginWhere .= " AND la.status = 'success'";
    } else {
        $loginWhere .= " AND la.status != 'success'";
    }
}

$login_query = "
    SELECT la.*, 
           COALESCE(
               CONCAT(u.first_name, ' ', IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(u.middle_name, ' '), ''), u.last_name),
               la.email,
               '[Unknown User]'
           ) AS fullName,
           u.role
    FROM login_attempts la
    LEFT JOIN users u ON la.user_id = u.id
    WHERE $loginWhere
    ORDER BY la.attempt_time DESC
";
$login_result = $conn->query($login_query);
if (!$login_result) {
    die("Login Query Error: " . $conn->error);
}

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
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
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
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../Dashboard/home.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Activity Logs</li>
            </ul>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs nav-tabs-solid nav-justified" role="tablist">
        <li class="nav-item">
          <button class="nav-link <?= $activeTab == 'audit' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#audit" type="button">Audit Logs</button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab == 'edit' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#edit" type="button">Edit Requests</button>
        </li>
        <li class="nav-item">
          <button class="nav-link <?= $activeTab == 'login' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#login" type="button">Login Attempts</button>
        </li>
      </ul>

      <div class="tab-content mt-3">
        
        <div class="tab-pane fade <?= $activeTab == 'audit' ? 'show active' : '' ?>" id="audit">
          <div class="card card-table comman-shadow">
            <div class="card-body">
              <div class="page-header">
                  <div class="row align-items-center">
                      <div class="col">
                          <h5 class="card-title">Audit Logs</h5>
                      </div>
                  </div>
              </div>
              <div class="table-responsive">
                <table class="table table-hover table-center mb-0 datatable nowrap w-100">
                  <thead class="thead-light">
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
                                <td><span class="badge badge-soft-info"><?= htmlspecialchars($row['action'] ?? 'N/A') ?></span></td>
                                <td>
                                    <button 
                                        class="btn btn-sm btn-white text-success me-2 view-desc-btn" 
                                        data-editor="<?= htmlspecialchars($row['editorName'] ?? 'System') ?>"
                                        data-target="<?= htmlspecialchars($row['targetName'] ?? '—') ?>"
                                        data-description="<?= htmlspecialchars($row['description'] ?? 'No details available.') ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#descModal">
                                        <i class="far fa-eye me-1"></i> View
                                    </button>
                                </td>
                                <td><?= !empty($row['log_time']) ? date('M d, Y h:i A', strtotime($row['log_time'])) : 'N/A' ?></td>
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

        <div class="tab-pane fade <?= $activeTab == 'edit' ? 'show active' : '' ?>" id="edit">
          <div class="card card-table comman-shadow">
            <div class="card-body">
               <div class="page-header">
                  <div class="row align-items-center">
                      <div class="col">
                          <h5 class="card-title">Edit Requests</h5>
                      </div>
                  </div>
              </div>
              <div class="table-responsive">
                <table class="table table-hover table-center mb-0 datatable nowrap w-100">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>User</th>
                      <th>Request Type</th>
                      <th>Status</th>
                      <th>Details</th>
                      <th>Date/Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1; if ($edit_result && $edit_result->num_rows > 0): ?>
                        <?php while ($row = $edit_result->fetch_assoc()): 

                            $jsonData = base64_encode(json_encode([
                                'requesterName' => $row['requesterName'] ?? 'N/A',
                                'request_type' => $row['request_type'] ?? 'N/A',
                                'status' => $row['status'] ?? 'pending',
                                'reviewerName' => $row['reviewerName'] ?? 'N/A',
                                'review_time' => $row['review_time'] ?? '',
                                'description' => $row['reason'] ?? 'No details available.'
                            ]));
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['requesterName'] ?? 'N/A') ?></td>
                                <td><span class="badge badge-soft-primary"><?= htmlspecialchars($row['request_type'] ?? 'N/A') ?></span></td>

                                <td>
                                    <?php 
                                        $status = $row['status'] ?? 'pending';
                                        if ($status === 'approved') echo '<span class="badge badge-soft-success">Approved</span>';
                                        else if ($status === 'rejected') echo '<span class="badge badge-soft-danger">Rejected</span>';
                                        else echo '<span class="badge badge-soft-warning">Pending</span>';
                                    ?>
                                </td>

                                <td>
                                    <button 
                                        class="btn btn-sm btn-white text-success me-2 view-edit-btn"
                                        data-request="<?= $jsonData ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRequestModal">
                                        <i class="far fa-eye me-1"></i> View
                                    </button>
                                </td>

                                <td>
                                    <?= !empty($row['request_time']) ? date('M d, Y h:i A', strtotime($row['request_time'])) : 'N/A' ?>
                                    <?php if (!empty($row['review_time'])): ?>
                                        <br><small class="text-muted">Reviewed: <?= date('M d, Y h:i A', strtotime($row['review_time'])) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No edit requests available.</td></tr>
                    <?php endif; ?>
                </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade <?= $activeTab == 'login' ? 'show active' : '' ?>" id="login">
          
          <div class="alert alert-primary alert-dismissible fade show mb-3" role="alert">
                <i class="fas fa-network-wired me-2"></i>
                <strong>System Diagnostic:</strong> Your current IP Address is detected as: 
                <span class="badge bg-white text-primary ms-2" style="font-size: 1em;">
                    <?php echo $_SERVER['REMOTE_ADDR']; ?>
                </span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>

          <div class="card card-table comman-shadow">
            <div class="card-body">
               <div class="page-header">
                  <div class="row align-items-center">
                      <div class="col">
                          <h5 class="card-title">Login Attempts</h5>
                      </div>
                  </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">From:</label>
                    <input type="date" id="filterFrom" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">To:</label>
                    <input type="date" id="filterTo" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status:</label>
                    <select id="filterStatus" class="form-select">
                        <option value="all" <?= $statusFilter == 'all' ? 'selected' : '' ?>>All</option>
                        <option value="success" <?= $statusFilter == 'success' ? 'selected' : '' ?>>Success Only</option>
                        <option value="failed" <?= $statusFilter == 'failed' ? 'selected' : '' ?>>Failed Only</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="filterDateBtn" class="btn btn-primary w-100">Filter</button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover table-center mb-0 datatable nowrap w-100">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>User / Email</th>
                      <th>Role</th>
                      <th>IP Address</th>
                      <th>Device Used</th>
                      <th>Status</th>
                      <th>Date/Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i=1; if ($login_result && $login_result->num_rows > 0): ?>
                        <?php while ($row = $login_result->fetch_assoc()): 
                            $deviceInfo = getDeviceIconAndName($row['user_agent'] ?? '');
                            $displayName = $row['fullName'] ?? 'N/A';
                            $displayRole = $row['role'] ?? 'Unknown';
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>

                                <td>
                                    <h2 class="table-avatar">
                                        <a class="avatar avatar-sm me-2"><i class="fas fa-user-circle fa-2x text-secondary"></i></a>
                                        <a><?= htmlspecialchars($displayName) ?></a>
                                    </h2>
                                </td>

                                <td><?= htmlspecialchars($displayRole) ?></td>

                                <td><span class="text-monospace"><?= htmlspecialchars($row['ip_address'] ?? 'N/A') ?></span></td>

                                <td>
                                    <i class="fab <?= $deviceInfo['icon'] ?? 'fa-question' ?> me-1 text-muted"></i>
                                    <?= htmlspecialchars($deviceInfo['name'] ?? 'Unknown Device') ?>
                                </td>

                                <td>
                                    <?php if (($row['status'] ?? '') === 'success'): ?>
                                        <span class="badge badge-soft-success">Success</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-danger">Failed</span>
                                        <?php if (!empty($row['reason'])): ?>
                                            <i class="fas fa-info-circle text-danger ms-1" data-bs-toggle="tooltip" title="<?= htmlspecialchars($row['reason']) ?>"></i>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['attempt_time']) ? date('M d, Y h:i A', strtotime($row['attempt_time'])) : 'N/A' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No login attempts recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
     </div>
  </div>
</div>

<div class="modal fade" id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="descModalLabel">Log Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label fw-bold">Editor:</label>
            <span id="modalEditor"></span>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Target:</label>
            <span id="modalTarget"></span>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Description:</label>
            <div class="p-2 bg-light border rounded" id="modalDescription" style="min-height: 60px; white-space: pre-wrap;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editRequestModal" tabindex="-1" aria-labelledby="editRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRequestModalLabel">Edit Request Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Requester:</label>
                <div id="modalRequester"></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Type:</label>
                <div id="modalRequestType"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Status:</label>
                <div id="modalStatus"></div>
            </div>
             <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Reviewed By:</label>
                <div id="modalReviewer"></div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Review Time:</label>
            <div id="modalReviewTime"></div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">Details:</label>
            <div class="p-2 bg-light border rounded" id="modalRequestDetails" style="min-height: 60px; white-space: pre-wrap;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../../assets/plugins/datatables/datatables.min.js"></script>
<script src="../../assets/js/script.js"></script>

<script>
$(document).ready(function() {

    // --- 1. Normalize table rows to match header count ---
    function normalizeTableCells($table) {
        var expected = $table.find('thead th').length;
        $table.find('tbody tr').each(function () {
            var $row = $(this);
            var cells = $row.find('td').length;
            if (cells > expected) $row.find('td:gt(' + (expected - 1) + ')').remove();
            if (cells < expected) {
                for (var i = cells; i < expected; i++) $row.append('<td></td>');
            }
        });
    }

    // --- 2. Initialize DataTables safely ---
    var initializedTables = {};

    function initTable($table) {
        if (
            $table.length && 
            !$.fn.DataTable.isDataTable($table) &&
            $table.find('tbody tr').length &&
            $table.is(':visible')
        ) {
            normalizeTableCells($table); 
            $.fn.dataTable.ext.errMode = 'none'; 
            $table.DataTable({
                bFilter: true,
                ordering: false,
                language: { paginate: { next: 'Next', previous: 'Previous' } }
            });
        }
    }

    // Initialize default visible tab
    var activeTabId = $('.tab-pane.active').attr('id');
    if(activeTabId) {
        initTable($('#' + activeTabId + ' .datatable'));
        initializedTables['#' + activeTabId] = true;
    }

    // --- 3. TAB STATE PERSISTENCE ---
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('data-bs-target').substring(1); // get id (e.g., 'login')
        
        // Initialize table if needed
        if (!initializedTables['#'+target]) {
            initTable($('#' + target + ' .datatable'));
            initializedTables['#'+target] = true;
        }

        // Update URL (e.g., ?tab=login)
        var newUrl = window.location.pathname + '?tab=' + target;
        if (target === 'login') {
            var from = $('#filterFrom').val();
            var to = $('#filterTo').val();
            var status = $('#filterStatus').val();
            if(from) newUrl += '&from=' + encodeURIComponent(from);
            if(to) newUrl += '&to=' + encodeURIComponent(to);
            if(status && status !== 'all') newUrl += '&status=' + encodeURIComponent(status);
        }
        
        window.history.pushState({path:newUrl}, '', newUrl);
    });

    // --- 4. Filter Login Attempts by Date & Status (Fixed) ---
    $('#filterDateBtn').on('click', function() {
        var from = $('#filterFrom').val();
        var to = $('#filterTo').val();
        var status = $('#filterStatus').val();
        
        // FORCE tab=login when filtering
        var url = window.location.pathname + '?tab=login';
        
        if (from) url += '&from=' + encodeURIComponent(from);
        if (to) url += '&to=' + encodeURIComponent(to);
        if (status && status !== 'all') url += '&status=' + encodeURIComponent(status);
        
        window.location.href = url;
    });

    // --- 5. View Audit Log Modal ---
    $(document).on('click', '.view-desc-btn', function () {
        var editor = $(this).data('editor');
        var target = $(this).data('target');
        var description = $(this).data('description') || "No details provided.";

        $('#modalEditor').text(editor);
        $('#modalTarget').text(target);
        $('#modalDescription').text(description);
    });

    // --- 6. View Edit Request Modal ---
    $(document).on('click', '.view-edit-btn', function () {
        var rawData = $(this).data('request');
        var data = {};
        try { data = JSON.parse(atob(rawData)); } catch(e) {}

        $('#modalRequester').text(data.requesterName || 'Unknown');
        $('#modalRequestType').text(data.request_type || 'N/A');
        $('#modalStatus').text(data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A');
        $('#modalReviewer').text(data.reviewerName || '—');
        $('#modalReviewTime').text(data.review_time ? new Date(data.review_time).toLocaleString() : '—');
        $('#modalRequestDetails').text(data.description || 'No details provided.');
    });

    // --- 7. Initialize Tooltips ---
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

});
</script>

</body>
</html>