<?php
/**
 * Action Controller: Admin Operations
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');
require_csrf();

$pdo = get_db_connection();
$action = $_GET['action'] ?? '';

// -------------------------------------------------------------
// 1. APPROVE CREATOR
// -------------------------------------------------------------
if ($action === 'approve_creator') {
    $creatorId = (int) ($_POST['creator_id'] ?? 0);

    $cStmt = $pdo->prepare("SELECT user_id, studio_name FROM creator_profiles WHERE id = ?");
    $cStmt->execute([$creatorId]);
    $creator = $cStmt->fetch();

    if ($creator) {
        $pdo->beginTransaction();
        $updProfile = $pdo->prepare("UPDATE creator_profiles SET is_approved = 1, updated_at = NOW() WHERE id = ?");
        $updProfile->execute([$creatorId]);

        $updUser = $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?");
        $updUser->execute([$creator['user_id']]);

        // Send notification
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([
            $creator['user_id'],
            'Application Approved',
            'Congratulations! Your creator profile has been verified and is now live on Shoot Karo.',
            'dashboard/creator.php'
        ]);

        $pdo->commit();
        set_flash('success', "Creator '{$creator['studio_name']}' approved successfully.");
    }
    redirect('admin/creators.php');
}

// -------------------------------------------------------------
// 2. TOGGLE CREATOR STATUS (Suspend / Reactivate)
// -------------------------------------------------------------
elseif ($action === 'toggle_creator_status') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? 'active');

    if (in_array($newStatus, ['active', 'suspended'], true)) {
        $upd = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? AND role = 'creator'");
        $upd->execute([$newStatus, $userId]);
        set_flash('success', "Creator account status updated to {$newStatus}.");
    }
    redirect('admin/creators.php');
}

// -------------------------------------------------------------
// 3. OVERRIDE BOOKING STATUS
// -------------------------------------------------------------
elseif ($action === 'update_booking_status') {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');

    $allowed = ['pending', 'accepted', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    if (in_array($newStatus, $allowed, true)) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $bookingId]);
        set_flash('success', "Booking status updated to {$newStatus}.");
    }
    redirect('admin/bookings.php');
}

// -------------------------------------------------------------
// 4. MARK CREATOR PAYOUT AS PAID
// -------------------------------------------------------------
elseif ($action === 'mark_payout_paid') {
    $payoutId = (int) ($_POST['payout_id'] ?? 0);
    $reference = 'SETTLE-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    $stmt = $pdo->prepare("
        UPDATE creator_payouts 
        SET payout_status = 'paid', payout_date = NOW(), payout_reference = ?, notes = 'Settled via admin verification'
        WHERE id = ?
    ");
    $stmt->execute([$reference, $payoutId]);

    set_flash('success', "Creator payout marked as paid. Settlement ref: {$reference}.");
    redirect('admin/payments.php');
}

// -------------------------------------------------------------
// 5. RESOLVE COMPLAINT TICKET
// -------------------------------------------------------------
elseif ($action === 'resolve_complaint') {
    $complaintId = (int) ($_POST['complaint_id'] ?? 0);
    $status      = trim($_POST['status'] ?? 'resolved');
    $notes       = trim($_POST['resolution_notes'] ?? '');

    $stmt = $pdo->prepare("UPDATE complaints SET status = ?, resolution_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $notes, $complaintId]);

    set_flash('success', "Support ticket #{$complaintId} updated.");
    redirect('admin/complaints.php');
}

// -------------------------------------------------------------
// 6. UPDATE GLOBAL SETTINGS
// -------------------------------------------------------------
elseif ($action === 'update_settings') {
    $commission = (float) ($_POST['platform_commission_percent'] ?? 10.00);
    $advance    = (float) ($_POST['default_advance_percent'] ?? 20.00);
    $phone      = trim($_POST['support_phone'] ?? '');
    $email      = trim($_POST['support_email'] ?? '');
    $siteName   = trim($_POST['site_name'] ?? 'SHOOT KARO');

    $settingsToUpdate = [
        'platform_commission_percent' => number_format($commission, 2, '.', ''),
        'default_advance_percent'     => number_format($advance, 2, '.', ''),
        'support_phone'               => $phone,
        'support_email'               => $email,
        'site_name'                   => $siteName,
    ];

    $updStmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
    foreach ($settingsToUpdate as $k => $v) {
        $updStmt->execute([$k, $v]);
    }

    set_flash('success', 'Global platform settings updated successfully.');
    redirect('admin/settings.php');
}

// -------------------------------------------------------------
// 7. ADD AREA LOCATION
// -------------------------------------------------------------
elseif ($action === 'add_area') {
    $cityId   = (int) ($_POST['city_id'] ?? 0);
    $areaName = trim($_POST['area_name'] ?? '');
    $zip      = trim($_POST['postal_code'] ?? '');

    if ($cityId > 0 && !empty($areaName)) {
        $stmt = $pdo->prepare("INSERT INTO areas (city_id, name, postal_code, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$cityId, $areaName, $zip]);
        set_flash('success', "Area '{$areaName}' added to Pune directory.");
    }
    redirect('admin/settings.php');
}

else {
    redirect('admin/index.php');
}
