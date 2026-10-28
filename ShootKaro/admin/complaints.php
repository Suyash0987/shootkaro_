<?php
/**
 * Shoot Karo - Admin Customer Complaints Desk
 */
$pageTitle = 'Customer Complaints & Disputes';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();

$complaints = $pdo->query("
    SELECT 
        c.*,
        u.name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        b.booking_reference
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN bookings b ON c.booking_id = b.id
    ORDER BY c.created_at DESC
")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle"><a href="<?php echo url('admin/index.php'); ?>" style="color: var(--text-muted);">&larr; Admin Overview</a></div>
      <h1>Complaints &amp; Support Tickets</h1>
      <p>Resolve customer disputes, shoot delays, and service quality inquiries.</p>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>User &amp; Contact</th>
            <th>Booking Reference</th>
            <th>Subject &amp; Dispute Message</th>
            <th>Status</th>
            <th>Resolution Notes</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($complaints)): ?>
            <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No open complaints filed.</td></tr>
          <?php else: ?>
            <?php foreach ($complaints as $cmp): ?>
              <tr>
                <td>
                  <strong><?php echo e($cmp['user_name']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($cmp['user_email']); ?> &bull; <?php echo e($cmp['user_phone']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo format_date($cmp['created_at']); ?></div>
                </td>
                <td>
                  <?php if (!empty($cmp['booking_reference'])): ?>
                    <strong><?php echo e($cmp['booking_reference']); ?></strong>
                  <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.8125rem;">General Inquiries</span>
                  <?php endif; ?>
                </td>
                <td style="max-width: 320px;">
                  <strong style="display: block; margin-bottom: 4px;"><?php echo e($cmp['subject']); ?></strong>
                  <p style="font-size: 0.8125rem; color: var(--text-main); margin: 0; line-height: 1.4;">
                    <?php echo nl2br(e($cmp['message'])); ?>
                  </p>
                </td>
                <td>
                  <?php echo get_status_badge($cmp['status']); ?>
                </td>
                <td style="max-width: 200px; font-size: 0.8125rem; color: var(--text-muted);">
                  <?php echo e($cmp['resolution_notes'] ?: 'No notes yet.'); ?>
                </td>
                <td>
                  <button type="button" class="btn btn-secondary btn-sm" data-modal-target="resolve-modal-<?php echo $cmp['id']; ?>">
                    Resolve
                  </button>

                  <!-- Modal to update complaint -->
                  <div id="resolve-modal-<?php echo $cmp['id']; ?>" class="modal-overlay">
                    <div class="modal-card">
                      <h3 style="margin-bottom: 6px;">Manage Support Ticket #<?php echo $cmp['id']; ?></h3>
                      <p style="font-size: 0.875rem; margin-bottom: 16px;">Client: <strong><?php echo e($cmp['user_name']); ?></strong> &bull; <?php echo e($cmp['subject']); ?></p>

                      <form action="<?php echo url('actions/admin-action.php?action=resolve_complaint'); ?>" method="POST">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="complaint_id" value="<?php echo $cmp['id']; ?>">

                        <div class="form-group">
                          <label class="form-label">Ticket Status</label>
                          <select name="status" class="form-control" required>
                            <option value="open" <?php echo ($cmp['status'] === 'open') ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo ($cmp['status'] === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo ($cmp['status'] === 'resolved') ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo ($cmp['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                          </select>
                        </div>

                        <div class="form-group">
                          <label class="form-label">Resolution Summary / Action Notes</label>
                          <textarea name="resolution_notes" class="form-control" rows="3" placeholder="Explain the resolution or outcome..."><?php echo e($cmp['resolution_notes']); ?></textarea>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                          <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                          <button type="submit" class="btn btn-primary">Save Ticket</button>
                        </div>
                      </form>
                    </div>
                  </div>
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
