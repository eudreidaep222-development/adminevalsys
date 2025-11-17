<?php
require_once '../../1database_conn/connection.php';

$form_id = $_GET['id'] ?? null;

if (!$form_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid form ID']);
    exit;
}

try {
    // 🔹 Fetch base form info
    $stmt = $conn->prepare("SELECT id, form_name FROM evaluation_forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $form_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$form_result) {
        echo json_encode(['success' => false, 'error' => 'Form not found']);
        exit;
    }

    $form_data = [
        "form_id" => $form_result['id'],
        "form_name" => $form_result['form_name'],
        "version" => 2, // default
        "categories" => []
    ];

    // 🔹 Check if this form has Version 1 criteria
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM criteria WHERE form_id = ? AND version = 1");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $version1Check = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($version1Check && $version1Check['count'] > 0) {
        // ✅ Load Version 1 structure
        $form_data['version'] = 1;
        $categories = [];

        $stmt = $conn->prepare("
            SELECT category_name, criteria_text 
            FROM criteria 
            WHERE form_id = ? AND version = 1 
            ORDER BY category_name
        ");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $catName = $row['category_name'];
            if (!isset($categories[$catName])) {
                $categories[$catName] = [
                    "category_name" => $catName,
                    "category_percentage" => 0, // V1 has no percentage
                    "criteria" => []
                ];
            }
            $categories[$catName]['criteria'][] = [
                "output_name" => $row['criteria_text']
            ];
        }

        $form_data['categories'] = array_values($categories);

        echo json_encode($form_data);
        exit;
    }

    // 🧩 Load Version 2 structure
    $stmt = $conn->prepare("SELECT id, category_name, category_percentage FROM evaluation_categories WHERE form_id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $categories_result = $stmt->get_result();
    $stmt->close();

    while ($category = $categories_result->fetch_assoc()) {
        $category_id = $category['id'];

        $stmt2 = $conn->prepare("
            SELECT output_name, success_indicator, accomplishment, q_rating, e_rating, t_rating, a_rating
            FROM evaluation_form_details 
            WHERE category_id = ?
        ");
        $stmt2->bind_param("i", $category_id);
        $stmt2->execute();
        $criteria_result = $stmt2->get_result();
        $stmt2->close();

        $criteria = [];
        while ($row = $criteria_result->fetch_assoc()) {
            $criteria[] = $row;
        }

        $form_data['categories'][] = [
            "category_id" => $category_id,
            "category_name" => $category['category_name'],
            "category_percentage" => $category['category_percentage'],
            "criteria" => $criteria
        ];
    }

    // Ensure version is properly detected
    $form_data['version'] = count($form_data['categories']) > 0 ? 2 : 1;

    echo json_encode($form_data);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
