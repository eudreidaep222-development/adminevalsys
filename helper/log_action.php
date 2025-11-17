<?php
function log_action($conn, $user_id, $action, $target_id = null, $extra_data = null, $description = null) {
    // Ensure $user_id is not null — if no session user, use 0 (system)
    if (empty($user_id)) {
    $user_id = null; // instead of 0
    }

    // Convert array extra_data to JSON
    if (is_array($extra_data)) {
        $extra_data = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
    }

    // Capture IP and User Agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "
        INSERT INTO audit_logs 
        (user_id, target_id, extra_data, ip_address, user_agent, action, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    if ($stmt = $conn->prepare($sql)) {
        // All values bound directly — nulls are allowed
        $stmt->bind_param(
            "iisssss",
            $user_id,
            $target_id,
            $extra_data,
            $ip_address,
            $user_agent,
            $action,
            $description
        );
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare audit log insert: " . $conn->error);
    }
}

function log_login_attempt($conn, $email, $status, $userId = null, $reason = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO login_attempts (email, user_id, status, ip_address, user_agent, reason)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sissss", $email, $userId, $status, $ip, $agent, $reason);
    $stmt->execute();
    $stmt->close();
}


?>
