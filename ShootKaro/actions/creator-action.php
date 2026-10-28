<?php
/**
 * Action Controller: Creator Management Actions
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('creator');
require_csrf();

$pdo = get_db_connection();
$user = current_user();
$action = $_GET['action'] ?? '';

// Fetch Creator Profile ID
$stmt = $pdo->prepare("SELECT id FROM creator_profiles WHERE user_id = ?");
$stmt->execute([$user['id']]);
$creatorId = (int) $stmt->fetchColumn();

if (!$creatorId) {
    set_flash('error', 'Creator profile not found.');
    redirect('dashboard/creator.php');
}

// -------------------------------------------------------------
// 1. UPDATE BOOKING STATUS (Accept / Reject / In Progress)
// -------------------------------------------------------------
if ($action === 'update_booking_status') {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');

    $allowedStatuses = ['accepted', 'rejected', 'in_progress', 'completed'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        set_flash('error', 'Invalid booking status update.');
        redirect('dashboard/creator.php');
    }

    // Verify creator ownership
    $verifyStmt = $pdo->prepare("SELECT id, customer_id, booking_reference FROM bookings WHERE id = ? AND creator_id = ?");
    $verifyStmt->execute([$bookingId, $creatorId]);
    $booking = $verifyStmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking order not found.');
        redirect('dashboard/creator.php');
    }

    $updateStmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$newStatus, $bookingId]);

    // Send customer notification
    $statusText = ucwords(str_replace('_', ' ', $newStatus));
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $notifStmt->execute([
        $booking['customer_id'],
        "Shoot Status: {$statusText}",
        "Your booking {$booking['booking_reference']} was marked as {$statusText} by the creator.",
        'dashboard/customer.php'
    ]);

    set_flash('success', "Booking {$booking['booking_reference']} updated to {$statusText}.");
    redirect('dashboard/creator.php');
}

// -------------------------------------------------------------
// 2. UPLOAD DELIVERABLES & MARK COMPLETE
// -------------------------------------------------------------
elseif ($action === 'upload_deliverable') {
    $bookingId        = (int) ($_POST['booking_id'] ?? 0);
    $title            = trim($_POST['title'] ?? '');
    $downloadUrl      = trim($_POST['download_url'] ?? '');
    $notes            = trim($_POST['notes'] ?? '');
    $markAsCompleted  = !empty($_POST['mark_as_completed']);

    if (empty($title)) {
        set_flash('error', 'Please enter a title for the deliverable.');
        redirect('dashboard/creator.php');
    }

    // Verify booking
    $bStmt = $pdo->prepare("SELECT id, customer_id, booking_reference FROM bookings WHERE id = ? AND creator_id = ?");
    $bStmt->execute([$bookingId, $creatorId]);
    $booking = $bStmt->fetch();

    if (!$booking) {
        set_flash('error', 'Booking not found.');
        redirect('dashboard/creator.php');
    }

    $filePath = null;
    if (!empty($_FILES['deliverable_file']['name'])) {
        $upload = upload_file($_FILES['deliverable_file'], 'deliverables', ['zip', 'jpg', 'jpeg', 'png', 'mp4', 'pdf']);
        if (!$upload['success']) {
            set_flash('error', 'Deliverable upload failed: ' . $upload['error']);
            redirect('dashboard/creator.php');
        }
        $filePath = $upload['filename'];
    }

    if (empty($downloadUrl) && empty($filePath)) {
        set_flash('error', 'Please either enter a cloud download URL or upload a file.');
        redirect('dashboard/creator.php');
    }

    try {
        $pdo->beginTransaction();

        $delivStmt = $pdo->prepare("
            INSERT INTO booking_deliverables (booking_id, title, file_path, download_url, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $delivStmt->execute([$bookingId, $title, $filePath, $downloadUrl, $notes]);

        if ($markAsCompleted) {
            $compStmt = $pdo->prepare("UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $compStmt->execute([$bookingId]);
        }

        // Notify client
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([
            $booking['customer_id'],
            'Deliverables Ready for Download',
            "Your shoot files for {$booking['booking_reference']} are ready in your Shoot Karo dashboard.",
            'dashboard/customer.php'
        ]);

        $pdo->commit();

        set_flash('success', "Deliverable published successfully and client notified!");
        redirect('dashboard/creator.php');

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Deliverable save error: " . $e->getMessage());
        set_flash('error', 'Could not record deliverable. Please try again.');
        redirect('dashboard/creator.php');
    }
}

// -------------------------------------------------------------
// 3. TOGGLE AVAILABILITY
// -------------------------------------------------------------
elseif ($action === 'toggle_availability') {
    $toggleStmt = $pdo->prepare("UPDATE creator_profiles SET is_available = IF(is_available = 1, 0, 1) WHERE id = ?");
    $toggleStmt->execute([$creatorId]);
    set_flash('success', 'Your booking availability status has been updated.');
    redirect('dashboard/creator.php');
}

// -------------------------------------------------------------
// 4. ADD PORTFOLIO ITEM
// -------------------------------------------------------------
elseif ($action === 'add_portfolio') {
    $title     = trim($_POST['title'] ?? '');
    $mediaType = trim($_POST['media_type'] ?? 'image');
    $videoUrl  = trim($_POST['video_url'] ?? '');
    $caption   = trim($_POST['caption'] ?? '');

    if (empty($title)) {
        set_flash('error', 'Please provide a title for the portfolio item.');
        redirect('dashboard/creator.php');
    }

    $imageFile = null;
    if ($mediaType === 'image') {
        if (!empty($_FILES['portfolio_image']['name'])) {
            $up = upload_file($_FILES['portfolio_image'], 'portfolio', ['jpg', 'jpeg', 'png', 'webp']);
            if (!$up['success']) {
                set_flash('error', 'Portfolio upload error: ' . $up['error']);
                redirect('dashboard/creator.php');
            }
            $imageFile = $up['filename'];
        } else {
            set_flash('error', 'Please select an image file to upload.');
            redirect('dashboard/creator.php');
        }
    }

    $insPort = $pdo->prepare("
        INSERT INTO portfolio_items (creator_id, title, media_type, file_path, video_url, caption)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $insPort->execute([$creatorId, $title, $mediaType, $imageFile, $videoUrl, $caption]);

    set_flash('success', 'Portfolio work added successfully!');
    redirect('dashboard/creator.php');
}

// -------------------------------------------------------------
// 5. DELETE PORTFOLIO ITEM
// -------------------------------------------------------------
elseif ($action === 'delete_portfolio') {
    $portfolioId = (int) ($_POST['portfolio_id'] ?? 0);
    $delPort = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ? AND creator_id = ?");
    $delPort->execute([$portfolioId, $creatorId]);
    set_flash('success', 'Portfolio item removed.');
    redirect('dashboard/creator.php');
}

// -------------------------------------------------------------
// 6. UPDATE PROFILE SETTINGS
// -------------------------------------------------------------
elseif ($action === 'update_profile') {
    $studioName      = trim($_POST['studio_name'] ?? '');
    $startingPrice   = (float) ($_POST['starting_price'] ?? 3000.00);
    $experienceYears = (int) ($_POST['experience_years'] ?? 1);
    $bio             = trim($_POST['bio'] ?? '');

    if (empty($studioName) || $startingPrice < 500) {
        set_flash('error', 'Please specify a valid studio name and starting price.');
        redirect('dashboard/creator.php');
    }

    $updProfile = $pdo->prepare("
        UPDATE creator_profiles 
        SET studio_name = ?, starting_price = ?, experience_years = ?, bio = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $updProfile->execute([$studioName, $startingPrice, $experienceYears, $bio, $creatorId]);

    set_flash('success', 'Profile and pricing updated.');
    redirect('dashboard/creator.php');
}

else {
    redirect('dashboard/creator.php');
}
