<?php
/**
 * Shoot Karo - Admin Bookings Management
 */
$pageTitle = 'Manage Platform Bookings';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();
$statusFilter = $_GET['status'] ?? 'all';

$sql = "
    SELECT 
        b.*,
        cp.studio_name,
        u_cr.name as creator_name,
        u_cr.phone as creator_phone,
        c.name as city_name,
        a.name as area_name
    FROM bookings b
    JOIN creator_profiles cp ON b.creator_id = cp.id
    JOIN users u_cr ON cp.user_id = u_cr.id
    LEFT JOIN cities c ON b.city_id = c.id
    LEFT JOIN areas a ON b.area_id = a.id
";

$params = [];
if ($statusFilter !== 'all') {
    $sql .= " WHERE b.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle"><a href="<?php echo url('admin/index.php'); ?>" style="color: var(--text-muted);">&larr; Admin Overview</a></div>
      <h1>All Bookings</h1>
      <p>Audit customer requests, monitor shoot fulfillment, and manage booking lifecycles.</p>
    </div>

    <!-- Status Filters -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <a href="<?php echo url('admin/bookings.php?status=all'); ?>" class="btn <?php echo ($statusFilter === 'all') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All</a>
      <a href="<?php echo url('admin/bookings.php?status=pending'); ?>" class="btn <?php echo ($statusFilter === 'pending') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Pending</a>
      <a href="<?php echo url('admin/bookings.php?status=confirmed'); ?>" class="btn <?php echo ($statusFilter === 'confirmed') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Confirmed</a>
      <a href="<?php echo url('admin/bookings.php?status=in_progress'); ?>" class="btn <?php echo ($statusFilter === 'in_progress') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">In Progress</a>
      <a href="<?php echo url('admin/bookings.php?status=completed'); ?>" class="btn <?php echo ($statusFilter === 'completed') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Completed</a>
      <a href="<?php echo url('admin/bookings.php?status=cancelled'); ?>" class="btn <?php echo ($statusFilter === 'cancelled') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Cancelled</a>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Customer</th>
            <th>Creator</th>
            <th>Venue &amp; Date</th>
            <th>Financials</th>
            <th>Status</th>
            <th>Admin Override</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookings)): ?>
            <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No bookings found for this filter.</td></tr>
          <?php else: ?>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td>
                  <strong><?php echo e($b['booking_reference']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo format_date($b['created_at']); ?></div>
                </td>
                <td>
                  <strong><?php echo e($b['customer_name']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($b['customer_phone']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($b['customer_email']); ?></div>
                </td>
                <td>
                  <strong><?php echo e($b['studio_name'] ?: $b['creator_name']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($b['creator_phone']); ?></div>
                </td>
                <td>
                  <div><strong><?php echo format_date($b['shoot_date']); ?></strong> (<?php echo format_time($b['shoot_time']); ?>)</div>
                  <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($b['area_name']); ?> &bull; <?php echo e($b['occasion']); ?></div>
                </td>
                <td>
                  <div><strong><?php echo format_inr($b['total_amount']); ?></strong></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">Adv: <?php echo format_inr($b['advance_amount']); ?> (<?php echo ucwords(str_replace('_', ' ', $b['payment_status'])); ?>)</div>
                </td>
                <td>
                  <?php echo get_status_badge($b['status']); ?>
                </td>
                <td>
                  <form action="<?php echo url('actions/admin-action.php?action=update_booking_status'); ?>" method="POST" style="display: flex; gap: 6px;">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <select name="new_status" class="form-control" style="font-size: 0.8125rem; padding: 4px 8px; width: auto;">
                      <option value="pending" <?php echo ($b['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                      <option value="accepted" <?php echo ($b['status'] === 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                      <option value="confirmed" <?php echo ($b['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                      <option value="in_progress" <?php echo ($b['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                      <option value="completed" <?php echo ($b['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                      <option value="cancelled" <?php echo ($b['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm">Update</button>
                  </form>
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
