<?php
// We assume session_start() has already been called in the main file

/**
 * Generates a new, secure CSRF token and stores it in the session.
 */
function generate_csrf_token() {
    // Generate a 32-byte secure random token
    // bin2hex converts the raw bytes into a 64-character string
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Checks if a CSRF token is already set. If not, it generates one.
 * This ensures that a token is always available for forms on first load.
 */
if (empty($_SESSION['csrf_token'])) {
    generate_csrf_token();
}
?>