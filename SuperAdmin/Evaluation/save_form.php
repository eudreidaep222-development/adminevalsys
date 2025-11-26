<?php
session_start();
require_once '../../1database_conn/connection.php';

if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'super admin') {
    echo json_encode([
        'success' => false, 
        'error' => "Unauthorized. Your actual role is: '" . ($_SESSION['role'] ?? 'NULL') . "'"
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$form_id     = $data['form_id'] ?? null;
$form_name   = trim($data['form_name'] ?? '');
$academic_id = $data['academic_id'] ?? null;
$categories  = $data['categories'] ?? [];
$versions    = $data['versions'] ?? 2;

// Preserve old academic_id if updating and none was sent
if ($form_id && empty($academic_id)) {
    $stmt = $conn->prepare("SELECT academic_id FROM evaluation_forms WHERE id = ?");
    $stmt->bind_param("i", $form_id);
    $stmt->execute();
    $stmt->bind_result($existing_academic_id);
    if ($stmt->fetch()) {
        $academic_id = $existing_academic_id;
    }
    $stmt->close();
}

// Validation
if (empty($form_name) || empty($academic_id)) {
    echo json_encode(['success' => false, 'error' => 'Form name and Academic Period are required']);
    exit;
}

try {
    $conn->begin_transaction();

    // Insert or update form
    if ($form_id) {
        $stmt = $conn->prepare("UPDATE evaluation_forms SET form_name = ?, academic_id = ?, versions = ? WHERE id = ?");
        $stmt->bind_param("siii", $form_name, $academic_id, $versions, $form_id);        
        $stmt->execute();
        $stmt->close();

        // Clear old categories & criteria
        $stmt = $conn->prepare("DELETE FROM evaluation_categories WHERE form_id = ?");
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $stmt->close();

        // Clear old criteria in proper details table
        if ($versions == 1) {
            $stmt = $conn->prepare("DELETE FROM evaluation_form_v1_details WHERE form_id = ?");
        } else {
            $stmt = $conn->prepare("DELETE FROM evaluation_form_details WHERE form_id = ?");
        }
        $stmt->bind_param("i", $form_id);
        $stmt->execute();
        $stmt->close();

    } else {
        $stmt = $conn->prepare("INSERT INTO evaluation_forms (form_name, academic_id, versions) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $form_name, $academic_id, $versions);
        $stmt->execute();
        $form_id = $stmt->insert_id;
        $stmt->close();
    }

    // Insert categories
    $stmtCat = $conn->prepare("INSERT INTO evaluation_categories (form_id, category_name, category_percentage) VALUES (?, ?, ?)");
    
    if ($versions == 1) {
        // V1 → prepare criteria insert into _v1 table
        $stmtCrit = $conn->prepare("INSERT INTO evaluation_form_v1_details (form_id, category_id, criterion) VALUES (?, ?, ?)");
    } else {
        // V2 → prepare criteria insert into normal table
        $stmtCrit = $conn->prepare("
            INSERT INTO evaluation_form_details 
            (form_id, category_id, output_name, success_indicator, accomplishment, q_rating, e_rating, t_rating, a_rating) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    }

    foreach ($categories as $category) {
        $categoryName = $category['categoryName'] ?? '';
        $categoryPercentage = $versions == 2 ? (float)($category['categoryPercentage'] ?? 0) : 0;

        $stmtCat->bind_param("isd", $form_id, $categoryName, $categoryPercentage);
        $stmtCat->execute();
        $category_id = $stmtCat->insert_id;

        foreach ($category['criteria'] as $criteria) {
            if ($versions == 1) {
                $criterion_text = $criteria['criterion'] ?? '';
                $stmtCrit->bind_param("iis", $form_id, $category_id, $criterion_text);
            } else {
                $output = $criteria['output'] ?? '';
                $success_indicator = $criteria['success_indicator'] ?? '';
                $accomplishment = $criteria['accomplishment'] ?? '';
                $q_rating = (float)($criteria['q_rating'] ?? 0);
                $e_rating = (float)($criteria['e_rating'] ?? 0);
                $t_rating = (float)($criteria['t_rating'] ?? 0);
                $a_rating = (float)($criteria['a_rating'] ?? 0);

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
            }
            $stmtCrit->execute();
        }
    }

    $stmtCat->close();
    $stmtCrit->close();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
