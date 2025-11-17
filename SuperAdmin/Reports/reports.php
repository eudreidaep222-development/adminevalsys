<?php
include '../../1database_conn/connection.php';

$detailsQuery = $conn->query("
    SELECT ea.id as assignment_id, 
           ea.form_id,
           u1.fullName as evaluator, 
           u2.fullName as evaluatee,
           d.department_name as department, 
           f.form_name as form,
           ea.assigned_at
    FROM evaluation_assignments ea
    LEFT JOIN users u1 ON ea.evaluator_id=u1.id
    LEFT JOIN users u2 ON ea.evaluatee_id=u2.id
    LEFT JOIN departments d ON ea.department_id=d.id
    LEFT JOIN evaluation_forms f ON ea.form_id=f.id
    WHERE ea.status = 'completed'
");


$details = [];
while($row = $detailsQuery->fetch_assoc()){
    $form_id = $row['form_id'] ?? 0; // ensure it's set
    $categories = [];

    if($form_id){
        $categoriesQuery = $conn->query("
            SELECT ec.id as category_id, ec.category_name, ec.category_percentage
            FROM evaluation_categories ec
            WHERE ec.form_id = $form_id
        ");

        while($cat = $categoriesQuery->fetch_assoc()){
            $category_id = $cat['category_id'] ?? 0;
            $details2 = [];

            if($category_id){
                $detailsQuery2 = $conn->query("
                    SELECT output_name as output, success_indicator, accomplishment, 
                           q_rating as q, e_rating as e, t_rating as t, a_rating as a
                    FROM evaluation_form_details
                    WHERE category_id = $category_id
                ");
                while($d = $detailsQuery2->fetch_assoc()){
                    $details2[] = $d;
                }
            }

            $categories[] = [
                'category_id' => $category_id,
                'category_name' => $cat['category_name'],
                'percentage' => $cat['category_percentage'],
                'details' => $details2
            ];
        }
    }

    $details[] = [
        'assignment_id' => $row['assignment_id'],
        'evaluator' => $row['evaluator'],
        'evaluatee' => $row['evaluatee'],
        'department' => $row['department'],
        'form' => $row['form'],
        'assigned_at' => $row['assigned_at'],
        'categories' => $categories,
        'actions' => '<button class="btn btn-sm btn-info" onclick="overviewForm('.$row['assignment_id'].')">Overview</button>
                      <button class="btn btn-sm btn-primary" onclick="viewPDF('.$row['assignment_id'].')">PDF</button>'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports Module</title>
<link rel="shortcut icon" href="../../assets/img/logo-small.png">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
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
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Reports Module</h3>
                        <div class="toolbar">
                            <button class="tool-btn" title="Refresh" id="refreshReports">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <select class="tool-select" id="departmentFilter">
                                <option value="">All Departments</option>
                                <?php
                                $departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name ASC");
                                while($dept = $departments->fetch_assoc()):
                                ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <select class="tool-select" id="semesterFilter">
                                <option value="">All Academic Periods</option>
                                <?php
                                $semesters = $conn->query("SELECT * FROM academic_list ORDER BY start_date ASC");
                                while($sem = $semesters->fetch_assoc()):
                                ?>
                                <option value="<?= $sem['id'] ?>">S.Y. <?= $sem['school_year'] ?> • Sem <?= $sem['semester'] ?></option>
                                <?php endwhile; ?>
                            </select>
                            <select class="tool-select" id="formFilter">
                                <option value="">All Forms</option>
                                <?php
                                $forms = $conn->query("SELECT id, form_name FROM evaluation_forms WHERE is_active=1 ORDER BY form_name ASC");
                                while($form = $forms->fetch_assoc()):
                                ?>
                                <option value="<?= $form['id'] ?>"><?= htmlspecialchars($form['form_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-comman">
                    <div class="card-body">
                        <h6>Total Completed Evaluations</h6>
                        <h3 id="totalCompleted">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-comman">
                    <div class="card-body">
                        <h6>Average Performance</h6>
                        <h3 id="avgPerformance">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-comman">
                    <div class="card-body">
                        <h6>Departments Covered</h6>
                        <h3 id="deptCount">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card card-chart">
                    <div class="card-header"><h5>Department-wise Average Scores</h5></div>
                    <div class="card-body"><div id="deptChart"></div></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-chart">
                    <div class="card-header"><h5>Evaluation Trends Over Semesters</h5></div>
                    <div class="card-body"><div id="trendChart"></div></div>
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5>Detailed Evaluations</h5></div>
                    <div class="card-body">
                        <table class="table table-bordered" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Evaluator</th>
                                    <th>Evaluatee</th>
                                    <th>Department</th>
                                    <th>Form</th>
                                    <th>Assigned At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Modal -->
        <div class="modal fade" id="overviewModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Evaluation Overview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body" id="overviewContent"></div>
            </div>
          </div>
        </div>

    </div>
</div>

<?php include '../../Layout Components/footer.php'; ?>
</div>

<script src="../../assets/js/jquery-3.6.0.min.js"></script>
<script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
<script src="../../assets/plugins/apexchart/apexcharts.min.js"></script>
<script src="../../assets/plugins/simple-calendar/jquery.simple-calendar.js"></script>
<script src="../../assets/js/calander.js"></script>
<script src="../../assets/js/script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="../../assets/js/alert-toast.js"></script>

<script>
const details = <?= json_encode($details) ?>;

document.addEventListener("DOMContentLoaded", function() {
    const deptSelect = document.getElementById('departmentFilter');
    const semSelect = document.getElementById('semesterFilter');
    const formSelect = document.getElementById('formFilter');
    let charts = {};

    function loadReports() {
        const dept = deptSelect.value;
        const sem = semSelect.value;
        const form = formSelect.value;

        fetch(`reports-data.php?department_id=${dept}&semesterFilter=${sem}&formFilter=${form}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) { toastr.error(data.error); return; }

            // Summary
            document.getElementById('totalCompleted').textContent = data.totalCompleted ?? 0;
            document.getElementById('avgPerformance').textContent = data.avgScore ?? 0;
            document.getElementById('deptCount').textContent = data.deptCount ?? 0;

            // Charts
            if (!charts.deptChart) {
                charts.deptChart = new ApexCharts(document.querySelector("#deptChart"), {
                    chart: { type: 'bar', height: 320 },
                    series: [{ name: 'Avg Score', data: data.deptScores ?? [] }],
                    xaxis: { categories: data.deptLabels ?? [] },
                    colors: ['#33c1ff'],
                    dataLabels: { enabled: true }
                });
                charts.deptChart.render();
            } else {
                charts.deptChart.updateSeries([{ name: 'Avg Score', data: data.deptScores ?? [] }]);
                charts.deptChart.updateOptions({ xaxis: { categories: data.deptLabels ?? [] } });
            }

            if (!charts.trendChart) {
                charts.trendChart = new ApexCharts(document.querySelector("#trendChart"), {
                    chart: { type: 'line', height: 320 },
                    series: [{ name: 'Avg Score', data: data.trendScores ?? [] }],
                    xaxis: { categories: data.trendLabels ?? [] },
                    colors: ['#008000'],
                    stroke: { curve: 'smooth', width: 3 },
                    markers: { size: 5 }
                });
                charts.trendChart.render();
            } else {
                charts.trendChart.updateSeries([{ name: 'Avg Score', data: data.trendScores ?? [] }]);
                charts.trendChart.updateOptions({ xaxis: { categories: data.trendLabels ?? [] } });
            }

            // Detailed Table
            const tbody = document.querySelector('#reportTable tbody');
            tbody.innerHTML = '';
            if (Array.isArray(data.details)) {
                data.details.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.evaluator}</td>
                        <td>${row.evaluatee}</td>
                        <td>${row.department}</td>
                        <td>${row.form}</td>
                        <td>${row.assigned_at}</td>
                        <td>${row.actions}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

        })
        .catch(err => { toastr.error("Failed to load reports"); console.error(err); });
    }

    deptSelect.addEventListener('change', loadReports);
    semSelect.addEventListener('change', loadReports);
    formSelect.addEventListener('change', loadReports);
    document.getElementById('refreshReports').addEventListener('click', loadReports);

    loadReports();
});

function escapeHtml(str){
    if(!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
}

function overviewForm(assignmentId) {
    fetch(`overview-data.php?assignment_id=${assignmentId}`)
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            toastr.error(data.error);
            return;
        }
        const ass = data.assignment;
        const categories = data.categories;
        const results = data.results;

        let html = `<p><strong>Evaluator:</strong> ${escapeHtml(ass.evaluator)}<br>
                    <strong>Evaluatee:</strong> ${escapeHtml(ass.evaluatee)}<br>
                    <strong>Department:</strong> ${escapeHtml(ass.department_name)}<br>
                    <strong>Form:</strong> ${escapeHtml(ass.form_name)}<br>
                    <small>Submitted At: ${results.length ? escapeHtml(results[0].submitted_at) : 'N/A'}</small></p><hr/>`;

        // Show form details with scores aligned per row
        categories.forEach(cat => {
            html += `<h5>${escapeHtml(cat.category_name)} (${cat.percentage}%)</h5>`;
            html += `<div class="table-responsive"><table class="table table-bordered"><thead>
                        <tr>
                            <th>Output</th>
                            <th>Success Indicator</th>
                            <th>Actual Accomplishment</th>
                            <th class="qeta">Q</th>
                            <th class="qeta">E</th>
                            <th class="qeta">T</th>
                            <th class="qeta">A</th>
                        </tr>
                     </thead><tbody>`;

            cat.details.forEach((d, index) => {
                // Get corresponding result row if available
                const r = results[index] || { q: '', e: '', t: '', a: '' };
                html += `<tr>
                            <td>${escapeHtml(d.output_name)}</td>
                            <td>${escapeHtml(d.success_indicator)}</td>
                            <td>${escapeHtml(d.accomplishment)}</td>
                            <td class="qeta">${r.q}</td>
                            <td class="qeta">${r.e}</td>
                            <td class="qeta">${r.t}</td>
                            <td class="qeta">${r.a}</td>
                         </tr>`;
            });

            html += `</tbody></table></div>`;
        });

        $('#overviewContent').html(html);
        new bootstrap.Modal(document.getElementById('overviewModal')).show();
    })
    .catch(err => {
        toastr.error("Failed to load overview");
        console.error(err);
    });
}

function viewPDF(assignmentId){
    window.open('generate_pdf.php?assignment_id='+assignmentId, '_blank');
}
</script>
</body>
</html>
