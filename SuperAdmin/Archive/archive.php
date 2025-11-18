<?php
session_start();
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Archive</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<!-- archive_module.php -->

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
                            <h3 class="page-title">Archiving & Retrieval Module</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Archiving</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Archiving Module Content -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Archiving and Retrieval Module</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            The Archiving and Retrieval Module ensures the long-term preservation of data and enables authorized users to retrieve archived records using filters such as semester dates. This supports future evaluations and audits.
                        </p>

                        <!-- Filters -->
                        <form class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Department</label>
                                <select class="form-select">
                                    <option selected>Choose...</option>
                                    <option value="IMACS">IMACS</option>
                                    <option value="Engineering">Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">School Year</label>
                                <select class="form-select">
                                    <option selected>Choose...</option>
                                    <option>2023-2024</option>
                                    <option>2024-2025</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Semester</label>
                                <select class="form-select">
                                    <option selected>Choose...</option>
                                    <option>1st</option>
                                    <option>2nd</option>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <button type="button" class="btn btn-outline-secondary">Export</button>
                            </div>
                        </form>

                        <!-- Table -->
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Administrator</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Semester</th>
                                    <th>Avg. Score</th>
                                    <th>Date Archived</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>Admin 1</td>
                                    <td>IMACS</td>
                                    <td>2023-2024</td>
                                    <td>1st</td>
                                    <td>88.5</td>
                                    <td>2024-06-01</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Admin 2</td>
                                    <td>Engineering</td>
                                    <td>2023-2024</td>
                                    <td>2nd</td>
                                    <td>91.2</td>
                                    <td>2024-06-02</td>
                                </tr>
                            </tbody>
                        </table>
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
