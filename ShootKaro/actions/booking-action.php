<?php
/**
 * Action Controller: Booking Processor
 * Atomically validates, calculates, and records new bookings
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('creators.php');
}

require_csrf();

$pdo = get_db_connection();

// Collect Form Fields
$creatorId              = (int) ($_POST['creator_id'] ?? 0);
$customerName           = trim($_POST['customer_name'] ?? '');
$customerEmail          = trim($_POST['customer_email'] ?? '');
$customerPhone          = trim($_POST['customer_phone'] ?? '');
$cityId                 = (int) ($_POST['city_id'] ?? 0);
$areaId                 = (int) ($_POST['area_id'] ?? 0);
$shootVenue             = trim($_POST['shoot_venue'] ?? '');
$shootDate              = trim($_POST['shoot_date'] ?? '');
$shootTime              = trim($_POST['shoot_time'] ?? '');
$occasion               = trim($_POST['occasion'] ?? 'Other');
$selectedServices       = $_POST['selected_services'] ?? [];
$deliverableType        = trim($_POST['deliverable_type'] ?? 'both');
$durationHours          = max(1, (int) ($_POST['duration_hours'] ?? 2));
$photoCountEstimate     = trim($_POST['photo_count_estimate'] ?? '');
$videoDurationEstimate  = trim($_POST['video_duration_estimate'] ?? '');
$editingRequirements    = trim($_POST['editing_requirements'] ?? '');
$specialInstructions    = trim($_POST['special_instructions'] ?? '');
$paymentMethod          = trim($_POST['payment_method'] ?? 'upi');

// Validation
if ($creatorId <= 0 || empty($customerName) || empty($customerEmail) || empty($customerPhone) || empty($shootVenue) || empty($shootDate) || empty($shootTime)) {
    set_flash('error', 'Please fill in all mandatory booking fields.');
    redirect('booking.php?creator_id=' . $creatorId);
}

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please provide a valid email address.');
    redirect('booking.php?creator_id=' . $creatorId);
}

if (empty($selectedServices) || !is_array($selectedServices)) {
    set_flash('error', 'Please select at least one shooting equipment service.');
    redirect('booking.php?creator_id=' . $creatorId);
}

// 1. Fetch Creator and verify offered services
$creatorStmt = $pdo->prepare("SELECT cp.*, u.id as user_id, u.name as creator_name FROM creator_profiles cp JOIN users u ON cp.user_id = u.id WHERE cp.id = ? AND cp.is_approved = 1 AND cp.is_available = 1");
$creatorStmt->execute([$creatorId]);
$creator = $creatorStmt->fetch();

if (!$creator) {
    set_flash('error', 'Selected creator is not available.');
    redirect('creators.php');
}

// Fetch creator services to prevent booking services they do not provide
$srvStmt = $pdo->prepare("SELECT service_type, base_price, service_title FROM creator_services WHERE creator_id = ? AND is_active = 1");
$srvStmt->execute([$creatorId]);
$offeredServices = [];
while ($row = $srvStmt->fetch()) {
    $offeredServices[$row['service_type']] = $row;
}

$validatedServices = [];
foreach ($selectedServices as $st) {
    if (isset($offeredServices[$st])) {
        $validatedServices[] = $offeredServices[$st];
    }
}

if (empty($validatedServices)) {
    set_flash('error', 'Selected services are not offered by this creator.');
    redirect('booking.php?creator_id=' . $creatorId);
}

// 2. Resolve Customer Account
$customerId = null;
if (is_logged_in()) {
    $customerId = (int) $_SESSION['user_id'];
} else {
    // Check if user exists by email
    $userStmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
    $userStmt->execute([$customerEmail]);
    $existingUser = $userStmt->fetch();

    if ($existingUser) {
        $customerId = (int) $existingUser['id'];
    } else {
        // Create new customer account automatically
        $tempPassword = bin2hex(random_bytes(4)); // 8-char random password
        $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT);
        $createStmt = $pdo->prepare("
            INSERT INTO users (role, name, email, phone, password_hash, status)
            VALUES ('customer', ?, ?, ?, ?, 'active')
        ");
        $createStmt->execute([$customerName, $customerEmail, $customerPhone, $passwordHash]);
        $customerId = (int) $pdo->lastInsertId();

        // Auto login newly registered customer
        login_user([
            'id'    => $customerId,
            'role'  => 'customer',
            'name'  => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone
        ]);
    }
}

// 3. Server-Side Financial Recalculation (Never trust browser values)
$baseStartingPrice = (float) $creator['starting_price'];
$equipmentAddons = 0.00;

foreach ($validatedServices as $idx => $s) {
    if ($idx > 0) {
        $equipmentAddons += ((float) $s['base_price'] * 0.75); // 25% bundle discount
    }
}

$durationCharges = 0.00;
if ($durationHours > 2) {
    $durationCharges = ($durationHours - 2) * ($baseStartingPrice * 0.35);
}

$totalAmount = round($baseStartingPrice + $equipmentAddons + $durationCharges, 2);
$advancePercent = DEFAULT_ADVANCE_PERCENT;
$advanceAmount = round($totalAmount * ($advancePercent / 100), 2);
$balanceAmount = round($totalAmount - $advanceAmount, 2);

// Booking status and payment status logic
$bookingReference = generate_booking_reference();
$paymentReference = generate_payment_reference();

if ($paymentMethod === 'cash_pay_later') {
    $bookingStatus = 'pending';
    $paymentStatus = 'pending';
} else {
    // Simulated instant payment confirmation
    $bookingStatus = 'confirmed';
    $paymentStatus = 'advance_paid';
}

try {
    $pdo->beginTransaction();

    // 4. Insert Booking
    $bookSql = "
        INSERT INTO bookings (
            booking_reference, customer_id, creator_id, city_id, area_id,
            customer_name, customer_email, customer_phone, shoot_venue,
            shoot_date, shoot_time, occasion, duration_hours, deliverable_type,
            photo_count_estimate, video_duration_estimate, editing_requirements, special_instructions,
            starting_price, equipment_charges, duration_charges, total_amount,
            advance_percentage, advance_amount, balance_amount, status, payment_status
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ";

    $bookStmt = $pdo->prepare($bookSql);
    $bookStmt->execute([
        $bookingReference, $customerId, $creatorId, $cityId, $areaId,
        $customerName, $customerEmail, $customerPhone, $shootVenue,
        $shootDate, $shootTime, $occasion, $durationHours, $deliverableType,
        $photoCountEstimate, $videoDurationEstimate, $editingRequirements, $specialInstructions,
        $baseStartingPrice, $equipmentAddons, $durationCharges, $totalAmount,
        $advancePercent, $advanceAmount, $balanceAmount, $bookingStatus, $paymentStatus
    ]);
    $bookingId = (int) $pdo->lastInsertId();

    // 5. Insert Booking Services
    $bsStmt = $pdo->prepare("INSERT INTO booking_services (booking_id, service_type, service_price) VALUES (?, ?, ?)");
    foreach ($validatedServices as $idx => $s) {
        $price = ($idx === 0) ? $baseStartingPrice : ((float) $s['base_price'] * 0.75);
        $bsStmt->execute([$bookingId, $s['service_type'], $price]);
    }

    // 6. Record Payment if advance was simulated
    if ($paymentStatus === 'advance_paid') {
        $payStmt = $pdo->prepare("
            INSERT INTO payments (booking_id, payment_reference, payment_type, payment_method, amount, status, transaction_data, paid_at)
            VALUES (?, ?, 'advance', ?, ?, 'successful', ?, NOW())
        ");
        $mockPayload = json_encode([
            'gateway'   => 'mock_gateway_simulated',
            'method'    => $paymentMethod,
            'timestamp' => date('c'),
            'verified'  => true
        ]);
        $payStmt->execute([$bookingId, $paymentReference, $paymentMethod, $advanceAmount, $mockPayload]);
    }

    // 7. Calculate Commission & Ledger Entry
    $commissionRate = PLATFORM_COMMISSION_PERCENT;
    $commissionAmount = round($totalAmount * ($commissionRate / 100), 2);
    $creatorNetAmount = round($totalAmount - $commissionAmount, 2);

    $payoutStmt = $pdo->prepare("
        INSERT INTO creator_payouts (
            creator_id, booking_id, total_booking_amount, platform_commission_rate,
            platform_commission_amount, creator_net_amount, payout_status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'Created upon booking request')
    ");
    $payoutStmt->execute([$creatorId, $bookingId, $totalAmount, $commissionRate, $commissionAmount, $creatorNetAmount]);

    // 8. Notification to Creator
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
    $creatorUserStmt = $pdo->prepare("SELECT user_id FROM creator_profiles WHERE id = ?");
    $creatorUserStmt->execute([$creatorId]);
    $creatorUserId = $creatorUserStmt->fetchColumn();

    if ($creatorUserId) {
        $notifStmt->execute([
            $creatorUserId,
            'New Booking Received',
            "New shoot booking {$bookingReference} for {$occasion} on {$shootDate} received from {$customerName}.",
            'dashboard/creator.php'
        ]);
    }

    $pdo->commit();

    set_flash('success', "Booking request {$bookingReference} confirmed successfully!");
    redirect('booking-confirmation.php?ref=' . urlencode($bookingReference));

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Booking processing failed: " . $e->getMessage());
    set_flash('error', 'Booking failed due to an internal system error. Please try again.');
    redirect('booking.php?creator_id=' . $creatorId);
}
