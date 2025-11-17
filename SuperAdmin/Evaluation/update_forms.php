<?php
require_once '../../1database_conn/connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$form_id = $data['form_id'] ?? null;
$form_name = $data['form_name'] ?? '';

if (empty($form_id) || empty($form_name)) {
    echo json_encode(['success' => false, 'error' => 'Form ID and name are required']);
    exit;
}

$categories = $data['categories'] ?? [];

try {
    $conn->begin_transaction();

    // Update form name
    $stmt = $conn->prepare("UPDATE evaluation_forms SET form_name = ? WHERE id = ?");
    $stmt->bind_param("si", $form_name, $form_id);
    $stmt->execute();

    // Delete old categories and criteria before inserting new ones
    $stmt = $conn->prepare("DELETE FROM evaluation_categories WHERE form_id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();

    // Insert new categories and criteria
    foreach ($categories as $category) {
        $stmt = $conn->prepare("INSERT INTO evaluation_categories (form_id, category_name, category_percentage) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $form_id, $category['categoryName'], $category['categoryPercentage']);
        $stmt->execute();
        $category_id = $stmt->insert_id;

        foreach ($category['criteria'] as $criteria) {
            $stmt = $conn->prepare("INSERT INTO evaluation_form_details (form_id, category_id, output_name, success_indicator, accomplishment, q_rating, e_rating, t_rating, a_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "iisssdddd",
                $form_id,
                $category_id,
                $criteria['output'],
                $criteria['success_indicator'],
                $criteria['accomplishment'],
                $criteria['q_rating'],
                $criteria['e_rating'],
                $criteria['t_rating'],
                $criteria['a_rating']
            );
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
