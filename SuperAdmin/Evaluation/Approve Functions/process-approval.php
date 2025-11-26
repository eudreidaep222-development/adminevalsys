<?php
session_start();

// Path correction: SuperAdmin/Evaluations/Approve Functions/process-approval.php -> Root
// Corrected to use three '../' to reach the project root from the current directory
require_once '../../../1database_conn/connection.php';
require_once '../../../helper/log_action.php';

// 1. Security Check: Only Super Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    // Corrected logout path for consistency, assuming /logout.php is at project root
    header("Location: /logout.php"); 
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Get & Validate Inputs
    $evaluation_id = isset($_POST['evaluation_id']) ? intval($_POST['evaluation_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($evaluation_id === 0 || empty($action)) {
        $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Invalid request parameters.'];
        // Corrected redirect path: back to SuperAdmin/Evaluation/approve-evaluation.php
        header("Location: ../approve-evaluation.php"); 
        exit();
    }

    // 3. Fetch Evaluation Context (For detailed Audit Logging)
    $infoSql = "
        SELECT ea.id, 
               CONCAT(u1.first_name, ' ', u1.last_name) AS evaluator,
               CONCAT(u2.first_name, ' ', u2.last_name) AS evaluatee
        FROM evaluation_assignments ea
        JOIN users u1 ON ea.evaluator_id = u1.id
        JOIN users u2 ON ea.evaluatee_id = u2.id
        WHERE ea.id = ?
    ";
    $stmt = $conn->prepare($infoSql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$info) {
        $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Evaluation not found or already processed.'];
        // Corrected redirect path: back to SuperAdmin/Evaluation/approve-evaluation.php
        header("Location: ../approve-evaluation.php"); 
        exit();
    }

    // 4. Handle Actions
    if ($action === 'approve') {
        // --- APPROVE LOGIC ---
        $newStatus = 'approved'; 
        
        // **FIX:** Removed 'updated_at = NOW()' to avoid 'Unknown column' error
        $updateSql = "UPDATE evaluation_assignments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $newStatus, $evaluation_id);
        
        if ($stmt->execute()) {
            // Log Action
            $desc = "Approved evaluation #$evaluation_id (Evaluator: {$info['evaluator']} -> Evaluatee: {$info['evaluatee']})";
            log_action($conn, $_SESSION['user_id'], 'Approve Evaluation', $desc, $evaluation_id);

            $_SESSION['toastr'] = ['type' => 'success', 'message' => 'Evaluation approved successfully.'];
            
            // REDIRECT TO APPROVED LIST (relative to current folder's parent: ../approved-evaluation.php)
            header("Location: ../approved-evaluation.php");
            exit();
        } else {
            $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Database Update Failed: ' . $stmt->error];
        }
        $stmt->close();

    } elseif ($action === 'reject') {
        // --- REJECT LOGIC ---
        $newStatus = 'rejected';

        // **NEW:** Capture the rejection note from the modal form
        $rejection_note = isset($_POST['rejection_note']) ? trim($_POST['rejection_note']) : 'No reason provided.';

        // **FIX:** Removed 'updated_at = NOW()' to avoid 'Unknown column' error
        $updateSql = "UPDATE evaluation_assignments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("si", $newStatus, $evaluation_id);
        
        if ($stmt->execute()) {
            // Log Action - **NEW:** Include the note in the description
            $desc = "Rejected evaluation #$evaluation_id (Evaluator: {$info['evaluator']} -> Evaluatee: {$info['evaluatee']}). Reason: " . $rejection_note;
            log_action($conn, $_SESSION['user_id'], 'Reject Evaluation', $desc, $evaluation_id);

            // **FIXED:** Updated toastr message to confirm reason was noted
            $_SESSION['toastr'] = ['type' => 'warning', 'message' => 'Evaluation rejected. Reason noted: ' . htmlspecialchars($rejection_note)];
            
            // REDIRECT BACK TO PENDING LIST
            header("Location: ../approve-evaluation.php");
            exit();
        } else {
            $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Database Update Failed: ' . $stmt->error];
        }
        $stmt->close();

    } else {
        $_SESSION['toastr'] = ['type' => 'error', 'message' => 'Unknown action type.'];
    }

    // Fallback Redirect
    header("Location: ../approve-evaluation.php");
    exit();

} else {
    // Prevent direct GET access
    header("Location: ../approve-evaluation.php");
    exit();
}
?>