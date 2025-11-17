<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Update Evaluation Form</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

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
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Work Performance Review</h3>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Side: Update Form -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title">Update Evaluation Form</h5>
                            </div>
                            <div class="card-body">
                                <form id="updateEvaluationForm">

                                    <!-- Academic Period -->
                                    <div class="form-group">
                                        <label for="academicSelect">Academic Period</label>
                                        <?php
                                        require_once "../../1database_conn/connection.php";
                                        $academicOptions = [];
                                        $result = $conn->query("SELECT id, school_year, semester FROM academic_list");

                                        if ($result && $result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $semText = $row['semester'] == 1 ? "1st Semester" : ($row['semester'] == 2 ? "2nd Semester" : "Semester " . $row['semester']);
                                                $academicOptions[] = [
                                                    'id' => $row['id'],
                                                    'label' => $row['school_year'] . " - " . $semText
                                                ];
                                            }
                                        }
                                        ?>
                                        <select id="academicSelect" class="form-control" required>
                                            <option value="" tabindex="-1" disabled selected>-- Select Academic Period --</option>
                                            <?php if (!empty($academicOptions)): ?>
                                                <?php foreach ($academicOptions as $opt): ?>
                                                    <option value="<?= $opt['id'] ?>"><?= htmlspecialchars($opt['label']) ?></option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="" disabled>No academic periods found</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Dropdown for selecting a form -->
                                    <div class="form-group">
                                        <label for="selectForm">Select Form</label>
                                        <select class="form-control" id="selectForm">
                                            <option value="" tabindex="-1" disabled selected hidden>-- Select a Form --</option>
                                        </select>
                                    </div>

                                    <!-- Form Name (hidden until form is selected) -->
                                    <div class="form-group" id="formNameGroup" style="display: none;">
                                        <label for="formName">Form Name</label>
                                        <input type="text" class="form-control" id="formName" required>
                                    </div>

                                    <!-- Categories / Criteria will load here -->
                                    <div id="categoriesContainer"></div>

                                    <!-- Controls -->
                                    <div class="text-end mt-3" id="formControls" style="display: none;">
                                        <button type="button" class="btn btn-rounded btn-outline-info" id="addCategoryBtn">Add Category</button>
                                        <button type="button" class="btn btn-rounded btn-outline-success" id="updateFormBtn">Update Form</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Sticky Preview -->
                    <div class="col-md-6">
                        <div class="card sticky-preview">
                            <div class="card-header">
                                <h5 class="card-title">Form Preview</h5>
                            </div>
                            <div class="card-body">
                                <div id="previewContent"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Rating Scales</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card rating-scale-card">
                                            <div class="card-header">
                                                <h6 class="card-title">Performance</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Range</th>
                                                            <th>Interpretation</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr><td>4.50 – 5.00</td><td>Outstanding (O)</td><td>Performance represented an extraordinary level...</td></tr>
                                                        <tr><td>3.50 – 4.49</td><td>Very Satisfactory (VS)</td><td>Performance exceeded expectation...</td></tr>
                                                        <tr><td>2.50 – 3.49</td><td>Satisfactory (S)</td><td>Performance met expectations...</td></tr>
                                                        <tr><td>1.50 – 2.49</td><td>Unsatisfactory (US)</td><td>Performance was consistently below...</td></tr>
                                                        <tr><td>1.00 – 1.49</td><td>Poor (P)</td><td>Performance failed to meet expectations...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card rating-scale-card">
                                            <div class="card-header">
                                                <h6 class="card-title">Efficiency</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Rating</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr><td>5</td><td>Target or quota exceeded by 30% or more</td></tr>
                                                        <tr><td>4</td><td>Target or quota exceeded by 15%-29%</td></tr>
                                                        <tr><td>3</td><td>Target or quota accomplished as expected...</td></tr>
                                                        <tr><td>2</td><td>Only 51%-99% of the target or quota accomplished</td></tr>
                                                        <tr><td>1</td><td>50% or less of the target or quota accomplished</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card rating-scale-card">
                                            <div class="card-header">
                                                <h6 class="card-title">Timeliness</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Rating</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr><td>5</td><td>Task completed within the first 30%...</td></tr>
                                                        <tr><td>4</td><td>Task completed in 15% to 29%...</td></tr>
                                                        <tr><td>3</td><td>Task completed on the deadline or up to 14%...</td></tr>
                                                        <tr><td>2</td><td>Task completed 51% to 99%...</td></tr>
                                                        <tr><td>1</td><td>Task not completed at all or completed 50%...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const academicSelect = document.getElementById("academicSelect");
    const selectForm = document.getElementById("selectForm");
    const formNameEl = document.getElementById("formName");
    const categoriesContainer = document.getElementById("categoriesContainer");

    window.currentFormVersion = null;

    academicSelect.addEventListener("change", function () {
        const academicId = this.value;
        selectForm.innerHTML = '<option value="">-- Select a Form --</option>';
        selectForm.disabled = true;
        if (!academicId) return;

        fetch("get_forms.php?academic_id=" + academicId)
            .then(r => r.json())
            .then(forms => {
                if (forms && forms.length) {
                    forms.forEach(f => {
                        const opt = document.createElement("option");
                        opt.value = f.id;
                        opt.textContent = f.form_name;
                        selectForm.appendChild(opt);
                    });
                    selectForm.disabled = false;
                }
            })
            .catch(err => console.error(err));
    });

    selectForm.addEventListener("change", function () {
        const formId = this.value;
        if (!formId) { categoriesContainer.innerHTML = ''; window.currentFormVersion = null; return; }

        fetch("get_form_details.php?id=" + formId)
            .then(r => r.json())
            .then(data => {
                window.currentFormVersion = parseInt(data.version) || 1;
                formNameEl.value = data.form_name || '';
                categoriesContainer.innerHTML = '';

                if (window.currentFormVersion === 1) {
                    renderV1Form(data);
                } else {
                    data.categories.forEach(cat => addCategory(cat.category_name || '', parseInt(cat.category_percentage || 0), cat.criteria || [], String(window.currentFormVersion)));
                }
                updateLivePreview();
            });
    });

    function renderV1Form(data) {
        const v1Div = document.createElement('div');
        v1Div.classList.add('card', 'mt-3');
        v1Div.innerHTML = `
            <div class="card-body">
                <h4>Criteria</h4>
                <table class="table" id="v1CriteriaTable">
                    <thead>
                        <tr>
                            <th>Criterion</th>
                            <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-outline-secondary mt-2" id="addV1Btn">Add Criterion</button>
            </div>`;
        categoriesContainer.appendChild(v1Div);

        const tbody = v1Div.querySelector('tbody');
        (data.categories[0]?.criteria || []).forEach(c => addV1Row(tbody, c.criterion || c.output_name || ''));
        v1Div.querySelector('#addV1Btn').addEventListener('click', () => addV1Row(tbody, ''));
    }

    function addV1Row(tbody, text) {
        const row = document.createElement('tr');
        const name = 'v1_' + Math.random().toString(36).slice(2,10);
        row.innerHTML = `
            <td><textarea class="form-control" required>${text}</textarea></td>
            <td><input type="radio" name="${name}" value="1"></td>
            <td><input type="radio" name="${name}" value="2"></td>
            <td><input type="radio" name="${name}" value="3"></td>
            <td><input type="radio" name="${name}" value="4"></td>
            <td><input type="radio" name="${name}" value="5"></td>
            <td><button type="button" class="btn btn-outline-danger remove-btn">Remove</button></td>`;
        tbody.appendChild(row);

        row.querySelector('.remove-btn').addEventListener('click', () => { row.remove(); updateLivePreview(); });
        row.querySelectorAll('textarea, input').forEach(el => el.addEventListener('input', updateLivePreview));
    }

    function addCategory(name = '', pct = 0, criteria = [], version = '2') {
        const isV1 = version === '1';
        const div = document.createElement('div');
        div.classList.add('category');
        div.dataset.version = isV1 ? 'v1' : 'v2';

        const tableHeader = isV1 ? `<tr><th>Criterion</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>Action</th></tr>`
            : `<tr><th>Output</th><th>Success Indicator</th><th>Actual Accomplishment</th><th>Comment</th><th>Q</th><th>E</th><th>T</th><th>A</th><th>Action</th></tr>`;

        div.innerHTML = `
            <div class="card mt-3">
                <div class="card-body">
                    <label>Category Name</label>
                    <input type="text" class="form-control category-name" value="${name}" required>
                    <label>Category Percentage</label>
                    <input type="number" class="form-control category-percentage" value="${pct}" min="0" max="100">
                    <button type="button" class="btn btn-outline-danger remove-category mt-2">Remove Category</button>
                    <table class="table mt-3">
                        <thead>${tableHeader}</thead>
                        <tbody class="criteriaTable"></tbody>
                    </table>
                    <button type="button" class="btn btn-outline-secondary add-criteria mt-2">Add Criteria</button>
                </div>
            </div>`;

        const criteriaTable = div.querySelector('.criteriaTable');
        (criteria || []).forEach(c => addCategoryRow(criteriaTable, c, isV1));

        div.querySelector('.add-criteria').addEventListener('click', () => addCategoryRow(criteriaTable, {}, isV1));
        div.querySelector('.remove-category').addEventListener('click', () => { div.remove(); updateLivePreview(); });

        categoriesContainer.appendChild(div);
    }

    function addCategoryRow(table, crit = {}, isV1 = false) {
        const row = document.createElement('tr');
        if (isV1) {
            const name = 'v1_' + Math.random().toString(36).slice(2,10);
            row.innerHTML = `
                <td><textarea class="form-control" required>${crit.criterion || crit.output_name || ''}</textarea></td>
                <td><input type="radio" name="${name}" value="1"></td>
                <td><input type="radio" name="${name}" value="2"></td>
                <td><input type="radio" name="${name}" value="3"></td>
                <td><input type="radio" name="${name}" value="4"></td>
                <td><input type="radio" name="${name}" value="5"></td>
                <td><button type="button" class="btn btn-outline-danger remove-btn">Remove</button></td>`;
        } else {
            row.innerHTML = `
                <td><textarea class="form-control">${crit.output_name || ''}</textarea></td>
                <td><textarea class="form-control">${crit.success_indicator || ''}</textarea></td>
                <td><textarea class="form-control">${crit.accomplishment || ''}</textarea></td>
                <td><textarea class="form-control">${crit.comment || ''}</textarea></td>
                <td><input type="number" class="form-control" value="${crit.q_rating || 0}"></td>
                <td><input type="number" class="form-control" value="${crit.e_rating || 0}"></td>
                <td><input type="number" class="form-control" value="${crit.t_rating || 0}"></td>
                <td><input type="number" class="form-control" value="${crit.a_rating || 0}"></td>
                <td><button type="button" class="btn btn-outline-danger remove-btn">Remove</button></td>`;
        }
        table.appendChild(row);

        row.querySelector('.remove-btn').addEventListener('click', () => { row.remove(); updateLivePreview(); });
        row.querySelectorAll('textarea, input').forEach(el => el.addEventListener('input', updateLivePreview));
    }

    function updateLivePreview() {
        const preview = document.getElementById('previewContent');
        preview.innerHTML = `<h3>${formNameEl.value}</h3>`;

        categoriesContainer.querySelectorAll('.category').forEach(cat => {
            const version = cat.dataset.version;
            const catName = cat.querySelector('.category-name').value;
            const catPct = cat.querySelector('.category-percentage').value;
            let html = `<h4>${catName} (${catPct}%)</h4><table class="table"><thead>`;
            if (version === 'v1') {
                html += `<tr><th>Criterion</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr>`;
                html += '<tbody>';
                cat.querySelectorAll('.criteriaTable tr').forEach((row, i) => {
                    const val = row.querySelector('textarea').value;
                    html += `<tr><td>${val}</td><td></td><td></td><td></td><td></td><td></td></tr>`;
                });
                html += '</tbody>';
            } else {
                html += `<tr><th>Output</th><th>Success Indicator</th><th>Actual Accomplishment</th><th>Comment</th><th>Q</th><th>E</th><th>T</th><th>A</th></tr>`;
                html += '<tbody>';
                cat.querySelectorAll('.criteriaTable tr').forEach(row => {
                    const cells = row.querySelectorAll('input, textarea');
                    html += `<tr>`;
                    cells.forEach(c => html += `<td>${c.value}</td>`);
                    html += `</tr>`;
                });
                html += '</tbody>';
            }
            html += '</table>';
            preview.innerHTML += html;
        });

        // V1 flat table preview
        const v1Table = document.querySelector('#v1CriteriaTable tbody');
        if (v1Table) {
            let html = '<h4>Criteria</h4><table class="table"><thead><tr><th>Criterion</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead><tbody>';
            v1Table.querySelectorAll('tr').forEach(row => {
                const val = row.querySelector('textarea').value;
                html += `<tr><td>${val}</td><td></td><td></td><td></td><td></td><td></td></tr>`;
            });
            html += '</tbody></table>';
            preview.innerHTML += html;
        }
    }
});
</script>


</body>

</html>