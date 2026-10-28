<?php
/**
 * CSRF Protection Utility
 */

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token(): string {
    return generate_csrf_token();
}

function csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validate_csrf_token($token)) {
        http_response_code(403);
        die("
            <div style='font-family: system-ui, -apple-system, sans-serif; max-width: 500px; margin: 60px auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px;'>
                <h3 style='margin-top: 0;'>Security Validation Failed</h3>
                <p>Invalid or expired CSRF security token. Please return to the previous page, refresh, and try again.</p>
                <a href='javascript:history.back()' style='display: inline-block; padding: 8px 16px; background: #000; color: #fff; text-decoration: none; border-radius: 4px;'>Go Back</a>
            </div>
        ");
    }
}
