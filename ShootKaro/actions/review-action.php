<?php
/**
 * Action Controller: Customer Review Submission
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('customer');
require_csrf();

$pdo = get_db_connection();
$user = current_user();

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$creatorId = (int) ($_POST['creator_id'] ?? 0);
$rating    = (int) ($_POST['rating'] ?? 5);
$comment   = trim($_POST['comment'] ?? '');

if ($bookingId <= 0 || $creatorId <= 0 || empty($comment)) {
    set_flash('error', 'Please provide both a star rating and written feedback.');
    redirect('dashboard/customer.php');
}

$rating = max(1, min(5, $rating));

// Verify that booking belongs to this customer and is marked 'completed'
$stmt = $pdo->prepare("SELECT id, status, customer_id, booking_reference FROM bookings WHERE id = ? AND customer_id = ?");
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('error', 'Booking record not found or access denied.');
    redirect('dashboard/customer.php');
}

if ($booking['status'] !== 'completed') {
    set_flash('error', 'Reviews can only be submitted after a shoot is completed.');
    redirect('dashboard/customer.php');
}

// Check for existing review
$checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ? LIMIT 1");
$checkStmt->execute([$bookingId]);
if ($checkStmt->fetch()) {
    set_flash('error', 'You have already submitted a review for this shoot.');
    redirect('dashboard/customer.php');
}

try {
    $insStmt = $pdo->prepare("
        INSERT INTO reviews (booking_id, creator_id, customer_id, rating, comment, is_approved)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $insStmt->execute([$bookingId, $creatorId, $user['id'], $rating, $comment]);

    // Send notification to creator
    $cUserStmt = $pdo->prepare("SELECT user_id FROM creator_profiles WHERE id = ?");
    $cUserStmt->execute([$creatorId]);
    $creatorUserId = $cUserStmt->fetchColumn();

    if ($creatorUserId) {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([
            $creatorUserId,
            'New Client Review',
            "{$user['name']} left a {$rating}-star review for booking {$booking['booking_reference']}.",
            'dashboard/creator.php'
        ]);
    }

    set_flash('success', 'Thank you! Your review has been published.');
    redirect('dashboard/customer.php');

} catch (Exception $e) {
    error_log("Review submission error: " . $e->getMessage());
    set_flash('error', 'Could not save review. Please try again.');
    redirect('dashboard/customer.php');
}
