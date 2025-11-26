<?php
require_once '../../1database_conn/connection.php';
session_start();

// --------------------------------------------------
// Ensure only faculty can access
// --------------------------------------------------
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'faculty') {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

// --------------------------------------------------
// Get form_id and optional assignment_id from GET
// --------------------------------------------------
$form_id = $_GET['form_id'] ?? null;
$assignment_id = $_GET['assignment_id'] ?? null;
$assigned_version = null;

// --------------------------------------------------
// Step 1: Get assigned version if assignment exists
// --------------------------------------------------
if ($assignment_id) {
    $stmt = $conn->prepare("
        SELECT ea.form_id, ef.versions AS assigned_version
        FROM evaluation_assignments ea
        INNER JOIN evaluation_forms ef ON ef.id = ea.form_id
        WHERE ea.id = ? AND ea.evaluator_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $assignment_id, $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $form_id = $row['form_id'];
        $assigned_version = (int)$row['assigned_version'];
    }
    $stmt->close();
}

// --------------------------------------------------
// Step 2: Load the correct form version
// --------------------------------------------------
if ($assigned_version !== null) {
    // Load assigned version
    $stmt = $conn->prepare("
        SELECT id, form_name, versions, version_name_snapshot, evaluation_weight
        FROM evaluation_forms
        WHERE id = ? AND versions = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $form_id, $assigned_version);
} else {
    // Load active version fallback
    $stmt = $conn->prepare("
        SELECT id, form_name, versions, version_name_snapshot, evaluation_weight
        FROM evaluation_forms
        WHERE id = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("i", $form_id);
}
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();
$stmt->close();

if (!$form) {
    echo "<div class='alert alert-danger'>Error: Form ID {$form_id} not found.</div>";
    exit;
}

// Use assigned version if exists, otherwise fallback to form's versions column
$version_to_load = $assigned_version ?? (int)$form['versions'];
$weight = htmlspecialchars($form['evaluation_weight']);
$version_name = !empty($form['version_name_snapshot']) 
    ? htmlspecialchars($form['version_name_snapshot']) 
    : "Version " . $version_to_load;

// --------------------------------------------------
// Step 3: Load categories for this version
// --------------------------------------------------
$stmt = $conn->prepare("
    SELECT id, category_name, category_percentage
    FROM evaluation_categories
    WHERE form_id = ?
    ORDER BY id ASC
");
$stmt->bind_param("i", $form_id,);
$stmt->execute();
$categories = $stmt->get_result();
$stmt->close();

if ($categories->num_rows === 0) {
    echo "<div class='alert alert-info'>No categories found for this form version.</div>";
    exit;
}

// --------------------------------------------------
// Step 4: Display form header
// --------------------------------------------------
echo "<div class='alert alert-secondary'>
        <strong>Form:</strong> " . htmlspecialchars($form['form_name']) . "
        <br><strong>Version:</strong> " . $version_name . "
        <br><strong>Weight:</strong> " . $weight . "%
      </div>";

// --------------------------------------------------
// Step 5: Display categories & criteria with 5 radio buttons
// --------------------------------------------------
// --------------------------------------------------
// Step 5: Display categories & criteria with 5 numeric radio buttons and label headers
// --------------------------------------------------
$rating_labels = [
    1 => " (1) Strongly Disagree",
    2 => " (2) Disagree",
    3 => " (3) Neutral",
    4 => " (4) Agree",
    5 => " (5) Strongly Agree"
];

echo "<form id='evaluation-form'>";
while ($cat = $categories->fetch_assoc()):
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-light'>
            <strong>" . htmlspecialchars($cat['category_name']) . " (" . $cat['category_percentage'] . "%)</strong>
          </div>";

    echo "<div class='card-body p-0'>";
    echo "<table class='table table-bordered mb-0'>
            <thead>
                <tr>
                    <th>Criterion</th>";
    // Add label headers above radio buttons
    foreach ($rating_labels as $label) {
        echo "<th class='text-center'>" . htmlspecialchars($label) . "</th>";
    }
    echo "  </tr>
            </thead>
            <tbody>";

    // Load criteria filtered by version
    $stmt2 = $conn->prepare("
        SELECT id, output_name
        FROM evaluation_form_details
        WHERE form_id = ? AND category_id = ?
        ORDER BY id ASC
    ");
    $stmt2->bind_param("ii", $form_id, $cat['id']);
    $stmt2->execute();
    $criteria = $stmt2->get_result();
    $stmt2->close();

    while ($c = $criteria->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($c['output_name']) . "</td>";

        // Create numeric radio buttons under each header
        for ($i = 1; $i <= 5; $i++) {
            echo "<td class='text-center'>
                    <input type='radio' name='criterion_{$c['id']}' value='$i' required>
                  </td>";
        }

        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div></div>";
endwhile;

echo "<button type='submit' class='btn btn-primary'>Submit Evaluation</button>";
echo "</form>";

?>
