<?php
require_once '../../1database_conn/connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$form_id     = $data['form_id'] ?? null;
$form_name   = trim($data['form_name'] ?? '');
$academic_id = $data['academic_id'] ?? null;
$categories  = $data['categories'] ?? [];
$evaluation_weight = $data['evaluation_weight'] ?? 50;
$version = $data['version'] ?? 2;

// ✅ Preserve old academic_id if updating and none was sent
if ($form_id && empty($academic_id)) {
    $stmt = $conn->prepare("SELECT academic_id FROM evaluation_forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $stmt->bind_result($existing_academic_id);
    if ($stmt->fetch()) {
        $academic_id = $existing_academic_id;
        error_log("Preserved academic_id={$academic_id} for form_id={$form_id}");
    } else {
        error_log("Failed to fetch academic_id for form_id={$form_id}");
    }
    $stmt->close();
}

// ✅ Validation
if (empty($form_name) || empty($academic_id)) {
    error_log("Validation failed: form_name='{$form_name}', academic_id='{$academic_id}'");
    echo json_encode(['success' => false, 'error' => 'Form name and Academic Period are required']);
    exit;
}

try {
    $conn->begin_transaction();

    // ✅ Insert or Update form
    if ($form_id) {
        $stmt = $conn->prepare("UPDATE evaluation_forms SET form_name = ?, academic_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $form_name, $academic_id, $form_id);
        $stmt->execute();
        $stmt->close();
        error_log("Updated form_id={$form_id} with form_name='{$form_name}', academic_id={$academic_id}");

        // Clear old categories & criteria
        $stmt = $conn->prepare("DELETE FROM evaluation_categories WHERE form_id = ?");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $stmt->close();
        error_log("Cleared categories for form_id={$form_id}");
    } else {
        $stmt = $conn->prepare("INSERT INTO evaluation_forms (form_name, academic_id) VALUES (?, ?)");
        $stmt->bind_param("si", $form_name, $academic_id);
        $stmt->execute();
        $form_id = $stmt->insert_id;
        $stmt->close();
        error_log("Inserted new form_id={$form_id} with form_name='{$form_name}', academic_id={$academic_id}");
    }

    // ✅ Insert categories + criteria
    $stmtCat = $conn->prepare("INSERT INTO evaluation_categories (form_id, category_name, category_percentage) VALUES (?, ?, ?)");
    $stmtCrit = $conn->prepare("
        INSERT INTO evaluation_form_details 
        (form_id, category_id, output_name, success_indicator, accomplishment, q_rating, e_rating, t_rating, a_rating) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($categories as $category) {
    $categoryName = $category['categoryName'] ?? '';

    if ($version == 1) {
        // Version 1 does not use percentages — safely default to 0
        $categoryPercentage = 0;
    } else {
        // Version 2 expects percentage
        $categoryPercentage = isset($category['categoryPercentage'])
            ? (float)$category['categoryPercentage']
            : (isset($category['category_percentage']) ? (float)$category['category_percentage'] : 0);
    }

    // ✅ Ensure it's numeric and safe
    if (!is_numeric($categoryPercentage)) {
        $categoryPercentage = 0;
    }

    $stmtCat->bind_param("isd", $form_id, $categoryName, $categoryPercentage);
    $stmtCat->execute();
    $category_id = $stmtCat->insert_id;

    error_log("Inserted category '{$categoryName}' (id={$category_id}, version={$version}, percent={$categoryPercentage}) for form_id={$form_id}");


        // ✅ Insert criteria safely for both form versions
        foreach ($category['criteria'] as $criteria) {
            $output = $criteria['output'] ?? ($criteria['criterion'] ?? ''); // version 1 fallback
            $success_indicator = $criteria['success_indicator'] ?? '';
            $accomplishment = $criteria['accomplishment'] ?? '';
            $q_rating = isset($criteria['q_rating']) ? (float)$criteria['q_rating'] : 0;
            $e_rating = isset($criteria['e_rating']) ? (float)$criteria['e_rating'] : 0;
            $t_rating = isset($criteria['t_rating']) ? (float)$criteria['t_rating'] : 0;
            $a_rating = isset($criteria['a_rating']) ? (float)$criteria['a_rating'] : 0;

            $stmtCrit->bind_param(
                "iisssdddd",
                $form_id,
                $category_id,
                $output,
                $success_indicator,
                $accomplishment,
                $q_rating,
                $e_rating,
                $t_rating,
                $a_rating
            );
            $stmtCrit->execute();
        }
        error_log("Inserted " . count($category['criteria']) . " criteria for category_id={$category_id}");
    }

    $stmtCat->close();
    $stmtCrit->close();

    $conn->commit();
    error_log("Transaction committed successfully for form_id={$form_id}");
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Transaction failed for form_id={$form_id}: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
