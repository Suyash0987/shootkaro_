<?php
/**
 * Shoot Karo - Admin Executive Dashboard
 */
$pageTitle = 'Admin Executive Dashboard';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();

// 1. Core KPIs
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalCreators = (int) $pdo->query("SELECT COUNT(*) FROM creator_profiles cp JOIN users u ON cp.user_id = u.id WHERE cp.is_approved = 1 AND u.status = 'active'")->fetchColumn();
$pendingCreators = (int) $pdo->query("SELECT COUNT(*) FROM creator_profiles WHERE is_approved = 0")->fetchColumn();
$totalBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$activeShoots = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('pending', 'accepted', 'confirmed', 'in_progress')")->fetchColumn();
$openComplaints = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'open'")->fetchColumn();

// Financial Ledger Aggregates
$grossGmv = (float) $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status = 'completed'")->fetchColumn();
$platformCommissionEarned = (float) $pdo->query("SELECT SUM(platform_commission_amount) FROM creator_payouts WHERE payout_status = 'paid'")->fetchColumn();
$pendingPayouts = (float) $pdo->query("SELECT SUM(creator_net_amount) FROM creator_payouts WHERE payout_status = 'pending'")->fetchColumn();

// 2. Recent Bookings
$recentBookings = $pdo->query("
    SELECT 
        b.*,
        cp.studio_name,
        u_cr.name as creator_name,
        c.name as city_name,
        a.name as area_name
    FROM bookings b
    JOIN creator_profiles cp ON b.creator_id = cp.id
    JOIN users u_cr ON cp.user_id = u_cr.id
    LEFT JOIN cities c ON b.city_id = c.id
    LEFT JOIN areas a ON b.area_id = a.id
    ORDER BY b.created_at DESC
    LIMIT 6
")->fetchAll();

// 3. Pending Creator Approvals
$pendingCreatorList = $pdo->query("
    SELECT cp.*, u.name as user_name, u.email, u.phone, c.name as city_name, a.name as area_name
    FROM creator_profiles cp
    JOIN users u ON cp.user_id = u.id
    LEFT JOIN cities c ON cp.city_id = c.id
    LEFT JOIN areas a ON cp.area_id = a.id
    WHERE cp.is_approved = 0
    ORDER BY cp.created_at DESC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle">System Administration</div>
      <h1>Platform Overview</h1>
      <p>Monitor bookings, verify creator onboarding, track revenue commissions, and handle complaints.</p>
    </div>

    <!-- Quick Navigation Submenu -->
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="<?php echo url('admin/creators.php'); ?>" class="btn btn-secondary btn-sm">
        Creators (<?php echo $pendingCreators > 0 ? "<strong>{$pendingCreators} Pending</strong>" : $totalCreators; ?>)
      </a>
      <a href="<?php echo url('admin/bookings.php'); ?>" class="btn btn-secondary btn-sm">Bookings</a>
      <a href="<?php echo url('admin/payments.php'); ?>" class="btn btn-secondary btn-sm">Finance &amp; Commission</a>
      <a href="<?php echo url('admin/complaints.php'); ?>" class="btn btn-secondary btn-sm">
        Complaints <?php if ($openComplaints > 0): ?><span class="badge badge-danger" style="margin-left: 4px;"><?php echo $openComplaints; ?></span><?php endif; ?>
      </a>
      <a href="<?php echo url('admin/settings.php'); ?>" class="btn btn-primary btn-sm">Settings</a>
    </div>
  </div>

  <!-- Primary Metric Cards -->
  <div class="metric-grid">
    <div class="metric-card">
      <div class="metric-label">Gross Shoot GMV</div>
      <div class="metric-value"><?php echo format_inr($grossGmv); ?></div>
      <div class="metric-sub">Completed bookings value</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Platform Commission</div>
      <div class="metric-value"><?php echo format_inr($platformCommissionEarned); ?></div>
      <div class="metric-sub"><?php echo PLATFORM_COMMISSION_PERCENT; ?>% collected fee</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Pending Creator Payouts</div>
      <div class="metric-value"><?php echo format_inr($pendingPayouts); ?></div>
      <div class="metric-sub">Owed to creators</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Pending Approvals</div>
      <div class="metric-value" style="<?php echo $pendingCreators > 0 ? 'color: #b45309;' : ''; ?>">
        <?php echo $pendingCreators; ?>
      </div>
      <div class="metric-sub"><?php echo $totalCreators; ?> active creators</div>
    </div>
  </div>

  <!-- Pending Creator Approvals Banner (If any) -->
  <?php if (!empty($pendingCreatorList)): ?>
    <div class="card" style="border-left: 4px solid #b45309;">
      <div class="card-header">
        <h3 style="margin: 0; color: #92400e;">Creator Applications Awaiting Approval (<?php echo count($pendingCreatorList); ?>)</h3>
        <a href="<?php echo url('admin/creators.php'); ?>" class="btn btn-sm btn-outline">Review All</a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Studio / Name</th>
              <th>Contact Info</th>
              <th>Location</th>
              <th>Starting Rate</th>
              <th>Applied Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingCreatorList as $p): ?>
              <tr>
                <td><strong><?php echo e($p['studio_name']); ?></strong> (<?php echo e($p['user_name']); ?>)</td>
                <td><?php echo e($p['email']); ?><br><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($p['phone']); ?></span></td>
                <td><?php echo e($p['area_name'] ?? 'Pune'); ?></td>
                <td><?php echo format_inr($p['starting_price']); ?></td>
                <td><?php echo format_date($p['created_at']); ?></td>
                <td>
                  <form action="<?php echo url('actions/admin-action.php?action=approve_creator'); ?>" method="POST" style="display: inline-block;">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="creator_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Recent Bookings Audit Table -->
  <div class="card">
    <div class="card-header">
      <h3 style="margin: 0;">Recent Platform Bookings</h3>
      <a href="<?php echo url('admin/bookings.php'); ?>" class="btn btn-secondary btn-sm">View All Bookings</a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Creator</th>
            <th>Shoot Date &amp; Area</th>
            <th>Total Amount</th>
            <th>Advance</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentBookings as $rb): ?>
            <tr>
              <td><strong><?php echo e($rb['booking_reference']); ?></strong></td>
              <td>
                <?php echo e($rb['customer_name']); ?>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($rb['customer_phone']); ?></div>
              </td>
              <td><?php echo e($rb['studio_name'] ?: $rb['creator_name']); ?></td>
              <td>
                <?php echo format_date($rb['shoot_date']); ?>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($rb['area_name']); ?></div>
              </td>
              <td><strong><?php echo format_inr($rb['total_amount']); ?></strong></td>
              <td><?php echo format_inr($rb['advance_amount']); ?></td>
              <td><?php echo get_status_badge($rb['status']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
