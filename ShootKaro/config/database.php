<?php
/**
 * Database Configuration for Shoot Karo
 * Standard XAMPP Defaults
 */

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'shoot_karo_db');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_PORT')) define('DB_PORT', 3306);

/**
 * Returns a shared PDO instance
 * 
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log real error on server, show clean message to user
            error_log("Database connection failed: " . $e->getMessage());
            die("
                <div style='font-family: system-ui, -apple-system, sans-serif; max-width: 600px; margin: 60px auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;'>
                    <h2 style='margin-top: 0; color: #111;'>Database Connection Error</h2>
                    <p style='color: #4b5563; line-height: 1.5;'>Could not connect to the database <code>" . htmlspecialchars(DB_NAME) . "</code> on <code>" . htmlspecialchars(DB_HOST) . "</code>.</p>
                    <p style='color: #4b5563; line-height: 1.5;'>Please make sure MySQL is running in your XAMPP Control Panel and the database schema has been imported from <code>database/shoot_karo.sql</code>.</p>
                </div>
            ");
        }
    }

    return $pdo;
}
