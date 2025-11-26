<?php
session_start();
include '../../1database_conn/connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Only allow users with role 'Administrator'
$role = strtolower(trim($_SESSION['role']));
if ($role !== 'administrator') {
    header("Location: ../../index.php"); // Redirect non-admins to login/dashboard
    exit();
}

$admin_id = intval($_SESSION['user_id']);

// Get assignment_id from GET parameter if needed
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
$evaluatee = null;

if ($assignment_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.fullName, d.department_name, ea.id AS assignment_id
        FROM evaluation_assignments ea
        INNER JOIN users u ON ea.evaluatee_id = u.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE ea.id = ? AND ea.evaluator_id = ?
    ");
    $stmt->bind_param("ii", $assignment_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $evaluatee = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch academic list for step 1
$academics = $conn->query("SELECT id, semester, school_year, is_default 
                           FROM academic_list ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Fill Evaluation</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>

<body>

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
                            <h3 class="page-title">Fill Evaluation</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../home.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Evaluation Form</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-body">

                                <form id="fillEvaluationForm">
                                    <input type="hidden" id="evaluateeId" value="<?= $evaluatee_id ?>">
                                    <input type="hidden" id="formId" value="<?= $form_id ?>">

                                    <?php foreach ($categories as $cat): ?>
                                        <div class="card mt-3" data-category-id="<?= $cat['category_id'] ?>">
                                            <div class="card-header"><strong><?= htmlspecialchars($cat['category_name']) ?></strong></div>
                                            <div class="card-body">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Output</th>
                                                            <th>Success Indicator</th>
                                                            <th>Actual Accomplishment</th>
                                                            <th>Q</th>
                                                            <th>E</th>
                                                            <th>T</th>
                                                            <th>A</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($cat['criteria'] as $crit): ?>
                                                            <tr>
                                                                <td><textarea class="form-control output_name"><?= htmlspecialchars($crit['output_name']) ?></textarea></td>
                                                                <td><textarea class="form-control success_indicator"><?= htmlspecialchars($crit['success_indicator']) ?></textarea></td>
                                                                <td><textarea class="form-control accomplishment"><?= htmlspecialchars($crit['accomplishment']) ?></textarea></td>
                                                                <td><input type="number" class="form-control q_rating" min="0" max="5" step="0.01" value="0"></td>
                                                                <td><input type="number" class="form-control e_rating" min="0" max="5" step="0.01" value="0"></td>
                                                                <td><input type="number" class="form-control t_rating" min="0" max="5" step="0.01" value="0"></td>
                                                                <td><input type="number" class="form-control a_rating" min="0" max="5" step="0.01" value="0"></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="text-end mt-3">
                                        <button type="button" class="btn btn-success" id="submitEvaluationBtn">Submit Evaluation</button>
                                    </div>
                                </form>

                            </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        $('#submitEvaluationBtn').click(function() {
            let categories = [];

            $('.card[data-category-id]').each(function() {
                let categoryName = $(this).find('.card-header').text().trim();
                let categoryId = $(this).data('category-id');
                let criteria = [];

                $(this).find('tbody tr').each(function() {
                    criteria.push({
                        output_name: $(this).find('.output_name').val().trim(),
                        success_indicator: $(this).find('.success_indicator').val().trim(),
                        accomplishment: $(this).find('.accomplishment').val().trim(),
                        q_rating: parseFloat($(this).find('.q_rating').val()) || 0,
                        e_rating: parseFloat($(this).find('.e_rating').val()) || 0,
                        t_rating: parseFloat($(this).find('.t_rating').val()) || 0,
                        a_rating: parseFloat($(this).find('.a_rating').val()) || 0
                    });
                });

                categories.push({ category_id: categoryId, categoryName: categoryName, criteria: criteria });
            });

            let payload = {
                evaluatee_id: parseInt($('#evaluateeId').val()),
                form_id: parseInt($('#formId').val()),
                categories: categories
            };

            $.ajax({
                url: 'submit_evaluation.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(res) {
                    if(res.success){
                        toastr.success('Evaluation submitted successfully!');
                        $('#fillEvaluationForm')[0].reset();
                    } else {
                        toastr.error(res.error || 'Error submitting evaluation.');
                    }
                },
                error: function() {
                    toastr.error('Server error. Please try again.');
                }
            });
        });
    </script>

</body>

</html>
