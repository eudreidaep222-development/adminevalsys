<?php
require '../../1database_conn/connection.php';
header('Content-Type: application/json');

$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
if ($assignment_id <= 0) {
    echo json_encode(['error' => 'Invalid assignment ID']);
    exit;
}

// Fetch assignment info
$sql = "SELECT ea.id AS assignment_id, ea.form_id, 
               u1.fullName AS evaluator, u2.fullName AS evaluatee,
               d.department_name, f.form_name
        FROM evaluation_assignments ea
        LEFT JOIN users u1 ON ea.evaluator_id = u1.id
        LEFT JOIN users u2 ON ea.evaluatee_id = u2.id
        LEFT JOIN departments d ON ea.department_id = d.id
        LEFT JOIN evaluation_forms f ON ea.form_id = f.id
        WHERE ea.id = $assignment_id AND ea.status = 'completed'";

$assignment = $conn->query($sql)->fetch_assoc();
if (!$assignment) {
    echo json_encode(['error' => 'Assignment not found or not completed']);
    exit;
}

// Fetch categories and form details
$form_id = intval($assignment['form_id']);
$categories = [];
$catRes = $conn->query("SELECT id, category_name, category_percentage FROM evaluation_categories WHERE form_id = $form_id ORDER BY id ASC");
while ($cat = $catRes->fetch_assoc()) {
    $categories[$cat['id']] = [
        'category_name' => $cat['category_name'],
        'percentage' => $cat['category_percentage'],
        'details' => []
    ];
}

$formDetailsRes = $conn->query("SELECT category_id, output_name, success_indicator, accomplishment FROM evaluation_form_details WHERE form_id = $form_id ORDER BY id ASC");
while ($fd = $formDetailsRes->fetch_assoc()) {
    $cat_id = $fd['category_id'];
    if (isset($categories[$cat_id])) {
        $categories[$cat_id]['details'][] = $fd;
    }
}

// Fetch all evaluation_results rows for this assignment
$evalRes = $conn->query("SELECT q, e, t, a, submitted_at FROM evaluation_results WHERE assignment_id = $assignment_id ORDER BY submitted_at ASC");
$results = [];
while ($row = $evalRes->fetch_assoc()) {
    $results[] = $row;
}

echo json_encode([
    'assignment' => $assignment,
    'categories' => array_values($categories),
    'results' => $results
]);
exit;
