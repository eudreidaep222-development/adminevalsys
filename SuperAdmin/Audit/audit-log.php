<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php'; 

// Log view action
log_action($conn, $_SESSION['user_id'], 'View Audit Logs', null, 'Audit Logs', 'Super Admin viewed the audit logs page');

// --- Pagination ---
$limit = 20; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// --- Search ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$paramTypes = '';

if (!empty($search)) {
    $whereClause = "WHERE (actor.fullName LIKE ? OR al.action LIKE ? OR al.description LIKE ? OR target.fullName LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $paramTypes = 'ssss';
}

// --- Count Total ---
$countSql = "SELECT COUNT(*) as total 
             FROM audit_logs al
             LEFT JOIN users actor ON al.user_id = actor.id 
             LEFT JOIN users target ON al.target_id = target.id
             $whereClause";
             
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalLogs / $limit);

// --- Fetch Logs ---
$sql = "SELECT al.*, 
               actor.fullName AS actorName, 
               target.fullName AS targetName 
        FROM audit_logs al 
        LEFT JOIN users actor ON al.user_id = actor.id 
        LEFT JOIN users target ON al.target_id = target.id 
        $whereClause
        ORDER BY al.log_time DESC 
        LIMIT ?, ?";
        
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $paramTypes .= 'ii';
    $params[] = $offset;
    $params[] = $limit;
    $stmt->bind_param($paramTypes, ...$params);
} else {
    $stmt->bind_param('ii', $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Audit Logs</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
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
            
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="page-title">Audit Logs</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../Dashboard/home.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Audit Logs</li>
                    </ul>
                </div>
                <form method="GET" class="d-flex" style="max-width: 300px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control me-2" placeholder="Search logs...">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-table comman-shadow">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-center mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Editor</th>
                                            <th>Action</th>
                                            <th>Target</th>
                                            <th>Description</th>
                                            <th>Date/Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php $i = $offset + 1; while ($row = $result->fetch_assoc()): 
                                                // 1. PREPARE DATA
                                                $logData = [
                                                    'editor' => $row['actorName'] ?? 'System',
                                                    'target' => $row['targetName'] ?? '—',
                                                    'desc'   => $row['description'] ?? 'No details provided.',
                                                    'extra'  => $row['extra_data'] // Can be null
                                                ];
                                                
                                                // 2. ENCODE TO JSON (Handles quotes like 'Owner' perfectly)
                                                // ENT_QUOTES ensures the JSON string fits inside the HTML button attribute
                                                $safeJson = htmlspecialchars(json_encode($logData), ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <tr>
                                                    <td><?= $i++ ?></td>
                                                    <td><?= htmlspecialchars($row['actorName'] ?? 'System') ?></td>
                                                    <td><span class="badge bg-info"><?= htmlspecialchars($row['action']) ?></span></td>
                                                    <td><?= htmlspecialchars($row['targetName'] ?? '—') ?></td>
                                                    <td>
                                                        <button 
                                                            class="btn btn-sm btn-white text-success me-2 view-desc-btn" 
                                                            data-log="<?= $safeJson ?>"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#auditModal">
                                                            <i class="far fa-eye me-1"></i> View
                                                        </button>
                                                    </td>
                                                    <td><?= date('M d, Y h:i A', strtotime($row['log_time'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No audit logs found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPages > 1): ?>
                                <nav>
                                    <ul class="pagination justify-content-center mt-3">
                                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="auditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Log Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="fw-bold">Editor:</label>
                <div id="modalEditor" class="text-muted"></div>
            </div>
            <div class="col-md-6">
                <label class="fw-bold">Target:</label>
                <div id="modalTarget" class="text-muted"></div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="fw-bold">Description:</label>
            <div class="p-3 bg-light border rounded" id="modalDescription" style="min-height: 50px; white-space: pre-wrap;"></div>
        </div>

        <div class="mb-3" id="extraDataContainer" style="display:none;">
            <label class="fw-bold text-primary">Technical Details:</label>
            <div class="p-3 bg-dark text-white border rounded small" id="modalExtra" style="max-height: 150px; overflow-y: auto; font-family: monospace;"></div>
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
<script src="../../assets/js/script.js"></script>

<script>
$(document).ready(function() {
    // Handle "View" Button Click
    $(document).on('click', '.view-desc-btn', function () {
        // 1. jQuery automatically parses the JSON in data-log
        var logData = $(this).data('log');

        // Debugging: Uncomment to see data in console
        // console.log(logData);

        // 2. Fill fields
        $('#modalEditor').text(logData.editor);
        $('#modalTarget').text(logData.target);
        $('#modalDescription').text(logData.desc);

        // 3. Handle Extra Data (if exists)
        if (logData.extra && logData.extra !== null && logData.extra !== "NULL") {
            $('#modalExtra').text(logData.extra);
            $('#extraDataContainer').show();
        } else {
            $('#extraDataContainer').hide();
        }
    });
});
</script>

</body>
</html>