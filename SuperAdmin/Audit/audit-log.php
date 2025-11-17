<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php'; // centralized logging

// Log that Super Admin viewed the audit logs
log_action($conn, $_SESSION['user_id'], 'View Audit Logs', 'Super Admin viewed the audit logs page');

// Pagination setup
$limit = 20; // logs per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Optional search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$paramTypes = '';

if (!empty($search)) {
    $whereClause = "WHERE users.fullName LIKE ? OR audit_logs.action LIKE ? OR audit_logs.description LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $paramTypes = 'sss';
}

// Count total logs
$countSql = "SELECT COUNT(*) as total FROM audit_logs 
             JOIN users ON audit_logs.user_id = users.id $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = ceil($totalLogs / $limit);

// Fetch logs
$sql = "SELECT audit_logs.*, users.fullName 
        FROM audit_logs 
        JOIN users ON audit_logs.user_id = users.id 
        $whereClause
        ORDER BY audit_logs.log_time DESC 
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
            <h3 class="page-title">Audit Logs</h3>
            <form method="GET" class="d-flex" style="max-width: 300px;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control me-2" placeholder="Search logs...">
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
                                <th>Action</th>
                                <th>Description</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $i = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($row['fullName']) ?></td>
                                        <td><?= htmlspecialchars($row['action']) ?></td>
                                        <td><?= htmlspecialchars($row['description']) ?></td>
                                        <td><?= date('M d, Y h:i A', strtotime($row['log_time'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No audit logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
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
