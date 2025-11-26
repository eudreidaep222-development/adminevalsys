<?php
session_start();
header('Content-Type: application/json');
require_once '../../1database_conn/connection.php';
require_once '../../helper/log_action.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id']; // evaluator
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['evaluatee_id'], $data['form_id'], $data['categories'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data submitted.']);
    exit();
}

$evaluatee_id = intval($data['evaluatee_id']);
$form_id = intval($data['form_id']);
$categories = $data['categories'];

try {
    // 1️⃣ Insert into user_evaluations
    $stmt = $conn->prepare("
        INSERT INTO user_evaluations 
        (evaluator_id, evaluatee_id, form_id, status) 
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iii", $user_id, $evaluatee_id, $form_id);
    $stmt->execute();
    $user_evaluation_id = $stmt->insert_id;
    $stmt->close();

    // 2️⃣ Insert each criterion into user_evaluation_details
    $stmtDetail = $conn->prepare("
        INSERT INTO user_evaluation_details
        (user_evaluation_id, category_id, output_name, success_indicator, accomplishment, q_rating, e_rating, t_rating, a_rating)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($categories as $cat) {
        $category_id = intval($cat['category_id']); // must match the template category ID
        foreach ($cat['criteria'] as $crit) {
            $stmtDetail->bind_param(
                "iisssdddd",
                $user_evaluation_id,
                $category_id,
                $crit['output_name'],
                $crit['success_indicator'],
                $crit['accomplishment'],
                $crit['q_rating'],
                $crit['e_rating'],
                $crit['t_rating'],
                $crit['a_rating']
            );
            $stmtDetail->execute();
        }
    }
    $stmtDetail->close();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
