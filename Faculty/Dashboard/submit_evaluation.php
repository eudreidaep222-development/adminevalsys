<?php
include '../../1database_conn/connection.php';
session_start();

header('Content-Type: application/json');

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// 2. Receive Inputs
$assignment_id = intval($_POST['assignment_id'] ?? 0);
$form_id       = intval($_POST['form_id'] ?? 0);
$academic_id   = intval($_POST['academic_id'] ?? 0);
$criteria      = $_POST['criteria'] ?? [];
$version       = $_POST['version'] ?? 'v1'; 

if ($assignment_id <= 0 || empty($criteria)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// 3. Verify Assignment & Get Evaluatee
$stmt = $conn->prepare("SELECT evaluator_id, evaluatee_id FROM evaluation_assignments WHERE id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid assignment']);
    exit;
}

$row = $res->fetch_assoc();
$evaluator_id = $row['evaluator_id'];
$evaluatee_id = $row['evaluatee_id']; 

if ($evaluator_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not allowed']);
    exit;
}
$stmt->close();

// 4. Process Data based on Version
$conn->begin_transaction();

try {
    if ($version === 'v2') {
        // =================================================
        // VERSION 2 (Quantitative) -> 'evaluation_results'
        // =================================================
        // Schema: id, assignment_id, form_id, academic_id, admin_id, evaluator_id, q, e, t, a, submitted_at
        
        $stmtV2 = $conn->prepare("
            INSERT INTO evaluation_results 
            (assignment_id, form_id, academic_id, admin_id, evaluator_id, q, e, t, a, submitted_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($criteria as $c) {
            $q = intval($c['q']);
            $e = intval($c['e']);
            $t = intval($c['t']);
            $a = intval($c['a']); 

            // Note: 'admin_id' in this table refers to the evaluatee
            $stmtV2->bind_param("iiiiiiiii", 
                $assignment_id, $form_id, $academic_id, $evaluatee_id, $evaluator_id,
                $q, $e, $t, $a
            );
            $stmtV2->execute();
        }
        $stmtV2->close();

    } else {
        // =================================================
        // VERSION 1 (Qualitative) -> 'evaluation_results_v1'
        // =================================================
        // Schema: id, assignment_id, form_id, category_id, criterion_id, evaluator_id, evaluatee_id, rating, form_version, submitted_at
        
        // Helper to find category_id since it's required by your schema but might not be in the POST data
        $catLookup = $conn->prepare("SELECT category_id FROM evaluation_questions WHERE id = ? LIMIT 1");
        
        $stmtV1 = $conn->prepare("
            INSERT INTO evaluation_results_v1 
            (assignment_id, form_id, category_id, criterion_id, evaluator_id, evaluatee_id, rating, form_version, submitted_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        foreach ($criteria as $c) {
            $criterion_id = intval($c['question_id'] ?? 0);
            $rating       = intval($c['rating'] ?? 0);
            
            // Lookup Category ID
            $category_id = 0;
            if ($criterion_id > 0) {
                $catLookup->bind_param("i", $criterion_id);
                $catLookup->execute();
                $catRes = $catLookup->get_result();
                if($catRow = $catRes->fetch_assoc()){
                    $category_id = $catRow['category_id'];
                }
            }

            if($criterion_id > 0) {
                $stmtV1->bind_param("iiiiiii", 
                    $assignment_id, 
                    $form_id, 
                    $category_id, 
                    $criterion_id, 
                    $evaluator_id, 
                    $evaluatee_id, 
                    $rating
                );
                $stmtV1->execute();
            }
        }
        $catLookup->close();
        $stmtV1->close();
    }

    // 5. Mark Assignment Completed
    $upd = $conn->prepare("UPDATE evaluation_assignments SET status='completed', date_completed=NOW() WHERE id=?");
    $upd->bind_param("i", $assignment_id);
    $upd->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Evaluation submitted successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>