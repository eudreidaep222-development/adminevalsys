<?php
session_start();
// Check permissions
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    header("Location: ../../index.php"); 
    exit();
}

require_once '../../1database_conn/connection.php';

// --- HELPER: Parse User Agent for Device Name ---
function getDeviceIconAndName($userAgent) {
    $userAgent = strtolower($userAgent);
    $icon = 'fa-desktop';
    $name = 'Desktop';

    if (strpos($userAgent, 'windows') !== false) {
        $name = 'Windows PC';
        $icon = 'fa-microsoft';
    } elseif (strpos($userAgent, 'macintosh') !== false) {
        $name = 'Mac';
        $icon = 'fa-apple';
    } elseif (strpos($userAgent, 'iphone') !== false) {
        $name = 'iPhone';
        $icon = 'fa-mobile-alt';
    } elseif (strpos($userAgent, 'android') !== false) {
        $name = 'Android';
        $icon = 'fa-android';
    } elseif (strpos($userAgent, 'linux') !== false) {
        $name = 'Linux';
        $icon = 'fa-linux';
    }

    return ['name' => $name, 'icon' => $icon];
}

// --- FETCH LOGIN ATTEMPTS ---
// We JOIN with the users table to get the Name and Role based on user_id
// If user_id is NULL (e.g. invalid email used), we just show the email attempted.
$sql = "
    SELECT la.*, u.fullName, u.role 
    FROM login_attempts la 
    LEFT JOIN users u ON la.user_id = u.id 
    ORDER BY la.attempt_time DESC 
    LIMIT 50";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Security</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/feather/feather.css">
    <link rel="stylesheet" href="../../assets/plugins/icons/flags/flags.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                            <h3 class="page-title">Authentication & Security Module</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Security</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="alert alert-primary alert-dismissible fade show" role="alert">
                    <i class="fas fa-network-wired me-2"></i>
                    <strong>System Diagnostic:</strong> Your current IP Address is detected as: 
                    <span class="badge bg-white text-primary ms-2" style="font-size: 1em;">
                        <?php echo $_SERVER['REMOTE_ADDR']; ?>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Authentication Logs</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            This module protects user data and system integrity through authentication enforcement, access logging, and session timeout policies.
                        </p>

                        <h6 class="mb-3">Recent Login Activity</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User / Email</th>
                                        <th>Role</th>
                                        <th>IP Address</th>
                                        <th>Device Used</th>
                                        <th>Timestamp</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): 
                                            // Determine device info
                                            $deviceInfo = getDeviceIconAndName($row['user_agent']);
                                            
                                            // Determine Display Name
                                            $displayName = $row['fullName'] ?? $row['email'];
                                            $displayRole = $row['role'] ?? '<span class="text-muted">Unknown</span>';
                                        ?>
                                        <tr>
                                            <td>
                                                <h2 class="table-avatar">
                                                    <a href="javascript:void(0);" class="avatar avatar-sm me-2"><img class="avatar-img rounded-circle" src="../../assets/img/profiles/avatar-01.jpg" alt="User Image"></a>
                                                    <a href="javascript:void(0);"><?php echo htmlspecialchars($displayName); ?></a>
                                                </h2>
                                            </td>
                                            <td><?php echo htmlspecialchars($displayRole); ?></td>
                                            <td>
                                                <span class="text-monospace"><?php echo htmlspecialchars($row['ip_address']); ?></span>
                                            </td>
                                            <td>
                                                <i class="fab <?php echo $deviceInfo['icon']; ?> me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($deviceInfo['name']); ?>
                                            </td>
                                            <td><?php echo date('M d, Y h:i A', strtotime($row['attempt_time'])); ?></td>
                                            <td>
                                                <?php if ($row['status'] === 'success'): ?>
                                                    <span class="badge bg-success">Success</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                    <?php if(!empty($row['reason'])): ?>
                                                        <i class="fas fa-info-circle text-danger ms-1" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($row['reason']); ?>"></i>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No login attempts recorded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i> Session timeout is set to <strong>15 minutes</strong> of inactivity.
                        </div>
                    </div>
                </div>

            </div>
            <?php include '../../Layout Components/footer.php'; ?>
        </div>
    </div>

    <script src="../../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/feather.min.js"></script>
    <script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../../assets/js/script.js"></script>
    
    <script>
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>

</body>
</html>