<?php
/**
 * Authentication and Role-Based Access Control
 */

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => (int) $_SESSION['user_id'],
        'role'  => $_SESSION['user_role'] ?? 'customer',
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
    ];
}

function is_role(string $role): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === $role;
}

function is_admin(): bool {
    return is_role('admin');
}

function is_creator(): bool {
    return is_role('creator');
}

function is_customer(): bool {
    return is_role('customer');
}

function require_login(string $redirectPath = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
        set_flash('error', 'Please log in to continue.');
        header('Location: ' . url($redirectPath));
        exit;
    }
}

function require_role($roles, string $fallbackPath = 'index.php'): void {
    require_login();

    $allowed = is_array($roles) ? $roles : [$roles];
    $currentRole = $_SESSION['user_role'] ?? '';

    if (!in_array($currentRole, $allowed, true)) {
        set_flash('error', 'Access denied. You do not have permission to view that page.');
        header('Location: ' . url($fallbackPath));
        exit;
    }
}

function login_user(array $user): void {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';

    // If user is a creator, cache creator profile ID
    if ($user['role'] === 'creator') {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT id, is_approved, is_available FROM creator_profiles WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $profile = $stmt->fetch();
            if ($profile) {
                $_SESSION['creator_profile_id'] = (int) $profile['id'];
                $_SESSION['creator_approved']   = (bool) $profile['is_approved'];
            }
        } catch (Exception $e) {
            // Ignore
        }
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
