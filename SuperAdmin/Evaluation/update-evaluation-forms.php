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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
                                    <div class="form-group" id="formVersionGroup" style="display: none;">
                                        <label for="formVersionSelect">Form Type</label>
                                        <select id="formVersionSelect" class="form-control" required>
                                            <option value="" disabled selected>-- Select Form Version --</option>
                                            <?php
                                            $result = $conn->query("
                                                SELECT ef.versions AS version_id, fv.version_label 
                                                FROM evaluation_forms ef 
                                                JOIN form_versions fv ON ef.versions = fv.version_id 
                                                GROUP BY ef.versions 
                                                ORDER BY ef.versions ASC
                                            ");
                                            if($result && $result->num_rows > 0){
                                                while($row = $result->fetch_assoc()){
                                                    echo "<option value='{$row['version_id']}'>Version {$row['version_id']} - {$row['version_label']}</option>";
                                                }
                                            } else {
                                                echo "<option value='' disabled>No Form Versions Found</option>";
                                            }
                                            ?>
                                        </select>
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

    <script src="../../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/feather.min.js"></script>
    <script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../../assets/plugins/apexchart/apexcharts.min.js"></script>
    <script src="../../assets/plugins/apexchart/chart-data.js"></script>
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
    const formControls = document.getElementById("formControls");
    const formNameGroup = document.getElementById("formNameGroup");
    const formVersionGroup = document.getElementById("formVersionGroup");
    const formVersionSelect = document.getElementById("formVersionSelect");

    window.currentFormVersion = '2'; // Default

    // 1. Load Forms List
    academicSelect.addEventListener("change", function () {
        const academicId = this.value;
        selectForm.innerHTML = '<option value="">-- Select a Form --</option>';
        selectForm.disabled = true;
        formControls.style.display = 'none';
        formNameGroup.style.display = 'none';
        formVersionGroup.style.display = 'none'; // Hide version
        categoriesContainer.innerHTML = '';
        
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
                } else {
                    toastr.info("No forms found for this period.");
                }
            })
            .catch(err => console.error(err));
    });

    // 2. Load Form Details
    selectForm.addEventListener("change", function () {
        const formId = this.value;
        if (!formId) { 
            categoriesContainer.innerHTML = ''; 
            formControls.style.display = 'none';
            formNameGroup.style.display = 'none';
            formVersionGroup.style.display = 'none';
            return; 
        }

        fetch("get_form_details.php?id=" + formId)
            .then(r => r.json())
            .then(data => {
                isInitialLoad = true;
                // Set Version
                window.currentFormVersion = (data.versions || data.version) ? String(data.versions || data.version) : '2';   
                const versionSelect = document.getElementById("formVersionSelect");             
                if(versionSelect) formVersionSelect.value = window.currentFormVersion;
 
                formNameEl.value = data.form_name || '';
                
                // Show UI Elements
                formControls.style.display = 'block';
                formNameGroup.style.display = 'block';
                formVersionGroup.style.display = 'block'; // Show dropdown
                
                categoriesContainer.innerHTML = '';

                if (data.categories && data.categories.length > 0) {
                    data.categories.forEach(cat => {
                        addCategory(
                            cat.category_name || '', 
                            parseFloat(cat.category_percentage || 0), 
                            cat.criteria || [], 
                            window.currentFormVersion
                        );
                    });
                } else {
                    addCategory('', 0, [], window.currentFormVersion);
                }
                
                updateLivePreview();
            });
    });

    // 3. Handle Version Change
    formVersionSelect.addEventListener("change", function() {
        if(confirm("Changing the form version will clear current categories. Proceed?")) {
            window.currentFormVersion = this.value;
            categoriesContainer.innerHTML = '';
            addCategory('', 0, [], window.currentFormVersion);
            updateLivePreview();
        } else {
            this.value = window.currentFormVersion; // Revert
        }
    });

    // 4. Add Category Logic
    function addCategory(name = '', pct = 0, criteria = [], version = '2') {
        const isV1 = String(version) === '1';
        const div = document.createElement('div');
        div.classList.add('category');
        div.dataset.version = isV1 ? 'v1' : 'v2';

        let tableHeader = '';
        if (isV1) {
            tableHeader = `<tr>
                <th style="width: 50%;">Criterion</th>
                <th class="text-center" style="width: 8%;">1</th>
                <th class="text-center" style="width: 8%;">2</th>
                <th class="text-center" style="width: 8%;">3</th>
                <th class="text-center" style="width: 8%;">4</th>
                <th class="text-center" style="width: 8%;">5</th>
                <th style="width: 10%;">Action</th>
            </tr>`;
        } else {
            tableHeader = `<tr><th>Output</th><th>Success Indicator</th><th>Accomplishment</th><th>Q</th><th>E</th><th>T</th><th>A</th><th>Action</th></tr>`;
        }

        const pctDisplay = isV1 ? 'style="display:none;"' : '';

        div.innerHTML = `
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title text-primary">Category</h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-category">
                            <i class="fa fa-trash"></i> Remove
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <label>Category Name</label>
                            <input type="text" class="form-control category-name" value="${name}" required>
                        </div>
                        <div class="col-md-4" ${pctDisplay}>
                            <label>Percentage (%)</label>
                            <input type="number" class="form-control category-percentage" value="${pct}" min="0" max="100">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered mt-3">
                            <thead class="bg-light">${tableHeader}</thead>
                            <tbody class="criteriaTable"></tbody>
                        </table>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-secondary add-criteria mt-2">
                        <i class="fa fa-plus"></i> Add Criterion
                    </button>
                </div>
            </div>`;

        const criteriaTable = div.querySelector('.criteriaTable');
        
        if (criteria.length > 0) {
            criteria.forEach(c => addCategoryRow(criteriaTable, c, isV1));
        } else {
            addCategoryRow(criteriaTable, {}, isV1);
        }

        div.querySelector('.add-criteria').addEventListener('click', () => addCategoryRow(criteriaTable, {}, isV1));
        div.querySelector('.remove-category').addEventListener('click', () => { 
            if(confirm('Delete this category?')) { div.remove(); updateLivePreview(); }
        });
        div.querySelector('.category-name').addEventListener('input', updateLivePreview);

        categoriesContainer.appendChild(div);
    }

    // 5. Add Row Logic
    function addCategoryRow(table, crit = {}, isV1 = false) {
        const row = document.createElement('tr');
        const text = crit.criterion || crit.output_name || ''; 

        if (isV1) {
            row.innerHTML = `
                <td><textarea class="form-control criterion-input" rows="1" placeholder="Enter criterion...">${text}</textarea></td>
                <td class="text-center"><input type="radio" disabled></td>
                <td class="text-center"><input type="radio" disabled></td>
                <td class="text-center"><input type="radio" disabled></td>
                <td class="text-center"><input type="radio" disabled></td>
                <td class="text-center"><input type="radio" disabled></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-btn"><i class="fa fa-times"></i></button></td>`;
        } else {
            row.innerHTML = `
                <td><textarea class="form-control" rows="1">${crit.output_name || ''}</textarea></td>
                <td><textarea class="form-control" rows="1">${crit.success_indicator || ''}</textarea></td>
                <td><textarea class="form-control" rows="1">${crit.accomplishment || ''}</textarea></td>
                <td><input type="number" class="form-control" style="width:50px" value="${crit.q_rating || 0}"></td>
                <td><input type="number" class="form-control" style="width:50px" value="${crit.e_rating || 0}"></td>
                <td><input type="number" class="form-control" style="width:50px" value="${crit.t_rating || 0}"></td>
                <td><input type="number" class="form-control" style="width:50px" value="${crit.a_rating || 0}" readonly></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-btn"><i class="fa fa-times"></i></button></td>`;
        }
        
        table.appendChild(row);

        row.querySelector('.remove-btn').addEventListener('click', () => { 
            row.remove(); 
            updateLivePreview(); 
        });

        row.querySelectorAll('textarea, input').forEach(el => el.addEventListener('input', updateLivePreview));
    }

    // 6. Update Live Preview
    function updateLivePreview() {
        const preview = document.getElementById('previewContent');
        const fName = formNameEl.value || 'Form Name';
        const academicSelect = document.getElementById('academicSelect');
        const academicText = academicSelect.options[academicSelect.selectedIndex]?.text || '';

        preview.innerHTML = `<h3>${fName}</h3><p class="text-muted">${academicText}</p><hr>`;

        document.querySelectorAll('.category').forEach(cat => {
            const version = cat.dataset.version;
            const catName = cat.querySelector('.category-name').value || 'Untitled Category';
            const catPct = cat.querySelector('.category-percentage').value;
            
            const headerStr = version === 'v1' ? catName : `${catName} (${catPct}%)`;
            
            const previewDiv = document.createElement('div');
            previewDiv.innerHTML = `<h5 class="mt-4 text-primary">${headerStr}</h5>`;
            
            const table = document.createElement('table');
            table.classList.add('table', 'table-bordered', 'table-sm');
            
            let thead = '', tbody = '<tbody>';
            
            if (version === 'v1') {
                thead = `<thead><tr class="text-center bg-light"><th style="width:50%">Criterion</th><th>1</th><th>2</th><th>3</th><th>4</th><th>5</th></tr></thead>`;
                cat.querySelectorAll('.criteriaTable tr').forEach(row => {
                    const val = row.querySelector('.criterion-input').value;
                    tbody += `<tr><td>${val}</td><td class="text-center"><input type="radio" disabled></td><td class="text-center"><input type="radio" disabled></td><td class="text-center"><input type="radio" disabled></td><td class="text-center"><input type="radio" disabled></td><td class="text-center"><input type="radio" disabled></td></tr>`;
                });
            } else {
                thead = `<thead><tr class="bg-light"><th>Output</th><th>Success</th><th>Accomplishment</th><th>Q</th><th>E</th><th>T</th><th>A</th></tr></thead>`;
                cat.querySelectorAll('.criteriaTable tr').forEach(row => {
                    const fields = row.querySelectorAll('textarea, input');
                    if(fields.length >= 7) {
                        tbody += `<tr><td>${fields[0].value}</td><td>${fields[1].value}</td><td>${fields[2].value}</td><td>${fields[3].value}</td><td>${fields[4].value}</td><td>${fields[5].value}</td><td>${fields[6].value}</td></tr>`;
                    }
                });
            }
            
            tbody += '</tbody>';
            table.innerHTML = thead + tbody;
            previewDiv.appendChild(table);
            preview.appendChild(previewDiv);
        });
    }

    // 7. Global Listeners
    document.getElementById('addCategoryBtn').addEventListener('click', function() {
        addCategory('', 0, [], window.currentFormVersion);
    });
    
    document.getElementById('formName').addEventListener('input', updateLivePreview);
    
    document.getElementById('updateFormBtn').addEventListener('click', function() {
        if(!confirm("Save changes to this form?")) return;

        const formData = {
            form_id: selectForm.value,
            form_name: formNameEl.value,
            academic_id: parseInt(academicSelect.value),
            versions: window.currentFormVersion,
            categories: [],
            csrf_token: "<?php echo $_SESSION['csrf_token']; ?>"
        };

        document.querySelectorAll('.category').forEach(cat => {
            const catName = cat.querySelector('.category-name').value;
            const catPct = parseFloat(cat.querySelector('.category-percentage').value) || 0;
            const isV1 = cat.dataset.version === 'v1';
            const criteria = [];

            cat.querySelectorAll('.criteriaTable tr').forEach(row => {
                if(isV1) {
                    const text = row.querySelector('.criterion-input').value;
                    if(text) criteria.push({ criterion: text });
                } else {
                    const inputs = row.querySelectorAll('textarea, input');
                    criteria.push({
                        output: inputs[0].value, success_indicator: inputs[1].value, accomplishment: inputs[2].value,
                        q_rating: inputs[3].value, e_rating: inputs[4].value, t_rating: inputs[5].value, a_rating: inputs[6].value
                    });
                }
            });

            formData.categories.push({
                categoryName: catName,
                categoryPercentage: catPct,
                criteria: criteria
            });
        });

        fetch('save_form.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                toastr.success("Form updated successfully!");
                
                // Clear categories
                categoriesContainer.innerHTML = '';

                // Optionally reset version & formName
                formNameEl.value = '';
                formVersionSelect.value = '';
                window.currentFormVersion = '2';
                formControls.style.display = 'none';
                formNameGroup.style.display = 'none';
                formVersionGroup.style.display = 'none';

                // Optionally reload the forms list for the selected academic period
                academicSelect.dispatchEvent(new Event('change'));
            } else {
                toastr.error("Error: " + res.error);
            }
        })
        .catch(err => toastr.error("Failed to save changes."));
    });
});
</script>


</body>

</html>