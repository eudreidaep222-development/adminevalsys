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

<!-- security_module.php -->

    <div class="main-wrapper">
        <?php
        include '../../Layout Components/header.php';
        include '../../Layout Components/sidebar.php';
        ?>

        <div class="page-wrapper">
            <div class="content container-fluid">

                <!-- Page Header -->
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

                <!-- Security Module Content -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Authentication and Security Module</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            This module protects user data and system integrity through authentication enforcement, access logging, and session timeout policies.
                        </p>

                        <!-- Access Log Table -->
                        <h6>Recent Login Activity</h6>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>admin1</td>
                                    <td>Super Admin</td>
                                    <td>192.168.1.10</td>
                                    <td>2025-06-20 08:00</td>
                                    <td><span class="badge bg-success">Success</span></td>
                                </tr>
                                <tr>
                                    <td>evaluator2</td>
                                    <td>Evaluator</td>
                                    <td>192.168.1.23</td>
                                    <td>2025-06-20 08:15</td>
                                    <td><span class="badge bg-danger">Failed</span></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Info Alert -->
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
    <script src="../../assets/plugins/apexchart/apexcharts.min.js"></script>
    <script src="../../assets/plugins/apexchart/chart-data.js"></script>
    <script src="../../assets/plugins/simple-calendar/jquery.simple-calendar.js"></script>
    <script src="../../assets/js/calander.js"></script>
    <script src="../../assets/js/circle-progress.min.js"></script>
    <script src="../../assets/js/script.js"></script>

</body>

</html>
