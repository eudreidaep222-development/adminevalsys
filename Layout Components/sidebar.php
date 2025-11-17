<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role'])) {
    // No role means not logged in → force logout or login page
    header("Location: /logout.php");
    exit();
}

$role = strtolower(trim($_SESSION['role'])); // normalize role

// -----------------------------
// ✅ URL Protection
// -----------------------------
$current_path = $_SERVER['REQUEST_URI'];
$current_path = parse_url($current_path, PHP_URL_PATH); 
$current_path = trim($current_path, '/'); 

// Define allowed paths per role
$allowed_paths = [
    'super admin' => [
        'SuperAdmin/', // anything under SuperAdmin/
        'Shared/Settings/'
    ],
    'administrator' => [
        'Admin/Dashboard/home.php',
        'Admin/Dashboard/evaluation.php',
        'Shared/Settings/'
    ],
    'faculty' => [
        'Faculty/Dashboard/home.php',
        'Faculty/Dashboard/evaluation.php',
        'Shared/Settings/'
    ]
];

// Check access
$has_access = false;
if (isset($allowed_paths[$role])) {
    foreach ($allowed_paths[$role] as $allowed) {
        if (str_starts_with($current_path, trim($allowed, '/'))) {
            $has_access = true;
            break;
        }
    }
}

if (!$has_access) {
    // Redirect to correct dashboard if access not allowed
    if ($role === 'super admin') {
        header("Location: /SuperAdmin/Dashboard/home.php");
    } elseif ($role === 'administrator') {
        header("Location: /Admin/Dashboard/home.php");
    } elseif ($role === 'faculty') {
        header("Location: /Faculty/Dashboard/home.php");
    } else {
        header("Location: /login.php");
    }
    exit();
}

// -----------------------------
// ✅ Helpers for sidebar UI
// -----------------------------
function isActive($path) {
    global $current_path;
    return $current_path === trim($path, '/') ? 'active' : '';
}

function isSubmenuActive($paths) {
    global $current_path;
    foreach ($paths as $path) {
        if ($current_path === trim($path, '/')) return 'active';
    }
    return '';
}

function absolutePath($relative_path) {
    return '/' . ltrim($relative_path, '/');
}
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Main Menu</span></li>

                <?php if ($role === 'super admin'): ?>
                    <!-- SUPER ADMIN FULL MENU -->
                    <li class="<?php echo isActive('SuperAdmin/Dashboard/home.php'); ?>">
                        <a href="<?php echo absolutePath('SuperAdmin/Dashboard/home.php'); ?>">
                            <i class="feather-grid"></i> <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Example: User Management -->
                    <li class="submenu <?php echo isSubmenuActive([
                        'SuperAdmin/UserManagement/manage-users.php',
                        'SuperAdmin/UserManagement/approve-request.php',
                        'SuperAdmin/UserManagement/upload-users.php',
                        'SuperAdmin/UserManagement/activity-log.php'
                    ]); ?>">
                        <a href="#"><i class="fas fa-users-cog"></i> <span>User Management</span> <span class="menu-arrow"></span></a>
                        <ul>
                            <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/manage-users.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/manage-users.php'); ?>">Manage Users</a></li>
                            <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/approve-request.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/approve-request.php'); ?>">Approve Requests</a></li>
                            <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/upload-users.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/upload-users.php'); ?>">Upload Users</a></li>
                            <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/activity-log.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/activity-log.php'); ?>">Activity Logs</a></li>
                        </ul>
                    </li>

                                    <li class="submenu <?php echo isSubmenuActive([
                    'SuperAdmin/Semester/add-semester.php',
                    'SuperAdmin/Semester/edit-semester.php',
                    'SuperAdmin/Semester/semester.php'
                ]); ?>">
                    <a href="#"><i class="fas fa-calendar-alt"></i> <span>Semester Setup</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Semester/add-semester.php'); ?>" class="<?php echo isActive('SuperAdmin/Semester/add-semester.php'); ?>">Add Semester</a></li>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Semester/semester.php'); ?>" class="<?php echo isActive('SuperAdmin/Semester/semester.php'); ?>">Semester List</a></li>
                    </ul>
                </li>

                <li class="submenu <?php echo isSubmenuActive([
                    'SuperAdmin/Evaluation/evaluation-forms.php',
                    'SuperAdmin/Evaluation/update-evaluation-forms.php',
                    'SuperAdmin/Evaluation/evaluator-assignments.php',
                    'SuperAdmin/Evaluation/evaluation.php'
                ]); ?>">
                    <a href="#"><i class="fas fa-file-alt"></i> <span>Evaluation</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/evaluation-forms.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/evaluation-forms.php'); ?>">Add Evaluation Form</a></li>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/update-evaluation-forms.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/update-evaluation-forms.php'); ?>">Update Evaluation Form</a></li>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/evaluator-assignments.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/evaluator-assignments.php'); ?>">Evaluator Assignments</a></li>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/evaluation.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/evaluation.php'); ?>">Evaluation</a></li>
                    </ul>
                </li>

                <li class="submenu <?php echo isSubmenuActive([
                    'SuperAdmin/Reports/reports.php'
                ]); ?>">
                    <a href="#"><i class="fas fa-chart-bar"></i> <span>Reports</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Reports/reports.php'); ?>" class="<?php echo isActive('reports.php'); ?>">Evaluation Reports</a></li>
                        
                    </ul>
                </li>

                <li class="submenu <?php echo isSubmenuActive([
                    'SuperAdmin/Archive/archive.php',
                    'SuperAdmin/Archive/csv-upload.php',
                    'SuperAdmin/Archive/database-backup.php'
                ]); ?>">
                    <a href="#"><i class="fas fa-archive"></i> <span>Archive</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Archive/archive.php'); ?>" class="<?php echo isActive('SuperAdmin/Archive/archive.php'); ?>">Archive</a></li>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Archive/csv-upload.php'); ?>" class="<?php echo isActive('SuperAdmin/Archive/csv-upload.php'); ?>">CSV Upload</a></li>
                        <li><a href="<?php echo absolutePath('SuperAdmin/Archive/database-backup.php'); ?>" class="<?php echo isActive('SuperAdmin/Archive/database-backup.php'); ?>">Database Backup</a></li>
                    </ul>
                </li>

                <li class="menu-title"><span>Management</span></li>

                <!-- Future Management Links -->
                    <!-- (same as your original SuperAdmin sidebar content) -->

                <?php elseif ($role === 'administrator'): ?>
                    <!-- ADMINISTRATOR LIMITED MENU -->
                    <li class="<?php echo isActive('Admin/Dashboard/home.php'); ?>">
                        <a href="<?php echo absolutePath('Admin/Dashboard/home.php'); ?>">
                            <i class="feather-grid"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?php echo isActive('Admin/Dashboard/evaluation.php'); ?>">
                        <a href="<?php echo absolutePath('Admin/Dashboard/evaluation.php'); ?>">
                            <i class="fas fa-clipboard-check"></i> <span>Evaluation</span>
                        </a>
                    </li>

                <?php elseif ($role === 'faculty'): ?>
                    <!-- FACULTY LIMITED MENU -->
                    <li class="<?php echo isActive('Faculty/Dashboard/home.php'); ?>">
                        <a href="<?php echo absolutePath('Faculty/Dashboard/home.php'); ?>">
                            <i class="feather-grid"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?php echo isActive('Faculty/Dashboard/evaluation.php'); ?>">
                        <a href="<?php echo absolutePath('Faculty/Dashboard/evaluation.php'); ?>">
                            <i class="fas fa-clipboard-check"></i> <span>Evaluation</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- SETTINGS (always visible) -->
                <li class="submenu <?php echo isSubmenuActive([
                    'Shared/Settings/profile-settings.php',
                    'Shared/Settings/request-update.php'
                ]); ?>">
                    <a href="#"><i class="fas fa-cog"></i> <span>Settings</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li>
                            <a href="<?php echo absolutePath('Shared/Settings/profile-settings.php'); ?>" 
                            class="<?php echo isActive('Shared/Settings/profile-settings.php'); ?>">
                            Edit Profile
                            </a>
                        </li>

                        <!-- Request Info Change for Admin & Faculty only -->
                        <?php if ($role !== 'super admin'): ?>
                            <li>
                                <a href="<?php echo absolutePath('Shared/Settings/request-update.php'); ?>" 
                                class="<?php echo isActive('Shared/Settings/request-update.php'); ?>">
                                Request Info Change
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>

            </ul>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle submenu on click
    document.querySelectorAll('.sidebar-menu .submenu > a').forEach(function(menuLink){
        menuLink.addEventListener('click', function(e){
            e.preventDefault();
            const parent = this.parentElement;
            
            // Close other open submenus
            document.querySelectorAll('.sidebar-menu .submenu').forEach(function(sub){
                if(sub !== parent){
                    sub.classList.remove('open');
                    const ul = sub.querySelector('ul');
                    if(ul) ul.style.display = 'none';
                }
            });

            // Toggle clicked submenu
            parent.classList.toggle('open');
            const ul = parent.querySelector('ul');
            if(ul){
                ul.style.display = ul.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    // Auto-open submenu if current path matches a child
    const currentPath = window.location.pathname.replace(/^\/|\/$/g, '');
    document.querySelectorAll('.submenu ul li a').forEach(function(link){
        const linkPath = link.getAttribute('href').replace(/^\/|\/$/g, '');
        if(linkPath === currentPath){
            link.classList.add('active');
            const submenu = link.closest('.submenu');
            if(submenu){
                submenu.classList.add('open');
                const ul = submenu.querySelector('ul');
                if(ul) ul.style.display = 'block';
            }
        }
    });
});
</script>

</div>
