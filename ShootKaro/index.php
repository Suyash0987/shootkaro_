<?php
/**
 * Shoot Karo - Homepage
 */
$pageTitle = 'Find Your Perfect Cinematic Creator';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/header.php';

$pdo = get_db_connection();

// 1. Fetch Active Cities
$cities = $pdo->query("SELECT id, name FROM cities WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// 2. Fetch Active Areas
$areas = $pdo->query("SELECT id, city_id, name FROM areas WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// 3. Search & Filter Parameters
$cityFilter = isset($_GET['city_id']) ? (int) $_GET['city_id'] : 0;
$areaFilter = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;
$sortBy     = isset($_GET['sort']) ? trim($_GET['sort']) : 'rating_desc';

// 4. Query Approved & Available Creators
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

// Pre-fetch services for all returned creators
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

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-bg-overlay"></div>
  <div class="container hero-content">
    <div class="hero-badge">On-Demand Photography &amp; Cinematography</div>
    <h1 class="hero-title">Find Your Perfect Cinematic Creator</h1>
    <p class="hero-desc">Discover and book vetted local photographers, videographers, viral mobile reel makers, and certified drone pilots for your weddings, parties, products, and brand shoots.</p>
  </div>
</section>

<!-- Location Search Bar - Uber/Ola inspired -->
<div class="container">
  <div class="search-bar-wrapper">
    <form action="<?php echo url('index.php'); ?>" method="GET" class="search-form">
      <div class="search-field">
        <label for="search-city">Select City</label>
        <select name="city_id" id="search-city" class="search-select">
          <option value="">All Cities</option>
          <?php foreach ($cities as $ct): ?>
            <option value="<?php echo $ct['id']; ?>" <?php echo ($cityFilter == $ct['id']) ? 'selected' : ''; ?>>
              <?php echo e($ct['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="search-field">
        <label for="search-area">Select Area</label>
        <select name="area_id" id="search-area" class="search-select">
          <option value="">All Areas</option>
          <?php foreach ($areas as $ar): ?>
            <option value="<?php echo $ar['id']; ?>" data-city-id="<?php echo $ar['city_id']; ?>" <?php echo ($areaFilter == $ar['id']) ? 'selected' : ''; ?>>
              <?php echo e($ar['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="height: 52px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        Search Creators
      </button>
    </form>
  </div>
</div>

<!-- Popular Creators Section -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <div>
        <div class="section-subtitle">Verified Professionals</div>
        <h2>Popular Creators</h2>
      </div>

      <!-- Sorting Controls -->
      <form action="<?php echo url('index.php'); ?>" method="GET" style="display: flex; align-items: center; gap: 10px;">
        <?php if ($cityFilter): ?><input type="hidden" name="city_id" value="<?php echo $cityFilter; ?>"><?php endif; ?>
        <?php if ($areaFilter): ?><input type="hidden" name="area_id" value="<?php echo $areaFilter; ?>"><?php endif; ?>
        <label for="sort-select" style="font-size: 0.875rem; font-weight: 600; color: var(--text-muted);">Sort By:</label>
        <select name="sort" id="sort-select" class="form-control" style="width: auto; padding: 6px 12px;" onchange="this.form.submit()">
          <option value="rating_desc" <?php echo ($sortBy === 'rating_desc') ? 'selected' : ''; ?>>Highest Rated</option>
          <option value="shoots_desc" <?php echo ($sortBy === 'shoots_desc') ? 'selected' : ''; ?>>Most Shoots</option>
          <option value="price_asc" <?php echo ($sortBy === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo ($sortBy === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
        </select>
      </form>
    </div>

    <?php if (empty($creators)): ?>
      <div style="text-align: center; padding: 60px 20px; border: 1px dashed var(--border-color); border-radius: var(--radius-lg); background: var(--bg-secondary);">
        <h3 style="margin-bottom: 8px;">No Creators Found in this Location</h3>
        <p style="margin-bottom: 20px;">Try searching for another area or view all available creators across Pune.</p>
        <a href="<?php echo url('index.php'); ?>" class="btn btn-secondary">Clear Filters</a>
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
                <strong><?php echo (int) $cr['completed_shoots']; ?></strong> Shoots Completed
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
                <a href="<?php echo url('creator-profile.php?id=' . $cr['profile_id']); ?>" class="btn btn-secondary btn-sm" title="View Portfolio &amp; Reviews">View Profile</a>
                <a href="<?php echo url('booking.php?creator_id=' . $cr['profile_id']); ?>" class="btn btn-primary btn-sm">Book Now</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- How It Works Section -->
<section class="section" style="background-color: var(--bg-secondary); border-top: 1px solid var(--border-color);">
  <div class="container">
    <div style="text-align: center; max-width: 650px; margin: 0 auto 50px;">
      <div class="section-subtitle">Seamless Booking Flow</div>
      <h2>How Shoot Karo Works</h2>
      <p>Booking a trusted photographer or aerial videographer is as frictionless as calling a cab.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
      <div class="card" style="margin: 0; text-align: left;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--primary-black); margin-bottom: 12px;">01</div>
        <h4 style="margin-bottom: 8px;">Discover &amp; Match</h4>
        <p style="font-size: 0.9375rem;">Browse vetted creators in Pune filtered by city, area, and equipment needs (Camera, Mobile Reels, or 4K Drone).</p>
      </div>

      <div class="card" style="margin: 0; text-align: left;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--primary-black); margin-bottom: 12px;">02</div>
        <h4 style="margin-bottom: 8px;">Select Equipment</h4>
        <p style="font-size: 0.9375rem;">Customize your package in our 5-step wizard. Choose exact deliverables and review transparent estimates.</p>
      </div>

      <div class="card" style="margin: 0; text-align: left;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--primary-black); margin-bottom: 12px;">03</div>
        <h4 style="margin-bottom: 8px;">Secure with 20% Advance</h4>
        <p style="font-size: 0.9375rem;">Lock in your creator's date with a safe advance deposit via UPI, Card, or Pay Later options.</p>
      </div>

      <div class="card" style="margin: 0; text-align: left;">
        <div style="font-size: 2rem; font-weight: 800; color: var(--primary-black); margin-bottom: 12px;">04</div>
        <h4 style="margin-bottom: 8px;">Shoot &amp; Deliver</h4>
        <p style="font-size: 0.9375rem;">Enjoy your event. Access and download high-resolution photos and color-graded videos straight from your dashboard.</p>
      </div>
    </div>
  </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
