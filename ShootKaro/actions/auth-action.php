<?php
/**
 * Auth Action Controller: Login & Customer Registration
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$action = $_GET['action'] ?? '';
$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

require_csrf();

// Rate limiting check
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 60) {
    $remaining = 60 - (time() - $_SESSION['last_attempt_time']);
    set_flash('error', "Too many failed attempts. Please wait {$remaining} seconds before trying again.");
    redirect('login.php');
}

// -------------------------------------------------------------
// 1. LOGIN HANDLER
// -------------------------------------------------------------
if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        set_flash('error', 'Please provide both your email address and password.');
        redirect('login.php');
    }

    $stmt = $pdo->prepare("SELECT id, role, name, email, phone, password_hash, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Reset rate limiter
        $_SESSION['login_attempts'] = 0;

        // Check account status
        if ($user['status'] === 'suspended') {
            set_flash('error', 'Your account has been suspended by administration. Please contact support.');
            redirect('login.php');
        }

        if ($user['status'] === 'pending' && $user['role'] === 'creator') {
            set_flash('info', 'Your creator application is currently pending admin review. You will receive access once approved.');
            redirect('login.php');
        }

        // Log the user in
        login_user($user);
        set_flash('success', "Welcome back, " . htmlspecialchars($user['name']) . "!");

        // Redirect based on role or intended destination
        $intended = $_SESSION['intended_url'] ?? '';
        unset($_SESSION['intended_url']);

        if (!empty($intended) && strpos($intended, 'logout.php') === false && strpos($intended, 'login.php') === false) {
            header("Location: {$intended}");
            exit;
        }

        if ($user['role'] === 'admin') {
            redirect('admin/index.php');
        } elseif ($user['role'] === 'creator') {
            redirect('dashboard/creator.php');
        } else {
            redirect('dashboard/customer.php');
        }
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        set_flash('error', 'Invalid email address or password.');
        redirect('login.php');
    }
}

// -------------------------------------------------------------
// 2. CUSTOMER REGISTRATION HANDLER
// -------------------------------------------------------------
elseif ($action === 'register') {
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        set_flash('error', 'Please fill in all required registration fields.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid email address.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters long.');
        redirect('register.php');
    }

    if ($password !== $confirmPassword) {
        set_flash('error', 'Passwords do not match. Please re-enter.');
        redirect('register.php');
    }

    // Check duplicate email
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        set_flash('error', 'An account with this email address already exists. Please log in.');
        redirect('login.php');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (role, name, email, phone, password_hash, status)
            VALUES ('customer', ?, ?, ?, ?, 'active')
        ");
        $insertStmt->execute([$name, $email, $phone, $passwordHash]);
        $newUserId = (int) $pdo->lastInsertId();

        // Auto login
        $user = [
            'id'    => $newUserId,
            'role'  => 'customer',
            'name'  => $name,
            'email' => $email,
            'phone' => $phone
        ];
        login_user($user);

        set_flash('success', 'Your customer account was created successfully!');
        redirect('dashboard/customer.php');
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        set_flash('error', 'Registration failed due to a server error. Please try again.');
        redirect('register.php');
    }
}

else {
    redirect('index.php');
}
