<?php
/**
 * Shoot Karo - Customer Dashboard
 */
$pageTitle = 'My Bookings & Shoots';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('customer');

$pdo = get_db_connection();
$user = current_user();
$userId = $user['id'];

// Fetch Customer's Bookings
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        cp.studio_name,
        u_cr.name as creator_name,
        u_cr.phone as creator_phone,
        u_cr.email as creator_email,
        c.name as city_name,
        a.name as area_name,
        r.id as existing_review_id,
        r.rating as review_rating,
        r.comment as review_comment
    FROM bookings b
    JOIN creator_profiles cp ON b.creator_id = cp.id
    JOIN users u_cr ON cp.user_id = u_cr.id
    LEFT JOIN cities c ON b.city_id = c.id
    LEFT JOIN areas a ON b.area_id = a.id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

// Pre-fetch deliverables for these bookings
$deliverablesMap = [];
if (!empty($bookings)) {
    $bookingIds = array_column($bookings, 'id');
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $delivStmt = $pdo->prepare("SELECT * FROM booking_deliverables WHERE booking_id IN ($placeholders) ORDER BY uploaded_at DESC");
    $delivStmt->execute($bookingIds);
    while ($d = $delivStmt->fetch()) {
        $deliverablesMap[$d['booking_id']][] = $d;
    }
}

// Booking counts for metrics
$totalBookings = count($bookings);
$activeBookings = 0;
$completedBookings = 0;
foreach ($bookings as $b) {
    if (in_array($b['status'], ['pending', 'accepted', 'confirmed', 'in_progress'])) {
        $activeBookings++;
    } elseif ($b['status'] === 'completed') {
        $completedBookings++;
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle">Customer Portal</div>
      <h1>Welcome, <?php echo e($user['name']); ?></h1>
      <p>Manage your photography sessions, view deliverable links, and submit shoot reviews.</p>
    </div>
    <div>
      <a href="<?php echo url('creators.php'); ?>" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Book Another Creator
      </a>
    </div>
  </div>

  <!-- Metric Overview Cards -->
  <div class="metric-grid">
    <div class="metric-card">
      <div class="metric-label">Total Bookings</div>
      <div class="metric-value"><?php echo $totalBookings; ?></div>
      <div class="metric-sub">Lifetime requests</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Active / Scheduled</div>
      <div class="metric-value"><?php echo $activeBookings; ?></div>
      <div class="metric-sub">Shoots in queue</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Completed Shoots</div>
      <div class="metric-value"><?php echo $completedBookings; ?></div>
      <div class="metric-sub">Ready for download</div>
    </div>
  </div>

  <!-- Bookings List -->
  <div class="card">
    <div class="card-header">
      <h3 style="margin: 0;">My Shoot Bookings</h3>
    </div>

    <?php if (empty($bookings)): ?>
      <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
        <p style="margin-bottom: 16px;">You haven't booked any shoots yet.</p>
        <a href="<?php echo url('creators.php'); ?>" class="btn btn-secondary">Explore Creators</a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Creator / Studio</th>
              <th>Date &amp; Venue</th>
              <th>Occasion</th>
              <th>Cost &amp; Advance</th>
              <th>Status</th>
              <th>Deliverables / Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <?php $delivs = $deliverablesMap[$b['id']] ?? []; ?>
              <tr>
                <td>
                  <strong><?php echo e($b['booking_reference']); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo format_date($b['created_at']); ?></div>
                </td>
                <td>
                  <strong><?php echo e($b['studio_name'] ?: $b['creator_name']); ?></strong>
                  <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($b['creator_phone']); ?></div>
                </td>
                <td>
                  <strong><?php echo format_date($b['shoot_date']); ?></strong>
                  <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo format_time($b['shoot_time']); ?> &bull; <?php echo e($b['area_name']); ?></div>
                </td>
                <td>
                  <?php echo e($b['occasion']); ?>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo (int) $b['duration_hours']; ?> Hrs</div>
                </td>
                <td>
                  <div><strong><?php echo format_inr($b['total_amount']); ?></strong></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">
                    Adv: <?php echo format_inr($b['advance_amount']); ?> (<?php echo ucwords(str_replace('_', ' ', $b['payment_status'])); ?>)
                  </div>
                </td>
                <td>
                  <?php echo get_status_badge($b['status']); ?>
                  <?php if ($b['status'] === 'pending' && $b['payment_status'] === 'pending'): ?>
                    <div style="margin-top: 6px;">
                      <a href="<?php echo url('booking.php?creator_id=' . $b['creator_id']); ?>" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 2px 8px;">Pay Advance</a>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <!-- Deliverables Links -->
                  <?php if (!empty($delivs)): ?>
                    <div style="display: flex; flex-direction: column; gap: 4px; margin-bottom: 6px;">
                      <?php foreach ($delivs as $d): ?>
                        <?php if (!empty($d['download_url'])): ?>
                          <a href="<?php echo e($d['download_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm" style="font-size: 0.75rem; padding: 4px 8px;">
                            Download Deliverables &rarr;
                          </a>
                        <?php elseif (!empty($d['file_path'])): ?>
                          <a href="<?php echo url('uploads/deliverables/' . $d['file_path']); ?>" download class="btn btn-dark btn-sm" style="font-size: 0.75rem; padding: 4px 8px;">
                            Download File (ZIP/Media)
                          </a>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </div>
                  <?php elseif ($b['status'] === 'completed'): ?>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Processing deliverables</span>
                  <?php endif; ?>

                  <!-- Review Option for Completed Shoots -->
                  <?php if ($b['status'] === 'completed'): ?>
                    <?php if ($b['existing_review_id']): ?>
                      <div style="font-size: 0.75rem; color: #065f46; font-weight: 600;">
                        Reviewed: <?php echo (int) $b['review_rating']; ?>/5 Stars
                      </div>
                    <?php else: ?>
                      <button type="button" class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 4px 10px;" data-modal-target="review-modal-<?php echo $b['id']; ?>">
                        Leave Review
                      </button>

                      <!-- Modal for Customer Review -->
                      <div id="review-modal-<?php echo $b['id']; ?>" class="modal-overlay">
                        <div class="modal-card">
                          <h3 style="margin-bottom: 6px;">Rate Your Shoot Experience</h3>
                          <p style="font-size: 0.875rem; margin-bottom: 20px;">Review for <strong><?php echo e($b['studio_name'] ?: $b['creator_name']); ?></strong> (<?php echo e($b['booking_reference']); ?>)</p>

                          <form action="<?php echo url('actions/review-action.php'); ?>" method="POST">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                            <input type="hidden" name="creator_id" value="<?php echo $b['creator_id']; ?>">

                            <div class="form-group">
                              <label class="form-label">Rating (1 to 5 Stars) *</label>
                              <select name="rating" class="form-control" required>
                                <option value="5" selected>5 Stars - Outstanding</option>
                                <option value="4">4 Stars - Very Good</option>
                                <option value="3">3 Stars - Average</option>
                                <option value="2">2 Stars - Poor</option>
                                <option value="1">1 Star - Terrible</option>
                              </select>
                            </div>

                            <div class="form-group">
                              <label class="form-label">Your Feedback / Review *</label>
                              <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience regarding punctuality, creativity, and deliverable quality..." required></textarea>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                              <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                              <button type="submit" class="btn btn-primary">Submit Review</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
