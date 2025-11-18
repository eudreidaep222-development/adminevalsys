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
    // ✅ Helpers for sidebar UI
    // -----------------------------
    $current_uri = $_SERVER['REQUEST_URI'];

    function isActive($path) {
        global $current_uri;
        return basename($current_uri) === basename($path) ? 'active' : '';
    }

    function isSubmenuActive($prefix) {
        global $current_uri;
        $clean_uri = trim(parse_url($current_uri, PHP_URL_PATH), '/');
        $clean_prefix = trim($prefix, '/');
        return str_starts_with($clean_uri, $clean_prefix) ? 'active' : '';
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
                        <li class="<?php echo isActive('SuperAdmin/Dashboard/'); ?>">
                            <a href="<?php echo absolutePath('SuperAdmin/Dashboard/home.php'); ?>">
                                <i class="feather-grid"></i> <span>Dashboard</span>
                            </a>
                        </li>
                        <!-- User Management -->
                        <li class="submenu <?php echo isSubmenuActive('SuperAdmin/UserManagement/'); ?>">
                            <a href="#"><i class="fas fa-users-cog"></i> <span>User Management</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/manage-users.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/manage-users.php'); ?>">Manage Users</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/approve-request.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/approve-request.php'); ?>">Approve Requests</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/upload-users.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/upload-users.php'); ?>">Upload Users</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/UserManagement/activity-log.php'); ?>" class="<?php echo isActive('SuperAdmin/UserManagement/activity-log.php'); ?>">Activity Logs</a></li>
                            </ul>
                        </li>
                        <!-- Semester Setup -->
                        <li class="submenu <?php echo isSubmenuActive('SuperAdmin/Semester/'); ?>">
                            <a href="#"><i class="fas fa-calendar-alt"></i> <span>Semester Setup</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Semester/add-semester.php'); ?>" class="<?php echo isActive('SuperAdmin/Semester/add-semester.php'); ?>">Add Semester</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Semester/semester.php'); ?>" class="<?php echo isActive('SuperAdmin/Semester/semester.php'); ?>">Semester List</a></li>
                            </ul>
                        </li>
                        <!-- Evaluation -->
                        <li class="submenu <?php echo isSubmenuActive('SuperAdmin/Evaluation/'); ?>">
                            <a href="#"><i class="fas fa-file-alt"></i> <span>Evaluation</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/evaluation-forms.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/evaluation-forms.php'); ?>">Add Evaluation Form</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/update-evaluation-forms.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/update-evaluation-forms.php'); ?>">Update Evaluation Form</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/evaluator-assignments.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/evaluator-assignments.php'); ?>">Evaluator Assignments</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Evaluation/evaluation.php'); ?>" class="<?php echo isActive('SuperAdmin/Evaluation/evaluation.php'); ?>">Evaluation</a></li>
                            </ul>
                        </li>
                        <!-- Reports -->
                        <li class="submenu <?php echo isSubmenuActive('SuperAdmin/Reports/'); ?>">
                            <a href="#"><i class="fas fa-chart-bar"></i> <span>Reports</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Reports/reports.php'); ?>" class="<?php echo isActive('reports.php'); ?>">Evaluation Reports</a></li>
                            </ul>
                        </li>
                        <!-- Archive -->
                        <li class="submenu <?php echo isSubmenuActive('SuperAdmin/Archive/'); ?>">
                            <a href="#"><i class="fas fa-archive"></i> <span>Archive</span> <span class="menu-arrow"></span></a>
                            <ul>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Archive/archive.php'); ?>" class="<?php echo isActive('SuperAdmin/Archive/archive.php'); ?>">Archive</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Archive/csv-upload.php'); ?>" class="<?php echo isActive('SuperAdmin/Archive/csv-upload.php'); ?>">CSV Upload</a></li>
                                <li><a href="<?php echo absolutePath('SuperAdmin/Archive/database-backup.php'); ?>" class="<?php echo isActive('SuperAdmin/Archive/database-backup.php'); ?>">Database Backup</a></li>
                            </ul>
                        </li>
                    <li class="menu-title"><span>Management</span></li>
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
                    <li class="submenu <?php echo isSubmenuActive('Shared/Settings/'); ?>">
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
