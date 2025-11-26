<?php
session_start();
include '../../1database_conn/connection.php';

header('Content-Type: application/json');

// Authorization Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Super Admin'){
    echo json_encode(['success' => false, 'error' => 'Authorization failed. Super Admin access required.']);
    exit();
}

// Handle Assignment Action (Step 0)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_id']) && isset($_POST['form_id'])) {
    handle_form_assignment($conn);
} 
// Placeholder for future Final Approval Logic (Step 4)
// else if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'final_approve') {
//     handle_final_approval($conn); 
// }
else {
    echo json_encode(['success' => false, 'error' => 'Invalid or incomplete request.']);
    exit();
}

/**
 * Handles the initial assignment of the evaluation form (Step 0).
 */
function handle_form_assignment($conn) {
    $evaluatee_id = (int)$_POST['user_id'];
    $form_id = (int)$_POST['form_id'];
    $form_version = (int)$_POST['form_version'];
    $assigned_by_id = $_SESSION['user_id']; 

    if ($evaluatee_id <= 0 || $form_id <= 0 || $form_version != 2) {
        echo json_encode(['success' => false, 'error' => 'Invalid data provided for assignment.']);
        exit();
    }

    try {
        // Check for active duplicates (Statuses before Completion)
        $check_sql = "SELECT id FROM assigned_evaluations 
                      WHERE evaluatee_user_id = ? AND evaluation_form_id = ? AND status IN ('Pending', 'Needs Approval 1', 'Self-Evaluation')";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $evaluatee_id, $form_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            echo json_encode(['success' => false, 'error' => 'An active evaluation for this form is already underway for the selected administrator.']);
            exit();
        }
        $check_stmt->close();
        
        // Insert the assignment record (Initial Status: 'Pending' for Step 1)
        $insert_sql = "INSERT INTO assigned_evaluations 
                       (evaluatee_user_id, evaluation_form_id, assigned_by_user_id, status, form_version) 
                       VALUES (?, ?, ?, 'Pending', ?)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiii", $evaluatee_id, $form_id, $assigned_by_id, $form_version);
        
        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            
            // Fetch names for success message
            $user_q = $conn->query("SELECT CONCAT_WS(' ', first_name, middle_name, last_name) AS fullName FROM users WHERE id = $evaluatee_id");
            $form_q = $conn->query("SELECT form_name FROM evaluation_forms WHERE id = $form_id");
            $user_name = $user_q->fetch_assoc()['fullName'] ?? 'Administrator';
            $form_name = $form_q->fetch_assoc()['form_name'] ?? 'Form';
            
            echo json_encode(['success' => true, 'message' => "Evaluation **$form_name** successfully assigned to **$user_name**. Awaiting Administrator's initial input (Step 1)."]);
        } else {
            $insert_stmt->close();
            echo json_encode(['success' => false, 'error' => 'Database error during assignment: ' . $conn->error]);
        }

    } catch (\Throwable $th) {
        echo json_encode(['success' => false, 'error' => 'An unexpected server error occurred: ' . $th->getMessage()]);
    }
}

// Function placeholder for future implementation of Step 4
// function handle_final_approval($conn) { ... }

$conn->close();
?>