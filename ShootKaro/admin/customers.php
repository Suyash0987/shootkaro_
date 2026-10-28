<?php
/**
 * Shoot Karo - Admin Customers Management
 */
$pageTitle = 'Manage Customers';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();

$customers = $pdo->query("
    SELECT 
        u.*,
        COUNT(b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN b.total_amount ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN bookings b ON u.id = b.customer_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle"><a href="<?php echo url('admin/index.php'); ?>" style="color: var(--text-muted);">&larr; Admin Overview</a></div>
      <h1>Customer Accounts</h1>
      <p>View registered clients, booking history volumes, and account statuses.</p>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Email Address</th>
            <th>Phone</th>
            <th>Total Bookings</th>
            <th>Total Spent</th>
            <th>Status</th>
            <th>Registered On</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No customers registered yet.</td></tr>
          <?php else: ?>
            <?php foreach ($customers as $c): ?>
              <tr>
                <td><strong><?php echo e($c['name']); ?></strong></td>
                <td><?php echo e($c['email']); ?></td>
                <td><?php echo e($c['phone']); ?></td>
                <td><strong><?php echo (int) $c['total_bookings']; ?></strong> Shoots</td>
                <td><?php echo format_inr((float) $c['total_spent']); ?></td>
                <td><?php echo get_status_badge($c['status']); ?></td>
                <td><?php echo format_date($c['created_at']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
