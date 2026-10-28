<?php
/**
 * Action Controller: Customer Complaint / Dispute Processor
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contact.php');
}

require_csrf();

$pdo = get_db_connection();

$name             = trim($_POST['name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$bookingReference = trim($_POST['booking_reference'] ?? '');
$subject          = trim($_POST['subject'] ?? '');
$message          = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    set_flash('error', 'Please fill in all required complaint fields.');
    redirect('contact.php#complaint-form');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please enter a valid email address.');
    redirect('contact.php#complaint-form');
}

// 1. Resolve User
$userId = null;
if (is_logged_in()) {
    $userId = (int) $_SESSION['user_id'];
} else {
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$email]);
    $userId = $userStmt->fetchColumn();

    if (!$userId) {
        // Auto create guest user account for ticket tracking
        $tempPassword = password_hash(bin2hex(random_bytes(6)), PASSWORD_BCRYPT);
        $insUser = $pdo->prepare("INSERT INTO users (role, name, email, phone, password_hash, status) VALUES ('customer', ?, ?, '', ?, 'active')");
        $insUser->execute([$name, $email, $tempPassword]);
        $userId = (int) $pdo->lastInsertId();
    }
}

// 2. Resolve Booking ID if reference was provided
$bookingId = null;
if (!empty($bookingReference)) {
    $bStmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_reference = ? LIMIT 1");
    $bStmt->execute([$bookingReference]);
    $bookingId = $bStmt->fetchColumn() ?: null;
}

try {
    $insCmp = $pdo->prepare("
        INSERT INTO complaints (user_id, booking_id, subject, message, status)
        VALUES (?, ?, ?, ?, 'open')
    ");
    $insCmp->execute([$userId, $bookingId, $subject, $message]);
    $complaintId = (int) $pdo->lastInsertId();

    // Notify Admin
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $adminStmt->fetch();
    if ($admin) {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([
            $admin['id'],
            'New Customer Complaint Ticket',
            "Ticket #{$complaintId} logged by {$name}: {$subject}",
            'admin/complaints.php'
        ]);
    }

    set_flash('success', "Your support ticket #{$complaintId} has been submitted. The Shoot Karo administrative team will review it promptly.");
    redirect('contact.php');

} catch (Exception $e) {
    error_log("Complaint error: " . $e->getMessage());
    set_flash('error', 'Could not record ticket due to a system error. Please try again.');
    redirect('contact.php#complaint-form');
}
