<?php
/**
 * Application Configuration and Core Initialization
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set Indian Standard Time (IST) or system default
date_default_timezone_set('Asia/Kolkata');

// App Paths
define('APP_ROOT', dirname(__DIR__));
define('CONFIG_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'config');
define('INCLUDES_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'includes');
define('UPLOADS_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');

// Autodetect Base URL for XAMPP
function get_base_url(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443 ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine the base folder from script path
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // If the script is within /admin, /dashboard, or /actions, strip that suffix
    $cleanPath = preg_replace('#/(admin|dashboard|actions)(/.*)?$#', '', $scriptDir);
    $cleanPath = rtrim($cleanPath, '/');
    
    return $protocol . $host . ($cleanPath ? $cleanPath . '/' : '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', get_base_url());
}

/**
 * Generate a complete site URL
 */
function url(string $path = ''): string {
    return BASE_URL . ltrim($path, '/');
}

// Require Database Connection
require_once CONFIG_PATH . '/database.php';

/**
 * Fetch a site setting by key with a fallback value
 */
function get_site_setting(string $key, $fallback = ''): string {
    static $settingsCache = null;

    if ($settingsCache === null) {
        $settingsCache = [];
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
            while ($row = $stmt->fetch()) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            // Table may not exist yet during setup
        }
    }

    return $settingsCache[$key] ?? $fallback;
}

// Global App Settings
define('APP_NAME', get_site_setting('site_name', 'SHOOT KARO'));
define('SUPPORT_PHONE', get_site_setting('support_phone', '+91 98765 43210'));
define('SUPPORT_EMAIL', get_site_setting('support_email', 'support@shootkaro.local'));
define('PLATFORM_COMMISSION_PERCENT', (float) get_site_setting('platform_commission_percent', '10.00'));
define('DEFAULT_ADVANCE_PERCENT', (float) get_site_setting('default_advance_percent', '20.00'));
