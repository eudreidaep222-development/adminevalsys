<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../1database_conn/connection.php';

// Function to get the current user's info from DB
function getCurrentUser($conn) {
    if (!isset($_SESSION['user_id'])) {
        return [
            'name' => 'Guest',
            'role' => 'Visitor',
            'avatar' => '/assets/img/pfl temp.jpg'
        ];
    }

    $user_id = intval($_SESSION['user_id']);
    $sql = "SELECT fullName, role FROM users WHERE id = $user_id LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'name' => $row['fullName'],
            'role' => $row['role'],
            'avatar' => '/assets/img/pfl temp.jpg'
        ];
    }

    return [
        'name' => 'Unknown',
        'role' => 'N/A',
        'avatar' => '/assets/img/pfl temp.jpg'
    ];
}

// Fetch current user
$currentUser = getCurrentUser($conn);

// Notifications (dummy for now)
function getNotifications() {
    return [
        [
            'id' => 1,
            'title' => 'Carlson Tech has approved your estimate',
            'message' => 'Your estimate request was approved successfully.',
            'time' => '4 mins ago',
            'avatar' => '/assets/img/pfl temp.jpg',
            'is_read' => 0,
            'created_at' => date("Y-m-d H:i:s", strtotime("-4 minutes"))
        ],
        [
            'id' => 2,
            'title' => 'International Software Inc sent an invoice ($218)',
            'message' => 'You have received a new invoice for $218.',
            'time' => '6 mins ago',
            'avatar' => '/assets/img/pfl temp.jpg',
            'is_read' => 0,
            'created_at' => date("Y-m-d H:i:s", strtotime("-6 minutes"))
        ]
    ];
}
$notifications = getNotifications();

$unreadCount = count(array_filter($notifications, function($n) {
    return empty($n['is_read']) || $n['is_read'] == 0;
}));
?>



<div class="header">
    <div class="header-left">
        <a href="/home.php" class="logo">
            <img src="/assets/img/logo.png" alt="Logo" />
        </a>
        <a href="/home.php" class="logo logo-small">
            <img src="/assets/img/logo-small.png" alt="Logo" width="30" height="30" />
        </a>
    </div>

    <div class="menu-toggle">
        <a href="javascript:void(0);" id="toggle_btn">
            <i class="fas fa-bars"></i>
        </a>
    </div>

    <div class="top-nav-search">
<?php ?>

<?php
$defaultSemester = $conn->query("SELECT * FROM academic_list WHERE is_default = 1 LIMIT 1")->fetch_assoc();
?>

            <?php if ($defaultSemester): ?>
                <div class="d-flex justify-content-center">
                    <div class="alert alert-success mt-2 mb-3" style="width: fit-content;">
                        <strong> S.Y. <?= htmlspecialchars($defaultSemester['school_year']) ?> </strong> &nbsp;
                        <strong> Sem: </strong> <?= htmlspecialchars($defaultSemester['semester']) ?>&nbsp;
                        <strong> <?= date("F d, Y", strtotime($defaultSemester['start_date'])) ?> -</strong>
                        <strong> <?= date("F d, Y", strtotime($defaultSemester['end_date'])) ?> </strong>&nbsp;
                        <strong>Status:</strong>
                        <?php
                            if ($defaultSemester['status'] == 0) {
                                echo '<span class="badge bg-secondary">Pending</span>';
                            } elseif ($defaultSemester['status'] == 1) {
                                echo '<span class="badge bg-success">Started</span>';
                            } else {
                                echo '<span class="badge bg-warning text-dark">Closed</span>';
                            }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
    </div>

    <a class="mobile_btn" id="mobile_btn">
        <i class="fas fa-bars"></i>
    </a>

    <ul class="nav user-menu">
      
        <li class="nav-item zoom-screen me-2">
            <a href="#" class="nav-link header-nav-list">
                <img src="/assets/img/header-icon-04.svg" alt="" />
            </a>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link position-relative" data-bs-toggle="dropdown" href="#">
                <i class="bi <?= $unreadCount > 0 ? 'bi-bell-fill text-danger' : 'bi-bell' ?>"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end notifications-dropdown">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $n): ?>
                        <li class="notification-item" data-id="<?= $n['id'] ?>">
                            <a class="dropdown-item" href="#">
                                <strong><?= htmlspecialchars($n['title']) ?></strong>
                                <div class="text-truncate"><?= htmlspecialchars($n['message']) ?></div>
                                <small class="text-muted"><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="dropdown-item text-center">No notifications</li>
                <?php endif; ?>
            </ul>
        </li>


        <li class="nav-item dropdown has-arrow new-user-menus">
            <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                <span class="user-img">
                    <img class="rounded-circle" src="<?php echo $currentUser['avatar']; ?>" width="31" alt="<?= htmlspecialchars($currentUser['name']); ?><?php echo $currentUser['name']; ?>" />
                    <div class="user-text">
                        <h6><?= htmlspecialchars($currentUser['name']); ?></h6>
                        <p class="text-muted mb-0"><?php echo $currentUser['role']; ?></p>
                    </div>
                </span>
            </a>
            <div class="dropdown-menu">
                <div class="user-header">
                    <div class="avatar avatar-sm">
                        <img src="<?php echo $currentUser['avatar']; ?>" alt="User Image" class="avatar-img rounded-circle" />
                    </div>
                    <div class="user-text">
                        <h6><?= htmlspecialchars($currentUser['name']); ?></h6>
                        <p class="text-muted mb-0"><?= htmlspecialchars($currentUser['role']); ?></p>
                    </div>
                </div>
                <a class="dropdown-item" href="/logout.php">Logout</a>
            </div>
        </li>
    </ul>
</div>
