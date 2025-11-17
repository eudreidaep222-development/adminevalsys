<?php
include '../../1database_conn/connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$assignment_id = intval($_POST['assignment_id'] ?? 0);
$form_id       = intval($_POST['form_id'] ?? 0);
$academic_id   = intval($_POST['academic_id'] ?? 0);
$criteria      = $_POST['criteria'] ?? [];

if ($assignment_id <= 0 || $form_id <= 0 || $academic_id <= 0 || empty($criteria)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Check assignment ownership
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
$ratee_id     = $row['evaluatee_id']; // correct column

if ($evaluator_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Not allowed']);
    exit;
}
$stmt->close();

// 🔹 Batch insert all criteria in a single query, including assignment_id
$values = [];
$params = [];
$types  = '';
foreach ($criteria as $c) {
    $q = intval($c['q'] ?? 0);
    $e = intval($c['e'] ?? 0);
    $t = intval($c['t'] ?? 0);
    $a = intval($c['a'] ?? 0);

    $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?)"; // added assignment_id
    $params = array_merge($params, [$assignment_id, $form_id, $academic_id, $ratee_id, $evaluator_id, $q, $e, $t, $a]);
    $types .= "iiiiiiiii"; // 9 integers
}

$sql = "INSERT INTO evaluation_results (assignment_id, form_id, academic_id, admin_id, evaluator_id, q, e, t, a) VALUES " . implode(", ", $values);

$stmt = $conn->prepare($sql);

// Use call_user_func_array to bind dynamic number of params
$tmp = [];
$tmp[] = &$types;
for ($i = 0; $i < count($params); $i++) {
    $tmp[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $tmp);
$stmt->execute();
$stmt->close();

// Update assignment status
$upd = $conn->prepare("UPDATE evaluation_assignments SET status='completed' WHERE id=?");
$upd->bind_param("i", $assignment_id);  
$upd->execute();
$upd->close();

echo json_encode(['success' => true, 'message' => 'Evaluation submitted successfully']);
?>
