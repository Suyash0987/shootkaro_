<?php
/**
 * Shoot Karo - Creator Profile Showcase
 */
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/functions.php';

$pdo = get_db_connection();
$profileId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($profileId <= 0) {
    set_flash('error', 'Invalid creator profile.');
    redirect('creators.php');
}

// Fetch Creator Profile
$stmt = $pdo->prepare("
    SELECT 
        cp.*,
        u.name as creator_name,
        u.email,
        u.phone,
        u.status as user_status,
        c.name as city_name,
        a.name as area_name
    FROM creator_profiles cp
    JOIN users u ON cp.user_id = u.id
    LEFT JOIN cities c ON cp.city_id = c.id
    LEFT JOIN areas a ON cp.area_id = a.id
    WHERE cp.id = ?
");
$stmt->execute([$profileId]);
$creator = $stmt->fetch();

if (!$creator || !$creator['is_approved'] || $creator['user_status'] !== 'active') {
    set_flash('error', 'Creator profile is either pending verification or currently inactive.');
    redirect('creators.php');
}

$pageTitle = $creator['studio_name'] ?: $creator['creator_name'];
require_once INCLUDES_PATH . '/header.php';

// Fetch stats
$stats = get_creator_stats($profileId);

// Fetch services offered
$stmtSrv = $pdo->prepare("SELECT * FROM creator_services WHERE creator_id = ? AND is_active = 1");
$stmtSrv->execute([$profileId]);
$services = $stmtSrv->fetchAll();

// Fetch portfolio items
$stmtPort = $pdo->prepare("SELECT * FROM portfolio_items WHERE creator_id = ? ORDER BY sort_order ASC, id DESC");
$stmtPort->execute([$profileId]);
$portfolio = $stmtPort->fetchAll();

// Fetch service areas
$stmtAreas = $pdo->prepare("
    SELECT a.name as area_name 
    FROM creator_service_areas csa 
    JOIN areas a ON csa.area_id = a.id 
    WHERE csa.creator_id = ?
");
$stmtAreas->execute([$profileId]);
$serviceAreas = $stmtAreas->fetchAll();

// Fetch verified reviews
$stmtRev = $pdo->prepare("
    SELECT r.*, u.name as customer_name, b.occasion, b.shoot_date
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    JOIN bookings b ON r.booking_id = b.id
    WHERE r.creator_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
");
$stmtRev->execute([$profileId]);
$reviews = $stmtRev->fetchAll();

$avatarUrl = !empty($creator['profile_image']) ? url('uploads/profiles/' . $creator['profile_image']) : url('assets/logos/logo.svg');
?>

<div class="container section">
  <div style="margin-bottom: 24px;">
    <a href="<?php echo url('creators.php'); ?>" class="btn btn-secondary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      Back to Creators Directory
    </a>
  </div>

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 36px; align-items: start;">
    <!-- Left Column: Details, Portfolio, Services, Reviews -->
    <div>
      <!-- Creator Header Card -->
      <div class="card" style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
        <img src="<?php echo $avatarUrl; ?>" alt="<?php echo e($creator['creator_name']); ?>" style="width: 110px; height: 110px; border-radius: var(--radius-md); object-fit: cover; border: 1px solid var(--border-color); background: var(--bg-tertiary);">
        
        <div style="flex: 1; min-width: 260px;">
          <h1 style="font-size: 2rem; margin-bottom: 6px;"><?php echo e($creator['studio_name'] ?: $creator['creator_name']); ?></h1>
          <div style="font-size: 0.9375rem; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            Based in <?php echo e($creator['area_name'] ?? 'Pune'); ?>, <?php echo e($creator['city_name'] ?? 'Pune'); ?>
          </div>

          <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div class="rating-badge" style="font-size: 0.875rem; padding: 4px 10px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="#ffffff">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
              <?php echo number_format($stats['avg_rating'], 1); ?> / 5.0 (<?php echo $stats['review_count']; ?> Reviews)
            </div>
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-muted);">
              <strong><?php echo $stats['completed_shoots']; ?></strong> Shoots Completed
            </div>
            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-muted);">
              <strong><?php echo (int) $creator['experience_years']; ?></strong> Years Experience
            </div>
          </div>
        </div>
      </div>

      <!-- About & Bio -->
      <div class="card">
        <h3 style="margin-bottom: 12px;">About the Creator</h3>
        <p style="font-size: 1rem; line-height: 1.7; color: var(--text-main); white-space: pre-line;">
          <?php echo e($creator['bio'] ?: 'No biography added yet.'); ?>
        </p>

        <?php if (!empty($creator['portfolio_url']) || !empty($creator['instagram_url'])): ?>
          <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
            <?php if (!empty($creator['portfolio_url'])): ?>
              <a href="<?php echo e($creator['portfolio_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">
                External Portfolio Link
              </a>
            <?php endif; ?>
            <?php if (!empty($creator['instagram_url'])): ?>
              <a href="<?php echo e($creator['instagram_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">
                Instagram Profile
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Services & Equipment Offered -->
      <div class="card">
        <h3 style="margin-bottom: 16px;">Services &amp; Equipment Offered</h3>
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <?php foreach ($services as $s): ?>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 14px; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--bg-secondary);">
              <div>
                <span class="service-tag" style="margin-bottom: 6px; display: inline-block;">
                  <?php echo strtoupper($s['service_type']); ?>
                </span>
                <h4 style="margin: 4px 0;"><?php echo e($s['service_title']); ?></h4>
                <p style="font-size: 0.875rem; margin: 0; color: var(--text-muted);"><?php echo e($s['description']); ?></p>
              </div>
              <div style="text-align: right; white-space: nowrap; margin-left: 16px;">
                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Standard Base</div>
                <strong style="font-size: 1.125rem; color: var(--primary-black);"><?php echo format_inr($s['base_price']); ?></strong>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Service Locations -->
      <?php if (!empty($serviceAreas)): ?>
        <div class="card">
          <h3 style="margin-bottom: 12px;">Service Coverage Areas in Pune</h3>
          <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php foreach ($serviceAreas as $sa): ?>
              <span class="service-tag" style="background: #ffffff;"><?php echo e($sa['area_name']); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Showcase Portfolio -->
      <div class="card">
        <h3 style="margin-bottom: 16px;">Portfolio Showcase</h3>
        <?php if (empty($portfolio)): ?>
          <p style="color: var(--text-muted);">No portfolio media uploaded yet.</p>
        <?php else: ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;">
            <?php foreach ($portfolio as $item): ?>
              <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; background: #000;">
                <?php if ($item['media_type'] === 'image' && !empty($item['file_path'])): ?>
                  <img src="<?php echo url('uploads/portfolio/' . $item['file_path']); ?>" alt="<?php echo e($item['title']); ?>" style="width: 100%; height: 170px; object-fit: cover; display: block;">
                <?php else: ?>
                  <div style="height: 170px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #111; color: #fff; padding: 14px; text-align: center;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span style="font-size: 0.75rem; margin-top: 8px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">Video Reel</span>
                  </div>
                <?php endif; ?>
                <div style="padding: 12px; background: #ffffff;">
                  <h5 style="margin-bottom: 4px; font-size: 0.875rem;"><?php echo e($item['title']); ?></h5>
                  <?php if (!empty($item['caption'])): ?>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0;"><?php echo e($item['caption']); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($item['video_url'])): ?>
                    <a href="<?php echo e($item['video_url']); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; font-size: 0.75rem; font-weight: 600; color: var(--primary-black); margin-top: 6px;">Watch Video &rarr;</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Customer Reviews -->
      <div class="card">
        <h3 style="margin-bottom: 16px;">Verified Customer Reviews</h3>
        <?php if (empty($reviews)): ?>
          <p style="color: var(--text-muted);">No reviews posted for this creator yet.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 18px;">
            <?php foreach ($reviews as $rev): ?>
              <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                  <div>
                    <strong><?php echo e($rev['customer_name']); ?></strong>
                    <span style="font-size: 0.8125rem; color: var(--text-muted); margin-left: 8px;">
                      (<?php echo e($rev['occasion']); ?> Shoot &bull; <?php echo format_date($rev['shoot_date']); ?>)
                    </span>
                  </div>
                  <div class="rating-badge" style="font-size: 0.75rem;">
                    <?php echo (int) $rev['rating']; ?> / 5
                  </div>
                </div>
                <p style="font-size: 0.9375rem; color: var(--text-main); margin: 0; line-height: 1.5;">
                  &ldquo;<?php echo e($rev['comment']); ?>&rdquo;
                </p>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
                  Posted on <?php echo format_date($rev['created_at']); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Column: Booking Sticky Card -->
    <div style="position: sticky; top: 92px;">
      <div class="card" style="box-shadow: var(--shadow-lg); border-color: var(--primary-black);">
        <div style="margin-bottom: 20px;">
          <div style="font-size: 0.8125rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em;">Booking Rates</div>
          <div style="font-size: 2.25rem; font-weight: 800; color: var(--primary-black); line-height: 1.1; margin-top: 4px;">
            <?php echo format_inr($creator['starting_price']); ?>
          </div>
          <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 4px;">Base package starting price</div>
        </div>

        <div style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 16px 0; margin-bottom: 20px; font-size: 0.875rem;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--text-muted);">Advance Required</span>
            <strong><?php echo DEFAULT_ADVANCE_PERCENT; ?>%</strong>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
            <span style="color: var(--text-muted);">Response Time</span>
            <strong>Under 2 Hours</strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--text-muted);">Availability</span>
            <strong style="color: #065f46;">Accepting Bookings</strong>
          </div>
        </div>

        <a href="<?php echo url('booking.php?creator_id=' . $creator['id']); ?>" class="btn btn-primary btn-lg btn-block" style="margin-bottom: 12px;">
          Book Now
        </a>

        <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin: 0;">
          Select equipment, customize deliverables, and pay 20% advance securely.
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
