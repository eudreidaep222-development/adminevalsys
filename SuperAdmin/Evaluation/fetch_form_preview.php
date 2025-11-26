<?php
session_start();
include '../../1database_conn/connection.php';

header('Content-Type: application/json');

// Authorization Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin'){
    echo json_encode(['success' => false, 'error' => 'Authorization failed. Super Admin access required.']);
    exit();
}

// Input validation
if (!isset($_GET['form_id']) || !is_numeric($_GET['form_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing or invalid form ID.']);
    exit();
}

$form_id = (int)$_GET['form_id'];

// SQL to fetch sections and criteria (assuming form_sections and section_criteria tables)
$sql = "SELECT 
            fs.section_name, 
            fs.section_description, 
            sc.criteria_description,
            sc.max_score
        FROM form_sections fs
        LEFT JOIN section_criteria sc ON fs.id = sc.section_id
        WHERE fs.evaluation_form_id = ?
        ORDER BY fs.section_order ASC, sc.criteria_order ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $form_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No criteria or sections found for this form template.']);
    $stmt->close();
    $conn->close();
    exit();
}

// Build HTML Output
$html_output = '';
$current_section = '';
$section_count = 0;

while ($row = $result->fetch_assoc()) {
    if ($row['section_name'] !== $current_section) {
        // Close previous section if it exists
        if ($current_section !== '') {
            $html_output .= '</ul></div>';
        }

        // Start new section
        $section_count++;
        $current_section = $row['section_name'];
        $html_output .= '<div class="mb-4 p-3 border rounded">';
        $html_output .= '<h5>' . $section_count . '. ' . htmlspecialchars($row['section_name']) . '</h5>';
        if (!empty($row['section_description'])) {
             $html_output .= '<p class="text-muted small">' . htmlspecialchars($row['section_description']) . '</p>';
        }
        $html_output .= '<ul class="list-group list-group-flush">';
    }

    // Add criteria
    if ($row['criteria_description']) {
        $html_output .= '<li class="list-group-item d-flex justify-content-between align-items-center bg-light">';
        $html_output .= '<span>' . htmlspecialchars($row['criteria_description']) . '</span>';
        $html_output .= '<span class="badge bg-secondary">Max Score: ' . $row['max_score'] . '</span>';
        $html_output .= '</li>';
    }
}

// Close the very last section
if ($current_section !== '') {
    $html_output .= '</ul></div>';
}

$stmt->close();
$conn->close();

// Return the JSON response with the HTML content
echo json_encode(['success' => true, 'html_content' => $html_output]);

?>