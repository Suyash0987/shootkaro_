<?php
/**
 * Shoot Karo - Creator Discovery Directory
 */
$pageTitle = 'Find Professional Photographers, Videographers & Drone Pilots';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/header.php';

$pdo = get_db_connection();

// Filter inputs
$cityFilter    = isset($_GET['city_id']) ? (int) $_GET['city_id'] : 0;
$areaFilter    = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;
$serviceFilter = isset($_GET['service']) ? trim($_GET['service']) : '';
$sortBy        = isset($_GET['sort']) ? trim($_GET['sort']) : 'rating_desc';
$searchQuery   = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. Fetch Active Cities & Areas
$cities = $pdo->query("SELECT id, name FROM cities WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$areas  = $pdo->query("SELECT id, city_id, name FROM areas WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// 2. Query Active & Approved Creators
$sql = "
    SELECT 
        cp.id as profile_id,
        cp.user_id,
        cp.studio_name,
        cp.bio,
        cp.profile_image,
        cp.starting_price,
        cp.experience_years,
        u.name as creator_name,
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
    LEFT JOIN creator_service_areas csa ON cp.id = csa.creator_id
    LEFT JOIN creator_services cs ON cp.id = cs.creator_id
    WHERE cp.is_approved = 1 
      AND cp.is_available = 1
      AND u.status = 'active'
";

$params = [];

if ($cityFilter > 0) {
    $sql .= " AND cp.city_id = ?";
    $params[] = $cityFilter;
}

if ($areaFilter > 0) {
    $sql .= " AND (cp.area_id = ? OR csa.area_id = ?)";
    $params[] = $areaFilter;
    $params[] = $areaFilter;
}

if (!empty($serviceFilter) && in_array($serviceFilter, ['camera', 'mobile', 'drone'])) {
    $sql .= " AND cs.service_type = ? AND cs.is_active = 1";
    $params[] = $serviceFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (cp.studio_name LIKE ? OR u.name LIKE ? OR cp.bio LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " GROUP BY cp.id, cp.user_id, cp.studio_name, cp.bio, cp.profile_image, cp.starting_price, cp.experience_years, u.name, c.name, a.name";

// Sorting
switch ($sortBy) {
    case 'price_asc':
        $sql .= " ORDER BY cp.starting_price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY cp.starting_price DESC";
        break;
    case 'shoots_desc':
        $sql .= " ORDER BY completed_shoots DESC";
        break;
    case 'rating_desc':
    default:
        $sql .= " ORDER BY avg_rating DESC, completed_shoots DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$creators = $stmt->fetchAll();

// Pre-fetch services
$creatorServicesMap = [];
if (!empty($creators)) {
    $profileIds = array_column($creators, 'profile_id');
    $placeholders = implode(',', array_fill(0, count($profileIds), '?'));
    $srvStmt = $pdo->prepare("SELECT creator_id, service_type, service_title, base_price FROM creator_services WHERE creator_id IN ($placeholders) AND is_active = 1");
    $srvStmt->execute($profileIds);
    while ($srv = $srvStmt->fetch()) {
        $creatorServicesMap[$srv['creator_id']][] = $srv;
    }
}
?>

<div class="container section">
  <div style="margin-bottom: 30px;">
    <div class="section-subtitle">Directory</div>
    <h1>Find Your Creator</h1>
    <p>Browse verified photography, videography, mobile reel, and drone specialists in your neighborhood.</p>
  </div>

  <!-- Search & Filter Controls -->
  <div class="card" style="margin-bottom: 36px; padding: 20px;">
    <form action="<?php echo url('creators.php'); ?>" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 16px; align-items: flex-end;">
      <div>
        <label class="form-label" for="search-city">City</label>
        <select name="city_id" id="search-city" class="form-control">
          <option value="">All Cities</option>
          <?php foreach ($cities as $ct): ?>
            <option value="<?php echo $ct['id']; ?>" <?php echo ($cityFilter == $ct['id']) ? 'selected' : ''; ?>>
              <?php echo e($ct['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label" for="search-area">Area</label>
        <select name="area_id" id="search-area" class="form-control">
          <option value="">All Areas</option>
          <?php foreach ($areas as $ar): ?>
            <option value="<?php echo $ar['id']; ?>" data-city-id="<?php echo $ar['city_id']; ?>" <?php echo ($areaFilter == $ar['id']) ? 'selected' : ''; ?>>
              <?php echo e($ar['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="form-label" for="filter-service">Service Needed</label>
        <select name="service" id="filter-service" class="form-control">
          <option value="">All Services</option>
          <option value="camera" <?php echo ($serviceFilter === 'camera') ? 'selected' : ''; ?>>Professional Camera</option>
          <option value="mobile" <?php echo ($serviceFilter === 'mobile') ? 'selected' : ''; ?>>Mobile Reels &amp; Highlights</option>
          <option value="drone" <?php echo ($serviceFilter === 'drone') ? 'selected' : ''; ?>>4K Drone Operator</option>
        </select>
      </div>

      <div>
        <label class="form-label" for="filter-sort">Sort By</label>
        <select name="sort" id="filter-sort" class="form-control">
          <option value="rating_desc" <?php echo ($sortBy === 'rating_desc') ? 'selected' : ''; ?>>Highest Rated</option>
          <option value="shoots_desc" <?php echo ($sortBy === 'shoots_desc') ? 'selected' : ''; ?>>Most Shoots</option>
          <option value="price_asc" <?php echo ($sortBy === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo ($sortBy === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
        </select>
      </div>

      <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="height: 42px;">Apply Filter</button>
        <a href="<?php echo url('creators.php'); ?>" class="btn btn-secondary" style="height: 42px;">Reset</a>
      </div>
    </form>
  </div>

  <!-- Results Count -->
  <div style="margin-bottom: 24px; font-size: 0.9375rem; color: var(--text-muted);">
    Showing <strong><?php echo count($creators); ?></strong> active verified creators
  </div>

  <?php if (empty($creators)): ?>
    <div style="text-align: center; padding: 60px 20px; border: 1px dashed var(--border-color); border-radius: var(--radius-lg); background: var(--bg-secondary);">
      <h3 style="margin-bottom: 8px;">No matching creators found</h3>
      <p style="margin-bottom: 20px;">Try adjusting your city, area, or service requirements.</p>
      <a href="<?php echo url('creators.php'); ?>" class="btn btn-primary">View All Creators</a>
    </div>
  <?php else: ?>
    <div class="creator-grid">
      <?php foreach ($creators as $cr): ?>
        <?php 
          $services = $creatorServicesMap[$cr['profile_id']] ?? [];
          $avatarUrl = !empty($cr['profile_image']) ? url('uploads/profiles/' . $cr['profile_image']) : url('assets/logos/logo.svg');
        ?>
        <div class="creator-card">
          <div class="creator-card-header">
            <img src="<?php echo $avatarUrl; ?>" alt="<?php echo e($cr['creator_name']); ?>" class="creator-avatar">
            <div class="creator-info">
              <h3 class="creator-name"><?php echo e($cr['studio_name'] ?: $cr['creator_name']); ?></h3>
              <div class="creator-location">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"></path>
                  <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <?php echo e($cr['area_name'] ?? 'Pune'); ?>, <?php echo e($cr['city_name'] ?? 'Pune'); ?>
              </div>
            </div>
          </div>

          <div class="creator-meta-bar">
            <div class="rating-badge" title="Average Rating">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="#ffffff">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
              <?php echo number_format($cr['avg_rating'], 1); ?>
            </div>
            <div class="shoots-count">
              <strong><?php echo (int) $cr['completed_shoots']; ?></strong> Shoots
            </div>
            <div class="shoots-count" style="margin-left: auto;">
              <?php echo (int) $cr['experience_years']; ?> Yrs Exp
            </div>
          </div>

          <!-- Services Offered Badges -->
          <div class="creator-services-tags">
            <?php foreach ($services as $srv): ?>
              <span class="service-tag">
                <?php 
                  if ($srv['service_type'] === 'camera') echo 'Camera';
                  elseif ($srv['service_type'] === 'mobile') echo 'Mobile / Reels';
                  elseif ($srv['service_type'] === 'drone') echo 'Drone';
                ?>
              </span>
            <?php endforeach; ?>
          </div>

          <div class="creator-card-footer">
            <div class="price-box">
              <span class="price-label">Starts At</span>
              <span class="price-value"><?php echo format_inr($cr['starting_price']); ?></span>
            </div>

            <div class="creator-actions">
              <a href="<?php echo url('creator-profile.php?id=' . $cr['profile_id']); ?>" class="btn btn-secondary btn-sm">Profile</a>
              <a href="<?php echo url('booking.php?creator_id=' . $cr['profile_id']); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
