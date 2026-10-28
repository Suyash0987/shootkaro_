<?php
/**
 * Shoot Karo - Booking Confirmation & Voucher
 */
$pageTitle = 'Booking Confirmation';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

$pdo = get_db_connection();
$reference = trim($_GET['ref'] ?? '');

if (empty($reference)) {
    redirect('index.php');
}

// Query Booking Details
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        cp.studio_name,
        u_cr.name as creator_name,
        u_cr.phone as creator_phone,
        u_cr.email as creator_email,
        c.name as city_name,
        a.name as area_name
    FROM bookings b
    JOIN creator_profiles cp ON b.creator_id = cp.id
    JOIN users u_cr ON cp.user_id = u_cr.id
    JOIN cities c ON b.city_id = c.id
    JOIN areas a ON b.area_id = a.id
    WHERE b.booking_reference = ?
");
$stmt->execute([$reference]);
$booking = $stmt->fetch();

if (!$booking) {
    set_flash('error', 'Booking reference not found.');
    redirect('index.php');
}

// Fetch Services for this Booking
$srvStmt = $pdo->prepare("SELECT service_type, service_price FROM booking_services WHERE booking_id = ?");
$srvStmt->execute([$booking['id']]);
$bookedServices = $srvStmt->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section" style="max-width: 760px;">
  <!-- Success Confirmation Card -->
  <div class="card" style="box-shadow: var(--shadow-lg); padding: 40px 36px;">
    <div style="text-align: center; margin-bottom: 28px; border-bottom: 1px solid var(--border-color); padding-bottom: 24px;">
      <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: var(--primary-black); color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h1 style="font-size: 1.875rem; margin-bottom: 6px;">Booking Request Placed!</h1>
      <p style="font-size: 0.9375rem;">Your shoot request has been recorded in the Shoot Karo system.</p>
      
      <div style="display: inline-block; background: var(--bg-secondary); border: 1px dashed var(--primary-black); border-radius: var(--radius-md); padding: 8px 18px; margin-top: 14px; font-size: 1.125rem; font-weight: 800; letter-spacing: 0.05em;">
        <?php echo e($booking['booking_reference']); ?>
      </div>
    </div>

    <!-- Booking Summary Grid -->
    <div style="margin-bottom: 28px;">
      <h3 style="font-size: 1.125rem; margin-bottom: 16px;">Shoot Information</h3>
      
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.9375rem; background: var(--bg-secondary); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
        <div>
          <span style="color: var(--text-muted); display: block; font-size: 0.8125rem; text-transform: uppercase;">Creator</span>
          <strong><?php echo e($booking['studio_name'] ?: $booking['creator_name']); ?></strong>
          <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($booking['creator_phone']); ?></div>
        </div>

        <div>
          <span style="color: var(--text-muted); display: block; font-size: 0.8125rem; text-transform: uppercase;">Booking Status</span>
          <?php echo get_status_badge($booking['status']); ?>
        </div>

        <div>
          <span style="color: var(--text-muted); display: block; font-size: 0.8125rem; text-transform: uppercase;">Date &amp; Time</span>
          <strong><?php echo format_date($booking['shoot_date']); ?> at <?php echo format_time($booking['shoot_time']); ?></strong>
        </div>

        <div>
          <span style="color: var(--text-muted); display: block; font-size: 0.8125rem; text-transform: uppercase;">Occasion</span>
          <strong><?php echo e($booking['occasion']); ?></strong> (<?php echo (int) $booking['duration_hours']; ?> Hours)
        </div>

        <div style="grid-column: span 2;">
          <span style="color: var(--text-muted); display: block; font-size: 0.8125rem; text-transform: uppercase;">Venue Location</span>
          <strong><?php echo e($booking['shoot_venue']); ?>, <?php echo e($booking['area_name']); ?>, <?php echo e($booking['city_name']); ?></strong>
        </div>

        <div style="grid-column: span 2;">
          <span style="color: var(--text-muted); display: block; font-size: 0.8125rem; text-transform: uppercase;">Selected Equipment</span>
          <div style="display: flex; gap: 8px; margin-top: 4px;">
            <?php foreach ($bookedServices as $bs): ?>
              <span class="service-tag" style="background: #ffffff;"><?php echo strtoupper($bs['service_type']); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Financial Breakdown Table -->
    <div style="margin-bottom: 28px;">
      <h3 style="font-size: 1.125rem; margin-bottom: 12px;">Financial Breakdown</h3>
      <table class="summary-table">
        <tbody>
          <tr>
            <td>Total Shoot Amount</td>
            <td style="text-align: right; font-weight: 700;"><?php echo format_inr($booking['total_amount']); ?></td>
          </tr>
          <tr class="advance-row">
            <td>
              Advance Paid (<?php echo (float) $booking['advance_percentage']; ?>%)
              <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">Status: <?php echo ucwords(str_replace('_', ' ', $booking['payment_status'])); ?></span>
            </td>
            <td style="text-align: right; font-weight: 800;"><?php echo format_inr($booking['advance_amount']); ?></td>
          </tr>
          <tr>
            <td><strong>Remaining Balance (Due directly on shoot date)</strong></td>
            <td style="text-align: right; font-weight: 800;"><?php echo format_inr($booking['balance_amount']); ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Actions -->
    <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
      <a href="<?php echo url('dashboard/customer.php'); ?>" class="btn btn-primary btn-lg">View in Customer Dashboard</a>
      <a href="javascript:window.print()" class="btn btn-secondary btn-lg">Print Booking Voucher</a>
      <a href="<?php echo url('index.php'); ?>" class="btn btn-outline btn-lg">Back to Home</a>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
