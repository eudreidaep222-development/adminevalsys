<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'super admin') {
    header("Location: /login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Info Change</title>
    <link rel="shortcut icon" href="/assets/img/logo-small.png">
    <link rel="stylesheet" href="/assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
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
                        <h3 class="page-title">Request Info Change</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/Dashboard/home.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Request Info Change</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="field">Field to Update</label>
                            <select class="form-select" id="field" name="field">
                                <option value="name">Full Name</option>
                                <option value="email">Email</option>
                                <option value="department">Department</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="new_value">New Value</label>
                            <input type="text" class="form-control" id="new_value" name="new_value" placeholder="Enter new value">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js"></script>
<script src="../../assets/plugins/twitter-bootstrap-wizard/form-wizard.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
</body>
</html>
