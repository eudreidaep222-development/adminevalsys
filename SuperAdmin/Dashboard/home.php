
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"rel="stylesheet">
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>

<body>

    <div class="main-wrapper">

        <?php
        include '../../Layout Components/header.php';
        include '../../Layout Components/sidebar.php';
        if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
          header("Location: ../../index.php"); 
          exit();
        }
        ?>

        <div class="page-wrapper">
            <div class="content container-fluid">

                <div class="page-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-sub-header">
                                <h3 class="page-title">
                                    Welcome <span class="user-name"><?php echo $currentUser['name']; ?></span>!
                                </h3>                               
                                    <div class="toolbar">
                                        <!-- Refresh -->
                                        <button class="tool-btn" title="Refresh" data-bs-toggle="tooltip" data-bs-placement="bottom" onclick="refreshDashboard()">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>

                                        <!-- Previous Semester -->
                                        <button class="tool-btn" title="Previous Semester" data-bs-toggle="tooltip" data-bs-placement="bottom" onclick="previousSemester()">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>

                                        <!-- Next Semester -->
                                        <button class="tool-btn" title="Next Semester" data-bs-toggle="tooltip" data-bs-placement="bottom" onclick="nextSemester()">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>

                                        <!-- Department Filter -->
                                        <select class="tool-select" id="departmentFilter">
                                            <option value="">All Departments</option>
                                            <?php
                                            $departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                                            if ($departments && $departments->num_rows > 0):
                                                while ($dept = $departments->fetch_assoc()):
                                            ?>
                                            <option value="<?= htmlspecialchars($dept['id']) ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                <ul class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                    <li class="breadcrumb-item active">
                                        <?php echo ucfirst($_SESSION['role']); ?>
                                    </li>

                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-3 col-sm-6 col-12 d-flex">
                        <div class="card bg-comman w-100">
                            <div class="card-body">
                                <div class="db-widgets d-flex justify-content-between align-items-center">
                                    <div class="db-info">
                                        <h6>Employees</h6>
                                        <h3 id="employeesCount">0</h3>
                                    </div>
                                    <div class="db-icon">
                                        <img src="../../assets/img/employee-group-svgrepo-com.svg" alt="Employee Group">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 d-flex">
                        <div class="card bg-comman w-100">
                            <div class="card-body">
                                <div class="db-widgets d-flex justify-content-between align-items-center">
                                    <div class="db-info">
                                        <h6>Evaluations Completed</h6>
                                        <h3 id="completedCount">0</h3>
                                    </div>
                                    <div class="db-icon">   
                                        <img src="../../assets/img/task-complete-svgrepo-com.svg" alt="Evaluation Completed">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 d-flex">
                        <div class="card bg-comman w-100">
                            <div class="card-body">
                                <div class="db-widgets d-flex justify-content-between align-items-center">
                                    <div class="db-info">
                                        <h6>Evaluations Pending</h6>
                                        <h3 id="pendingCount">0</h3>
                                    </div>
                                    <div class="db-icon">
                                        <img src="../../assets/img/contract-pending-line-svgrepo-com.svg" alt="Evaluation Pending">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 col-12 d-flex">
                        <div class="card bg-comman w-100">
                            <div class="card-body">
                                <div class="db-widgets d-flex justify-content-between align-items-center">
                                    <div class="db-info">
                                        <h6>Average Performance Scores</h6>
                                        <h3 id="avgScore">0</h3>
                                    </div>
                                    <div class="db-icon">
                                        <img src="../../assets/img/average-svgrepo-com.svg" alt="Average Scores">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                  <!-- Evaluation Completion Rate -->
                  <div class="col-md-12 col-lg-6">
                    <div class="card card-chart">
                      <div class="card-header">
                        <h5 class="card-title">Evaluation Completion Rate</h5>
                      </div>
                      <div class="card-body">
                        <div id="completion-rate"></div>
                      </div>
                    </div>
                  </div>

                  <!-- Completed vs Pending -->
                  <div class="col-md-12 col-lg-6">
                    <div class="card card-chart">
                      <div class="card-header">
                        <h5 class="card-title">Evaluations Completed vs Pending</h5>
                      </div>
                      <div class="card-body">
                        <div id="completed-vs-pending"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <!-- Average Performance Scores -->
                  <div class="col-md-12 col-lg-6">
                    <div class="card card-chart">
                      <div class="card-header">
                        <h5 class="card-title">Average Performance Scores by Department</h5>
                      </div>
                      <div class="card-body">
                        <div id="avg-performance"></div>
                      </div>
                    </div>
                  </div>

                  <!-- Performance Trends -->
                  <div class="col-md-12 col-lg-6">
                    <div class="card card-chart">
                      <div class="card-header">
                        <h5 class="card-title">Performance Score Trends Over Time</h5>
                      </div>
                      <div class="card-body">
                        <div id="performance-trends"></div>
                      </div>
                    </div>
                  </div>
                </div>


                

            </div>
            <footer>
                <p>Copyright © 2025 Kolehiyo ng Lungsod ng Dasmariñas.</p>
            </footer>
        </div>
    </div>

<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/apexchart/apexcharts.min.js"></script>
<script src="../../assets/plugins/simple-calendar/jquery.simple-calendar.js"></script>
<script src="../../assets/js/calander.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<?php
$semesters = [];
$result = $conn->query("SELECT * FROM academic_list ORDER BY id ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $semesters[] = $row;
    }
}
?>
<script>
  const semesters = <?= json_encode($semesters); ?>;
</script>

<script> 
document.addEventListener("DOMContentLoaded", function() {
  const deptSelect = document.getElementById('departmentFilter');
  let charts = {}; 

  // 🔹 Track semesters
  let currentSemesterIndex = 0;  // first record in semesters[]
  let maxSemester = semesters.length;

  // Helper: format semester label
  function formatSemesterLabel(sem) {
    let statusBadge = "";
    if (sem.status == 0) statusBadge = "Pending";
    else if (sem.status == 1) statusBadge = "Started";
    else statusBadge = "Closed";

    return `S.Y. ${sem.school_year} • ${sem.semester} • 
      ${new Date(sem.start_date).toLocaleDateString()} - ${new Date(sem.end_date).toLocaleDateString()} 
      • Status: ${statusBadge}`;
  }

  function loadDashboard(deptId = "", semesterId = semesters[currentSemesterIndex].id) {
    fetch(`dashboard-data.php?department_id=${deptId}&semester_id=${semesterId}`)
      .then(res => res.json())
      .then(data => {
        document.getElementById('employeesCount').textContent = data.employees;
        document.getElementById('completedCount').textContent = data.completed;
        document.getElementById('pendingCount').textContent = data.pending;
        document.getElementById('avgScore').textContent = data.avg_score;
        renderCharts(data);
      })
      .catch(err => {
        toastr.error("Error fetching dashboard data", "Error", { closeButton: true });
        console.error('Error fetching dashboard data:', err);
      });
  }

  function renderCharts(data) {
    // Pie
    if (!charts.completionRate) {
      charts.completionRate = new ApexCharts(document.querySelector("#completion-rate"), {
        chart: { type: "pie", height: 320 },
        series: data.completion_rate,
        labels: ["Completed", "Pending"],
        colors: ["#008000", "#ffdb19"],
        legend: { position: "bottom" }
      });
      charts.completionRate.render();
    } else {
      charts.completionRate.updateSeries(data.completion_rate);
    }

    // Completed vs Pending
    if (!charts.pendingChart) {
      charts.pendingChart = new ApexCharts(document.querySelector("#completed-vs-pending"), {
        chart: { type: "bar", height: 320 },
        series: [{ name: "Evaluations", data: data.completed_vs_pending }],
        xaxis: { categories: ["Completed", "Pending"] },
        plotOptions: { bar: { distributed: true, columnWidth: "45%", borderRadius: 10 } },
        colors: ["#008000", "#ffdb19"],
        dataLabels: { enabled: true }
      });
      charts.pendingChart.render();
    } else {
      charts.pendingChart.updateSeries([{ name: "Evaluations", data: data.completed_vs_pending }]);
    }

    // Avg Performance
    if (!charts.avgPerformance) {
      charts.avgPerformance = new ApexCharts(document.querySelector("#avg-performance"), {
        chart: { type: "bar", height: 320 },
        series: [{ name: "Average Score", data: data.avg_performance }],
        xaxis: { categories: data.avg_performance_labels },
        plotOptions: { bar: { columnWidth: "55%", borderRadius: 8 } },
        colors: ["#33c1ff"],
        dataLabels: { enabled: true }
      });
      charts.avgPerformance.render();
    } else {
      charts.avgPerformance.updateSeries([{ name: "Average Score", data: data.avg_performance }]);
      charts.avgPerformance.updateOptions({ xaxis: { categories: data.avg_performance_labels } });
    }

    // Trends
    if (!charts.trendsChart) {
      charts.trendsChart = new ApexCharts(document.querySelector("#performance-trends"), {
        chart: { type: "line", height: 320 },
        series: [{ name: "Average Score", data: data.performance_trends }],
        xaxis: { categories: data.performance_trends_labels },
        colors: ["#008000"],
        stroke: { curve: "smooth", width: 3 },
        markers: { size: 5 }
      });
      charts.trendsChart.render();
    } else {
      charts.trendsChart.updateSeries([{ name: "Average Score", data: data.performance_trends }]);
      charts.trendsChart.updateOptions({ xaxis: { categories: data.performance_trends_labels } });
    }
  }

  // 🔹 Toolbar Functions
  window.refreshDashboard = function() {
    const sem = semesters[currentSemesterIndex];
    toastr.info("Refreshing " + formatSemesterLabel(sem), "Info", { closeButton: true });
    loadDashboard(deptSelect.value, sem.id);
  }

  window.previousSemester = function() {
    if (currentSemesterIndex > 0) {
      currentSemesterIndex--;
      const sem = semesters[currentSemesterIndex];
      toastr.success("Loaded " + formatSemesterLabel(sem), "Previous Semester", { closeButton: true });
      loadDashboard(deptSelect.value, sem.id);
    } else {
      toastr.error("No previous semester available", "Error", { closeButton: true });
    }
  }

  window.nextSemester = function() {
    if (currentSemesterIndex < maxSemester - 1) {
      currentSemesterIndex++;
      const sem = semesters[currentSemesterIndex];
      toastr.success("Loaded " + formatSemesterLabel(sem), "Next Semester", { closeButton: true });
      loadDashboard(deptSelect.value, sem.id);
    } else {
      toastr.error("No next semester available", "Error", { closeButton: true });
    }
  }

  // 🔹 Load default semester (first in array or is_default = 1)
  let defaultIndex = semesters.findIndex(s => s.is_default == 1);
  if (defaultIndex >= 0) currentSemesterIndex = defaultIndex;
  loadDashboard("", semesters[currentSemesterIndex].id);

  deptSelect.addEventListener('change', function() {
    loadDashboard(this.value, semesters[currentSemesterIndex].id);
  });

  // Bootstrap tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
});
</script>

</body>

</html>