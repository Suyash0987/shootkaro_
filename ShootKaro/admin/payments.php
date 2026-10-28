<?php
/**
 * Shoot Karo - Admin Financial & Commission Ledger
 */
$pageTitle = 'Payments, Commissions & Creator Payouts';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();

// Aggregate Stats
$totalGmv = (float) $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status = 'completed'")->fetchColumn();
$totalAdvanceCollected = (float) $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'successful'")->fetchColumn();
$totalCommissionDeducted = (float) $pdo->query("SELECT SUM(platform_commission_amount) FROM creator_payouts")->fetchColumn();
$totalPayoutsPaid = (float) $pdo->query("SELECT SUM(creator_net_amount) FROM creator_payouts WHERE payout_status = 'paid'")->fetchColumn();
$totalPayoutsPending = (float) $pdo->query("SELECT SUM(creator_net_amount) FROM creator_payouts WHERE payout_status = 'pending'")->fetchColumn();

// Fetch Payouts Ledger
$payouts = $pdo->query("
    SELECT 
        p.*,
        b.booking_reference,
        b.shoot_date,
        b.customer_name,
        b.status as booking_status,
        cp.studio_name,
        u_cr.name as creator_name,
        u_cr.email as creator_email,
        u_cr.phone as creator_phone
    FROM creator_payouts p
    JOIN bookings b ON p.booking_id = b.id
    JOIN creator_profiles cp ON p.creator_id = cp.id
    JOIN users u_cr ON cp.user_id = u_cr.id
    ORDER BY p.created_at DESC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle"><a href="<?php echo url('admin/index.php'); ?>" style="color: var(--text-muted);">&larr; Admin Overview</a></div>
      <h1>Financial &amp; Commission Ledger</h1>
      <p>Audit advance collections, platform revenue cuts (<?php echo PLATFORM_COMMISSION_PERCENT; ?>%), and settle creator balances.</p>
    </div>
  </div>

  <!-- Financial Metric Cards -->
  <div class="metric-grid">
    <div class="metric-card">
      <div class="metric-label">Completed Shoots GMV</div>
      <div class="metric-value"><?php echo format_inr($totalGmv); ?></div>
      <div class="metric-sub">Gross marketplace volume</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Advance Collected</div>
      <div class="metric-value"><?php echo format_inr($totalAdvanceCollected); ?></div>
      <div class="metric-sub">Platform bank deposits</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Platform Commissions</div>
      <div class="metric-value"><?php echo format_inr($totalCommissionDeducted); ?></div>
      <div class="metric-sub"><?php echo PLATFORM_COMMISSION_PERCENT; ?>% net revenue retained</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Pending Creator Payouts</div>
      <div class="metric-value" style="<?php echo $totalPayoutsPending > 0 ? 'color: #b45309;' : ''; ?>">
        <?php echo format_inr($totalPayoutsPending); ?>
      </div>
      <div class="metric-sub">Unsettled creator earnings</div>
    </div>
  </div>

  <!-- Payout Ledger Table -->
  <div class="card">
    <div class="card-header">
      <h3 style="margin: 0;">Creator Earnings &amp; Settlements</h3>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Booking Reference</th>
            <th>Creator / Studio</th>
            <th>Booking Total</th>
            <th>Commission (<?php echo PLATFORM_COMMISSION_PERCENT; ?>%)</th>
            <th>Creator Net Payable</th>
            <th>Payout Status</th>
            <th>Settlement Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payouts)): ?>
            <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No payout ledger entries recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($payouts as $p): ?>
              <tr>
                <td>
                  <strong><?php echo e($p['booking_reference']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">Shoot: <?php echo format_date($p['shoot_date']); ?></div>
                  <div style="font-size: 0.75rem;"><?php echo get_status_badge($p['booking_status']); ?></div>
                </td>
                <td>
                  <strong><?php echo e($p['studio_name'] ?: $p['creator_name']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($p['creator_phone']); ?></div>
                </td>
                <td><strong><?php echo format_inr($p['total_booking_amount']); ?></strong></td>
                <td>
                  <span style="color: var(--primary-black); font-weight: 700;"><?php echo format_inr($p['platform_commission_amount']); ?></span>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">(<?php echo (float)$p['platform_commission_rate']; ?>%)</div>
                </td>
                <td>
                  <strong style="color: #065f46; font-size: 1.0625rem;"><?php echo format_inr($p['creator_net_amount']); ?></strong>
                </td>
                <td>
                  <?php if ($p['payout_status'] === 'paid'): ?>
                    <span class="badge badge-success">Settled / Paid</span>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                      Ref: <?php echo e($p['payout_reference'] ?? 'Direct Transfer'); ?>
                    </div>
                  <?php else: ?>
                    <span class="badge badge-pending">Pending Settlement</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['payout_status'] === 'pending'): ?>
                    <form action="<?php echo url('actions/admin-action.php?action=mark_payout_paid'); ?>" method="POST" style="margin: 0;">
                      <?php echo csrf_input(); ?>
                      <input type="hidden" name="payout_id" value="<?php echo $p['id']; ?>">
                      <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Verify that payment has been transferred to creator before marking as paid?')">
                        Mark Paid
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="font-size: 0.8125rem; color: var(--text-muted);">&check; Settled</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
