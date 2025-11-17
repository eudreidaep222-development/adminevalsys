<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Add Evaluation Forms</title>
    <link rel="shortcut icon" href="../../assets/img/logo-small.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Work Performance Review</h3>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Side: Form -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title">Employee Evaluation</h5>
                                <button id="createNewFormBtn" class="btn btn-rounded btn-outline-success" data-bs-toggle="tooltip" data-bs-placement="top" title="Create New Form">
                                    <box-icon type='solid' name='file-plus'></box-icon>
                                </button>
                            </div>
                            <div class="card-body">
                                <form id="evaluationForm">
                                    <!-- Form Name -->
                                    <div class="form-group">
                                        <label for="formName">Form Name</label>
                                        <input type="text" class="form-control" id="formName" placeholder="Enter Form Name" required>
                                    </div>

                                    <!-- Academic Period -->
                                    <div class="form-group">
                                        <label for="academicSelect">Academic Period</label>
                                        <select id="academicSelect" class="form-control" required>
                                            <option value="" disabled selected>-- Select Academic Period --</option>
                                            <?php
                                            require_once "../../1database_conn/connection.php";
                                            $result = $conn->query("SELECT id, school_year, semester FROM academic_list");
                                            if($result && $result->num_rows > 0){
                                                while($row = $result->fetch_assoc()){
                                                    $semText = $row['semester']==1 ? "1st Semester" : ($row['semester']==2 ? "2nd Semester" : "Semester ".$row['semester']);
                                                    echo "<option value='{$row['id']}'>{$row['school_year']} - {$semText}</option>";
                                                }
                                            } else {
                                                echo "<option value='' disabled>No academic periods found</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <!-- Form Version -->
                                    <div class="form-group">
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

                                    <!-- Categories Container (Dynamic) -->
                                    <div id="categoriesContainer"></div>

                                    <!-- Action Buttons -->
                                    <div class="text-end mt-3">
                                        <button type="button" class="btn btn-outline-info" id="addCategoryBtn">Add Category</button>
                                        <button type="button" class="btn btn-outline-warning" id="addCommentBtn">Add Comment Section</button>
                                        <button type="button" class="btn btn-outline-success" id="createFormBtn">Save Form</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
let totalCategoryPercentage = 0;

// Update the total category percentage
function updateTotalPercentage() {
    totalCategoryPercentage = 0;
    document.querySelectorAll(".category-percentage").forEach(input => {
        totalCategoryPercentage += parseFloat(input.value) || 0;
    });
}

// Function to update the live preview
function updateLivePreview() {
    const formName = document.getElementById("formName").value;
    const formVersion = document.getElementById("formVersionSelect").value;

    // Get selected academic period text
    const academicSelect = document.getElementById("academicSelect");
    let academicPeriod = '';
    if (academicSelect.value) {
        academicPeriod = academicSelect.options[academicSelect.selectedIndex].text;
    }

    updateTotalPercentage();

    const categories = [];

    document.querySelectorAll(".category").forEach(categoryDiv => {
        const categoryName = categoryDiv.querySelector(".category-name").value;
        const criteria = [];

        categoryDiv.querySelectorAll(".criteriaTable tr").forEach((row, rowIndex) => {
            if (formVersion === "1") {
                const input = row.querySelector(".criterion-input");
                if (input) criteria.push({ criterion: input.value });
            } else {
                const fields = row.querySelectorAll("textarea, input");
                criteria.push({
                    output: fields[0].value,
                    success_indicator: fields[1].value,
                    accomplishment: fields[2].value,
                    q_rating: parseFloat(fields[3].value) || 0,
                    e_rating: parseFloat(fields[4].value) || 0,
                    t_rating: parseFloat(fields[5].value) || 0,
                    a_rating: parseFloat(fields[6].value) || 0
                });
            }
        });

        categories.push({ categoryName, criteria });
    });

    // Clear preview
    const previewContent = document.getElementById("previewContent");
    previewContent.innerHTML = "";

    // Form Name in Preview
    const previewFormName = document.createElement("h3");
    previewFormName.textContent = formName;
    previewContent.appendChild(previewFormName);

    // Academic Period in Preview
    if (academicPeriod) {
        const previewAcademic = document.createElement("p");
        previewAcademic.textContent = "Academic Period: " + academicPeriod;
        previewContent.appendChild(previewAcademic);
    }

    // Display categories
    categories.forEach((category, catIndex) => {
        const previewCategory = document.createElement("div");

        if (formVersion === "1") {
            // Version 1: Criterion + 5 radio buttons
            let criteriaRows = "";
            category.criteria.forEach((c, index) => {
                criteriaRows += `
                    <tr>
                        <td>${c.criterion}</td>
                        <td><input type="radio" name="cat${catIndex}_crit${index}" value="1"></td>
                        <td><input type="radio" name="cat${catIndex}_crit${index}" value="2"></td>
                        <td><input type="radio" name="cat${catIndex}_crit${index}" value="3"></td>
                        <td><input type="radio" name="cat${catIndex}_crit${index}" value="4"></td>
                        <td><input type="radio" name="cat${catIndex}_crit${index}" value="5"></td>
                    </tr>
                `;
            });

            previewCategory.innerHTML = `
                <h4>${category.categoryName}</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Criterion</th>
                            <th>1<br>(Severely Disagree)</th>
                            <th>2<br>(Disagree)</th>
                            <th>3<br>(Neutral)</th>
                            <th>4<br>(Agree)</th>
                            <th>5<br>(Severely Agree)</th>
                        </tr>
                    </thead>
                    <tbody>${criteriaRows}</tbody>
                </table>
            `;
        } else {
            // Version 2: Full layout
            let criteriaRows = "";
            category.criteria.forEach(c => {
                criteriaRows += `
                    <tr>
                        <td>${c.output}</td>
                        <td>${c.success_indicator}</td>
                        <td>${c.accomplishment}</td>
                        <td>${c.q_rating}</td>
                        <td>${c.e_rating}</td>
                        <td>${c.t_rating}</td>
                        <td>${c.a_rating}</td>
                    </tr>
                `;
            });

            previewCategory.innerHTML = `
                <h4>${category.categoryName}</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Output</th>
                            <th>Success Indicator</th>
                            <th>Actual Accomplishment</th>
                            <th style="width:50px;">Q</th>
                            <th style="width:50px;">E</th>
                            <th style="width:50px;">T</th>
                            <th style="width:50px;">A</th>
                        </tr>
                    </thead>
                    <tbody>${criteriaRows}</tbody>
                </table>
            `;
        }

        previewContent.appendChild(previewCategory);
    });

    // Show comment sections at the bottom of preview
    document.querySelectorAll(".comment-section").forEach((textarea, index) => {
        const commentPreview = document.createElement("div");
        commentPreview.innerHTML = `<h5>Comments</h5><p>${textarea.value}</p>`;
        previewContent.appendChild(commentPreview);
    });


    const formCommentsEl = document.getElementById("formComments");
    if (formCommentsEl && formCommentsEl.value.trim() !== "") {
        const previewComments = document.createElement("div");
        previewComments.innerHTML = `<h5>Comments:</h5><p>${formCommentsEl.value}</p>`;
        previewContent.appendChild(previewComments);
    }

}

// Add Category Button
document.getElementById("addCategoryBtn").addEventListener("click", function () {
    const categoryContainer = document.getElementById("categoriesContainer");
    const categoryDiv = document.createElement("div");
    const formVersion = document.getElementById("formVersionSelect").value;

    if (formVersion === "1") {
        // Version 1: Section + Criteria + 5 Radio Buttons
        categoryDiv.innerHTML = `
            <div class="card mt-3 category">
                <div class="card-body">
                    <label>Section Name:</label>
                    <input type="text" class="form-control category-name" required>
                    
                    <button type="button" class="btn btn-rounded btn-outline-danger removeCategoryBtn mt-2">Remove Section</button>
                    
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>Criterion</th>
                                <th>1</th>
                                <th>2</th>
                                <th>3</th>
                                <th>4</th>
                                <th>5</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="criteriaTable"></tbody>
                    </table>
                    
                    <button type="button" class="btn btn-rounded btn-outline-secondary addCriteriaBtn mt-2">Add Criterion</button>
                </div>
            </div>
        `;
    } else if (formVersion === "2") {
        // Version 2: Output / Success / Accomplishment / Q/E/T/A
        categoryDiv.innerHTML = `
            <div class="card mt-3 category">
                <div class="card-body">
                    <label>Category Name:</label>
                    <input type="text" class="form-control category-name" required>
                    
                    <button type="button" class="btn btn-rounded btn-outline-danger removeCategoryBtn mt-2">Remove Category</button>
                    
                    <table class="table mt-3">
                        <thead>
                            <tr>
                                <th>Output</th>
                                <th>Success Indicator</th>
                                <th>Actual Accomplishment</th>
                                <th>Q Rating</th>
                                <th>E Rating</th>
                                <th>T Rating</th>
                                <th>A Rating</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="criteriaTable"></tbody>
                    </table>
                    
                    <button type="button" class="btn btn-rounded btn-outline-secondary addCriteriaBtn mt-2">Add Criteria</button>
                </div>
            </div>
        `;
    }

    categoryContainer.appendChild(categoryDiv);
    const criteriaTable = categoryDiv.querySelector(".criteriaTable");

    // Automatically add first criterion for Version 1
    if (formVersion === "1") {
        const addFirstCriterion = () => {
            const newRow = document.createElement("tr");
            const critIndex = criteriaTable.rows.length;
            const catIndex = categoryContainer.children.length - 1;
            newRow.innerHTML = `
                <td><input type="text" class="form-control criterion-input" placeholder="Enter criterion" required></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="1"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="2"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="3"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="4"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="5"></td>
                <td><button type="button" class="btn btn-rounded btn-outline-danger removeCriteriaBtn">Remove</button></td>
            `;
            criteriaTable.appendChild(newRow);

            newRow.querySelector(".removeCriteriaBtn").addEventListener("click", function () {
                newRow.remove();
                updateLivePreview();
            });

            newRow.querySelector(".criterion-input").addEventListener("input", updateLivePreview);
        };

        addFirstCriterion();
    }

    // Add Criteria Button logic
    categoryDiv.querySelector(".addCriteriaBtn").addEventListener("click", function () {
        const newRow = document.createElement("tr");
        const critIndex = criteriaTable.rows.length;
        const catIndex = categoryContainer.children.length - 1;

        if (formVersion === "1") {
            newRow.innerHTML = `
                <td><input type="text" class="form-control criterion-input" placeholder="Enter criterion" required></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="1"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="2"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="3"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="4"></td>
                <td><input type="radio" name="cat${catIndex}_crit${critIndex}" value="5"></td>
                <td><button type="button" class="btn btn-rounded btn-outline-danger removeCriteriaBtn">Remove</button></td>
            `;
        } else if (formVersion === "2") {
            newRow.innerHTML = `
                <td><textarea class="form-control" name="output[]" required></textarea></td>
                <td><textarea class="form-control" name="success_indicator[]" required></textarea></td>
                <td><textarea class="form-control" name="accomplishment[]"></textarea></td>
                <td><input type="number" class="form-control" name="q_rating[]" min="0" max="5" step="0.01" value="0"></td>
                <td><input type="number" class="form-control" name="e_rating[]" min="0" max="5" step="0.01" value="0"></td>
                <td><input type="number" class="form-control" name="t_rating[]" min="0" max="5" step="0.01" value="0"></td>
                <td><input type="number" class="form-control" name="a_rating[]" min="0" max="5" step="0.01" value="0"></td>
                <td><button type="button" class="btn btn-rounded btn-outline-danger removeCriteriaBtn">Remove</button></td>
            `;
        }

        criteriaTable.appendChild(newRow);

        newRow.querySelector(".removeCriteriaBtn").addEventListener("click", function () {
            newRow.remove();
            updateLivePreview();
        });

        newRow.querySelectorAll("input, textarea").forEach(field => {
            field.addEventListener("input", updateLivePreview);
        });

        updateLivePreview();
    });

    // Remove Category Button
    categoryDiv.querySelector(".removeCategoryBtn").addEventListener("click", function () {
        categoryDiv.remove();
        updateLivePreview();
    });

    // Trigger live preview on all inputs in the category
    categoryDiv.querySelectorAll("input, textarea").forEach(input => {
        input.addEventListener("input", updateLivePreview);
    });

    updateLivePreview();
});

// Add Comment Section Button
document.getElementById("addCommentBtn").addEventListener("click", function() {
    const categoryContainer = document.getElementById("categoriesContainer");
    const commentDiv = document.createElement("div");
    commentDiv.classList.add("card", "mt-3"); // NOT a category with criteria
    commentDiv.innerHTML = `
        <div class="card-body">
            <label>Comment Section (Optional):</label>
            <textarea class="form-control comment-section" placeholder="Add comments here..." rows="4" style="resize: vertical;"></textarea>
            <button type="button" class="btn btn-rounded btn-outline-danger removeCommentBtn mt-2">Remove Comment</button>
        </div>
    `;
    categoryContainer.appendChild(commentDiv);

    // Remove comment section
    commentDiv.querySelector(".removeCommentBtn").addEventListener("click", function() {
        commentDiv.remove();
        updateLivePreview();
    });

    // Update live preview
    commentDiv.querySelector(".comment-section").addEventListener("input", updateLivePreview);

    updateLivePreview();
});

// Input listeners
document.getElementById("formName").addEventListener("input", updateLivePreview);
document.getElementById("academicSelect").addEventListener("change", updateLivePreview);


const formCommentsEl = document.getElementById("formComments");
if (formCommentsEl) formCommentsEl.addEventListener("input", updateLivePreview);


// Save Form
document.getElementById("createFormBtn").addEventListener("click", function () {
    const formVersion = document.getElementById("formVersionSelect").value;
    const formData = {
        form_name: document.getElementById("formName").value,
        academic_id: parseInt(document.getElementById("academicSelect").value),
        version: formVersion,
        categories: [],
        comments: []
    };

    // ✅ Collect optional comment sections only if they exist and not empty
    document.querySelectorAll(".comment-section").forEach((textarea) => {
        if (textarea && textarea.value.trim() !== "") {
            formData.comments.push(textarea.value.trim());
        }
    });

    document.querySelectorAll(".category").forEach(categoryDiv => {
        const categoryName = categoryDiv.querySelector(".category-name").value;
        const criteria = [];

        categoryDiv.querySelectorAll(".criteriaTable tr").forEach(row => {
            if (formVersion === "1") {
                const input = row.querySelector(".criterion-input");
                if (input) criteria.push({ criterion: input.value });
            } else {
                const fields = row.querySelectorAll("textarea, input");
                criteria.push({
                    output: fields[0].value,
                    success_indicator: fields[1].value,
                    accomplishment: fields[2].value,
                    q_rating: parseFloat(fields[3].value) || 0,
                    e_rating: parseFloat(fields[4].value) || 0,
                    t_rating: parseFloat(fields[5].value) || 0,
                    a_rating: parseFloat(fields[6].value) || 0
                });
            }
        });

        formData.categories.push({ categoryName, criteria });
    });

    fetch("save_form.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    // FIX_SNIPPET_SAVE_FORM
    .then(data => {
        if (data.success) {
            toastr.success("Form successfully created!", "Success", { closeButton: true, tapToDismiss: false });
            // Instead of window.location.reload(), just clear form and preview
            document.getElementById("evaluationForm").reset();
            document.getElementById("categoriesContainer").innerHTML = '';
            document.getElementById("previewContent").innerHTML = '';
        } else {
            toastr.error("Error: " + data.error, "Error", { closeButton: true, tapToDismiss: false });
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        toastr.error("An unexpected error occurred.", "Error", { closeButton: true, tapToDismiss: false });
    });
});

// FIX_SNIPPET_NEW_FORM_BTN
document.getElementById("createNewFormBtn").addEventListener("click", function () {
    // Clear all inputs and categories without reloading page
    document.getElementById("evaluationForm").reset();
    document.getElementById("categoriesContainer").innerHTML = '';
    document.getElementById("previewContent").innerHTML = '';
    toastr.info("New form ready.", "Info", { closeButton: true, tapToDismiss: false });
});

</script>

<script>
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })
</script>

</body>
</html>