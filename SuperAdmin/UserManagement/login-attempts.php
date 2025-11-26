<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

// Log that Super Admin viewed login attempts
log_action($conn, $_SESSION['user_id'], 'View Login Attempts', 'Super Admin viewed the login attempts page');

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$paramTypes = '';

// --- FIXED: Name Construction Logic ---
// We define this string to reuse it in both SELECT and WHERE clauses
$nameSql = "CONCAT(users.first_name, ' ', IF(users.middle_name IS NOT NULL AND users.middle_name != '', CONCAT(users.middle_name, ' '), ''), users.last_name)";

if (!empty($search)) {
    // FIXED: Replaced users.fullName with the CONCAT logic
    $whereClause = "WHERE ($nameSql LIKE ? OR login_attempts.ip_address LIKE ? OR login_attempts.status LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $paramTypes = 'sss';
}

// Count total
$countSql = "SELECT COUNT(*) as total FROM login_attempts 
             JOIN users ON login_attempts.user_id = users.id $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalLogs / $limit);

// Fetch attempts
// FIXED: Select constructed name as 'fullName' so the HTML below doesn't break
$sql = "SELECT login_attempts.*, 
        $nameSql AS fullName 
        FROM login_attempts 
        JOIN users ON login_attempts.user_id = users.id 
        $whereClause
        ORDER BY login_attempts.attempt_time DESC 
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

include '../../Layout Components/header.php';
include '../../Layout Components/sidebar.php';
?>

<div class="page-wrapper">
    <div class="content container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="page-title">Login Attempts</h3>
            <form method="GET" class="d-flex" style="max-width: 300px;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control me-2" placeholder="Search attempts...">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </form>
        </div>

        <div class="card card-table comman-shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
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
                            <?php if ($result->num_rows > 0): ?>
                                <?php $i = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($row['fullName']) ?></td>
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
                                <tr><td colspan="5" class="text-center">No login attempts found.</td></tr>
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

<?php include '../../Layout Components/footer-scripts.php'; ?>