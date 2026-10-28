<?php
/**
 * Shoot Karo - Admin Creator Directory & Moderation
 */
$pageTitle = 'Creator Management & Approvals';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();
$statusFilter = $_GET['filter'] ?? 'all';

$sql = "
    SELECT 
        cp.*,
        u.name as user_name,
        u.email,
        u.phone,
        u.status as user_status,
        c.name as city_name,
        a.name as area_name,
        COALESCE(AVG(r.rating), 5.0) as avg_rating,
        COUNT(DISTINCT r.id) as review_count,
        (SELECT COUNT(*) FROM bookings b WHERE b.creator_id = cp.id AND b.status = 'completed') as completed_shoots
    FROM creator_profiles cp
    JOIN users u ON cp.user_id = u.id
    LEFT JOIN cities c ON cp.city_id = c.id
    LEFT JOIN areas a ON cp.area_id = a.id
    LEFT JOIN reviews r ON cp.id = r.creator_id AND r.is_approved = 1
";

if ($statusFilter === 'pending') {
    $sql .= " WHERE cp.is_approved = 0";
} elseif ($statusFilter === 'active') {
    $sql .= " WHERE cp.is_approved = 1 AND u.status = 'active'";
} elseif ($statusFilter === 'suspended') {
    $sql .= " WHERE u.status = 'suspended'";
}

$sql .= " GROUP BY cp.id, cp.user_id, u.name, u.email, u.phone, u.status, c.name, a.name ORDER BY cp.created_at DESC";

$creators = $pdo->query($sql)->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle"><a href="<?php echo url('admin/index.php'); ?>" style="color: var(--text-muted);">&larr; Admin Overview</a></div>
      <h1>Creator Management</h1>
      <p>Verify incoming applications, adjust rates, or moderate active accounts.</p>
    </div>

    <!-- Filter Pills -->
    <div style="display: flex; gap: 8px;">
      <a href="<?php echo url('admin/creators.php?filter=all'); ?>" class="btn <?php echo ($statusFilter === 'all') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">All</a>
      <a href="<?php echo url('admin/creators.php?filter=pending'); ?>" class="btn <?php echo ($statusFilter === 'pending') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Pending Verification</a>
      <a href="<?php echo url('admin/creators.php?filter=active'); ?>" class="btn <?php echo ($statusFilter === 'active') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Active</a>
      <a href="<?php echo url('admin/creators.php?filter=suspended'); ?>" class="btn <?php echo ($statusFilter === 'suspended') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Suspended</a>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Studio / Creator</th>
            <th>Contact Details</th>
            <th>Base Area</th>
            <th>Starting Price</th>
            <th>Rating &amp; Shoots</th>
            <th>Status</th>
            <th>Admin Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($creators as $c): ?>
            <tr>
              <td>
                <strong><?php echo e($c['studio_name'] ?: $c['user_name']); ?></strong>
                <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($c['user_name']); ?> &bull; <?php echo (int) $c['experience_years']; ?> Yrs</div>
              </td>
              <td>
                <?php echo e($c['email']); ?>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($c['phone']); ?></div>
              </td>
              <td><?php echo e($c['area_name'] ?? 'Pune'); ?></td>
              <td><strong><?php echo format_inr($c['starting_price']); ?></strong></td>
              <td>
                <div style="font-weight: 700;"><?php echo number_format($c['avg_rating'], 1); ?> &starf; (<?php echo $c['review_count']; ?>)</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $c['completed_shoots']; ?> completed</div>
              </td>
              <td>
                <?php if (!$c['is_approved']): ?>
                  <span class="badge badge-pending">Pending Approval</span>
                <?php elseif ($c['user_status'] === 'suspended'): ?>
                  <span class="badge badge-danger">Suspended</span>
                <?php else: ?>
                  <span class="badge badge-success">Approved / Active</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <?php if (!$c['is_approved']): ?>
                    <form action="<?php echo url('actions/admin-action.php?action=approve_creator'); ?>" method="POST" style="margin: 0;">
                      <?php echo csrf_input(); ?>
                      <input type="hidden" name="creator_id" value="<?php echo $c['id']; ?>">
                      <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($c['user_status'] === 'active'): ?>
                    <form action="<?php echo url('actions/admin-action.php?action=toggle_creator_status'); ?>" method="POST" style="margin: 0;">
                      <?php echo csrf_input(); ?>
                      <input type="hidden" name="user_id" value="<?php echo $c['user_id']; ?>">
                      <input type="hidden" name="new_status" value="suspended">
                      <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Suspend this creator account?')">Suspend</button>
                    </form>
                  <?php elseif ($c['user_status'] === 'suspended'): ?>
                    <form action="<?php echo url('actions/admin-action.php?action=toggle_creator_status'); ?>" method="POST" style="margin: 0;">
                      <?php echo csrf_input(); ?>
                      <input type="hidden" name="user_id" value="<?php echo $c['user_id']; ?>">
                      <input type="hidden" name="new_status" value="active">
                      <button type="submit" class="btn btn-outline btn-sm">Reactivate</button>
                    </form>
                  <?php endif; ?>

                  <a href="<?php echo url('creator-profile.php?id=' . $c['id']); ?>" target="_blank" class="btn btn-secondary btn-sm">View</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
