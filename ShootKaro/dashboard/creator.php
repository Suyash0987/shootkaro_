<?php
/**
 * Shoot Karo - Creator Dashboard & Studio Management
 */
$pageTitle = 'Creator Studio Dashboard';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('creator');

$pdo = get_db_connection();
$user = current_user();

// Fetch Creator Profile
$profileStmt = $pdo->prepare("
    SELECT cp.*, c.name as city_name, a.name as area_name 
    FROM creator_profiles cp
    LEFT JOIN cities c ON cp.city_id = c.id
    LEFT JOIN areas a ON cp.area_id = a.id
    WHERE cp.user_id = ?
");
$profileStmt->execute([$user['id']]);
$creator = $profileStmt->fetch();

if (!$creator) {
    set_flash('error', 'Creator profile not found.');
    redirect('index.php');
}

$creatorId = (int) $creator['id'];

// Check approval status
if (!$creator['is_approved']) {
    require_once INCLUDES_PATH . '/header.php';
    echo "
    <div class='container section' style='max-width: 650px;'>
      <div class='card' style='text-align: center; padding: 40px;'>
        <div style='font-size: 2.5rem; margin-bottom: 12px;'>⌛</div>
        <h2>Application Under Review</h2>
        <p style='color: var(--text-muted); margin-bottom: 20px;'>Your creator application for <strong>" . e($creator['studio_name']) . "</strong> has been received and is currently being verified by the Shoot Karo administration team. Please check back shortly.</p>
        <a href='" . url('index.php') . "' class='btn btn-secondary'>Return to Homepage</a>
      </div>
    </div>";
    require_once INCLUDES_PATH . '/footer.php';
    exit;
}

// 1. Fetch Creator Bookings
$bookStmt = $pdo->prepare("
    SELECT 
        b.*,
        c.name as city_name,
        a.name as area_name,
        cp_payout.creator_net_amount,
        cp_payout.platform_commission_amount,
        cp_payout.payout_status
    FROM bookings b
    LEFT JOIN cities c ON b.city_id = c.id
    LEFT JOIN areas a ON b.area_id = a.id
    LEFT JOIN creator_payouts cp_payout ON b.id = cp_payout.booking_id
    WHERE b.creator_id = ?
    ORDER BY b.created_at DESC
");
$bookStmt->execute([$creatorId]);
$bookings = $bookStmt->fetchAll();

// 2. Fetch Creator Services
$srvStmt = $pdo->prepare("SELECT * FROM creator_services WHERE creator_id = ?");
$srvStmt->execute([$creatorId]);
$services = $srvStmt->fetchAll();

// 3. Fetch Portfolio Media
$portStmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE creator_id = ? ORDER BY sort_order ASC, id DESC");
$portStmt->execute([$creatorId]);
$portfolio = $portStmt->fetchAll();

// 4. Financial Calculations
$totalGrossBookings = 0;
$totalPlatformCommission = 0;
$totalNetEarnings = 0;
$completedShootsCount = 0;
$pendingRequestsCount = 0;

foreach ($bookings as $b) {
    if ($b['status'] === 'completed') {
        $completedShootsCount++;
        $totalGrossBookings += (float) $b['total_amount'];
        $totalPlatformCommission += (float) ($b['platform_commission_amount'] ?? ($b['total_amount'] * 0.10));
        $totalNetEarnings += (float) ($b['creator_net_amount'] ?? ($b['total_amount'] * 0.90));
    } elseif ($b['status'] === 'pending') {
        $pendingRequestsCount++;
    }
}

// 5. Aggregated Ratings
$stats = get_creator_stats($creatorId);

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <!-- Creator Studio Header -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; align-items: center; gap: 18px;">
      <img src="<?php echo !empty($creator['profile_image']) ? url('uploads/profiles/' . $creator['profile_image']) : url('assets/logos/logo.svg'); ?>" alt="Profile" style="width: 64px; height: 64px; border-radius: var(--radius-md); object-fit: cover; border: 1px solid var(--border-color); background: var(--bg-tertiary);">
      <div>
        <div class="section-subtitle">Creator Studio Portal</div>
        <h1 style="font-size: 1.875rem; margin-bottom: 2px;"><?php echo e($creator['studio_name'] ?: $user['name']); ?></h1>
        <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($creator['area_name'] ?? 'Pune'); ?> &bull; Starting at <?php echo format_inr($creator['starting_price']); ?></div>
      </div>
    </div>

    <!-- Availability Toggle Form -->
    <div style="display: flex; align-items: center; gap: 12px;">
      <form action="<?php echo url('actions/creator-action.php?action=toggle_availability'); ?>" method="POST" style="margin: 0;">
        <?php echo csrf_input(); ?>
        <button type="submit" class="btn <?php echo $creator['is_available'] ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
          Status: <?php echo $creator['is_available'] ? 'Accepting Bookings (Online)' : 'Off Duty (Paused)'; ?>
        </button>
      </form>
      <a href="<?php echo url('creator-profile.php?id=' . $creatorId); ?>" target="_blank" class="btn btn-secondary btn-sm">Preview Public Profile &rarr;</a>
    </div>
  </div>

  <!-- Financial & Performance Metrics -->
  <div class="metric-grid">
    <div class="metric-card">
      <div class="metric-label">Net Earnings (Completed)</div>
      <div class="metric-value"><?php echo format_inr($totalNetEarnings); ?></div>
      <div class="metric-sub">After 10% platform commission</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Gross Revenue</div>
      <div class="metric-value"><?php echo format_inr($totalGrossBookings); ?></div>
      <div class="metric-sub">Platform commission: <?php echo format_inr($totalPlatformCommission); ?></div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Completed Shoots</div>
      <div class="metric-value"><?php echo $completedShootsCount; ?></div>
      <div class="metric-sub">Verified completions</div>
    </div>

    <div class="metric-card">
      <div class="metric-label">Customer Rating</div>
      <div class="metric-value" style="display: flex; align-items: center; gap: 6px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="#000000">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
        </svg>
        <?php echo number_format($stats['avg_rating'], 1); ?>
      </div>
      <div class="metric-sub">From <?php echo $stats['review_count']; ?> client reviews</div>
    </div>
  </div>

  <!-- ========================================================
       BOOKINGS MANAGEMENT SECTION
       ======================================================== -->
  <div class="card">
    <div class="card-header">
      <h3 style="margin: 0;">Incoming &amp; Active Bookings</h3>
      <span class="badge badge-info"><?php echo count($bookings); ?> Total Orders</span>
    </div>

    <?php if (empty($bookings)): ?>
      <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
        No booking orders received yet. Make sure your profile is available and portfolio media is updated!
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Reference &amp; Client</th>
              <th>Venue &amp; Timings</th>
              <th>Occasion &amp; Details</th>
              <th>Total &amp; Net Earnings</th>
              <th>Current Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td>
                  <strong><?php echo e($b['booking_reference']); ?></strong>
                  <div style="font-size: 0.875rem; font-weight: 600; margin-top: 2px;"><?php echo e($b['customer_name']); ?></div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo e($b['customer_phone']); ?> &bull; <?php echo e($b['customer_email']); ?></div>
                </td>
                <td>
                  <strong><?php echo format_date($b['shoot_date']); ?></strong> at <?php echo format_time($b['shoot_time']); ?>
                  <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($b['shoot_venue']); ?> (<?php echo e($b['area_name']); ?>)</div>
                </td>
                <td>
                  <strong><?php echo e($b['occasion']); ?></strong> (<?php echo (int) $b['duration_hours']; ?> Hrs)
                  <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;"><?php echo e($b['deliverable_type']); ?></div>
                  <?php if (!empty($b['special_instructions'])): ?>
                    <div style="font-size: 0.75rem; color: #4b5563; margin-top: 4px; font-style: italic;">
                      &ldquo;<?php echo e($b['special_instructions']); ?>&rdquo;
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div><strong><?php echo format_inr($b['total_amount']); ?></strong></div>
                  <div style="font-size: 0.75rem; color: #065f46; font-weight: 600;">
                    Net: <?php echo format_inr($b['creator_net_amount'] ?? ($b['total_amount'] * 0.9)); ?>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">
                    Adv: <?php echo format_inr($b['advance_amount']); ?> (<?php echo ucwords(str_replace('_', ' ', $b['payment_status'])); ?>)
                  </div>
                </td>
                <td>
                  <?php echo get_status_badge($b['status']); ?>
                </td>
                <td>
                  <div style="display: flex; flex-direction: column; gap: 6px;">
                    <!-- Pending Actions -->
                    <?php if ($b['status'] === 'pending'): ?>
                      <form action="<?php echo url('actions/creator-action.php?action=update_booking_status'); ?>" method="POST" style="margin: 0;">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                        <input type="hidden" name="new_status" value="accepted">
                        <button type="submit" class="btn btn-primary btn-sm btn-block">Accept Shoot</button>
                      </form>

                      <form action="<?php echo url('actions/creator-action.php?action=update_booking_status'); ?>" method="POST" style="margin: 0;">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                        <input type="hidden" name="new_status" value="rejected">
                        <button type="submit" class="btn btn-secondary btn-sm btn-block" onclick="return confirm('Decline this booking request?')">Reject</button>
                      </form>
                    <?php endif; ?>

                    <!-- Accepted / Confirmed -> Mark In Progress -->
                    <?php if (in_array($b['status'], ['accepted', 'confirmed'])): ?>
                      <form action="<?php echo url('actions/creator-action.php?action=update_booking_status'); ?>" method="POST" style="margin: 0;">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                        <input type="hidden" name="new_status" value="in_progress">
                        <button type="submit" class="btn btn-dark btn-sm btn-block">Start Shoot</button>
                      </form>
                    <?php endif; ?>

                    <!-- In Progress -> Upload Deliverables & Complete -->
                    <?php if (in_array($b['status'], ['in_progress', 'confirmed', 'accepted', 'completed'])): ?>
                      <button type="button" class="btn btn-outline btn-sm btn-block" data-modal-target="deliverable-modal-<?php echo $b['id']; ?>">
                        Deliverables &amp; Complete &rarr;
                      </button>

                      <!-- Deliverables Upload Modal -->
                      <div id="deliverable-modal-<?php echo $b['id']; ?>" class="modal-overlay">
                        <div class="modal-card">
                          <h3 style="margin-bottom: 6px;">Upload Shoot Deliverables</h3>
                          <p style="font-size: 0.875rem; margin-bottom: 18px;">Client: <strong><?php echo e($b['customer_name']); ?></strong> (<?php echo e($b['booking_reference']); ?>)</p>

                          <form action="<?php echo url('actions/creator-action.php?action=upload_deliverable'); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_input(); ?>
                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">

                            <div class="form-group">
                              <label class="form-label">Deliverable Title *</label>
                              <input type="text" name="title" class="form-control" placeholder="e.g. Master 4K Highlight Reel / Color-Graded Photos ZIP" required>
                            </div>

                            <div class="form-group">
                              <label class="form-label">Cloud Download URL (Google Drive, Dropbox, Vimeo, WeTransfer)</label>
                              <input type="url" name="download_url" class="form-control" placeholder="https://drive.google.com/drive/folders/your_folder">
                            </div>

                            <div class="form-group">
                              <label class="form-label">Or Upload File Directly (ZIP, JPG, MP4, PDF)</label>
                              <input type="file" name="deliverable_file" class="form-control">
                              <div class="form-hint">Max file size: 15MB. For larger sets, use Cloud URL above.</div>
                            </div>

                            <div class="form-group">
                              <label class="form-label">Delivery Notes for Client</label>
                              <textarea name="notes" class="form-control" rows="2" placeholder="e.g. High-res files graded with warm tones. Access code is 1234."></textarea>
                            </div>

                            <div class="form-group">
                              <label style="display: flex; align-items: center; gap: 8px; font-size: 0.875rem; font-weight: 600;">
                                <input type="checkbox" name="mark_as_completed" value="1" checked>
                                Mark shoot as <strong>Completed</strong> upon upload
                              </label>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                              <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                              <button type="submit" class="btn btn-primary">Upload &amp; Notify Client</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ========================================================
       PORTFOLIO & PROFILE MANAGEMENT
       ======================================================== -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Add Portfolio Item -->
    <div class="card">
      <div class="card-header">
        <h3 style="margin: 0;">Add Portfolio Showcase</h3>
      </div>
      <form action="<?php echo url('actions/creator-action.php?action=add_portfolio'); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_input(); ?>

        <div class="form-group">
          <label class="form-label">Work Title *</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Pune Pre-Wedding Cinematic" required>
        </div>

        <div class="form-group">
          <label class="form-label">Media Type *</label>
          <select name="media_type" class="form-control" required>
            <option value="image">Showcase Image</option>
            <option value="video_link">YouTube / Vimeo Video Link</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Upload Image (JPG, PNG, WEBP)</label>
          <input type="file" name="portfolio_image" class="form-control" accept="image/*">
        </div>

        <div class="form-group">
          <label class="form-label">Video URL (If video reel)</label>
          <input type="url" name="video_url" class="form-control" placeholder="https://youtube.com/watch?v=...">
        </div>

        <div class="form-group">
          <label class="form-label">Caption / Description</label>
          <input type="text" name="caption" class="form-control" placeholder="Gear used, location...">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Add to Portfolio</button>
      </form>
    </div>

    <!-- Edit Studio Profile Rates -->
    <div class="card">
      <div class="card-header">
        <h3 style="margin: 0;">Studio Settings &amp; Rates</h3>
      </div>
      <form action="<?php echo url('actions/creator-action.php?action=update_profile'); ?>" method="POST">
        <?php echo csrf_input(); ?>

        <div class="form-group">
          <label class="form-label">Studio Name *</label>
          <input type="text" name="studio_name" class="form-control" value="<?php echo e($creator['studio_name']); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Starting Rate (₹ INR) *</label>
          <input type="number" name="starting_price" class="form-control" min="1000" step="500" value="<?php echo (float) $creator['starting_price']; ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Years of Experience *</label>
          <input type="number" name="experience_years" class="form-control" min="1" max="40" value="<?php echo (int) $creator['experience_years']; ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Bio &amp; Shooting Style</label>
          <textarea name="bio" class="form-control" rows="3"><?php echo e($creator['bio']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-secondary btn-block">Save Profile Changes</button>
      </form>
    </div>
  </div>

  <!-- Portfolio Grid -->
  <?php if (!empty($portfolio)): ?>
    <div class="card">
      <div class="card-header">
        <h3 style="margin: 0;">Your Live Portfolio Media (<?php echo count($portfolio); ?> Items)</h3>
      </div>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
        <?php foreach ($portfolio as $p): ?>
          <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; position: relative;">
            <?php if ($p['media_type'] === 'image' && !empty($p['file_path'])): ?>
              <img src="<?php echo url('uploads/portfolio/' . $p['file_path']); ?>" alt="<?php echo e($p['title']); ?>" style="width: 100%; height: 130px; object-fit: cover;">
            <?php else: ?>
              <div style="height: 130px; background: #111; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; text-transform: uppercase;">
                Video Link
              </div>
            <?php endif; ?>
            <div style="padding: 10px; font-size: 0.8125rem;">
              <strong><?php echo e($p['title']); ?></strong>
              <form action="<?php echo url('actions/creator-action.php?action=delete_portfolio'); ?>" method="POST" style="margin-top: 6px;">
                <?php echo csrf_input(); ?>
                <input type="hidden" name="portfolio_id" value="<?php echo $p['id']; ?>">
                <button type="submit" style="background: none; border: none; color: #dc2626; font-size: 0.75rem; cursor: pointer;" onclick="return confirm('Remove portfolio item?')">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
