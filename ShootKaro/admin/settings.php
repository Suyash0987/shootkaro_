<?php
/**
 * Shoot Karo - Admin Settings & Area Configuration
 */
$pageTitle = 'Platform Settings & Configurations';
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

require_role('admin');

$pdo = get_db_connection();

// Fetch All Site Settings
$settingsRows = $pdo->query("SELECT setting_key, setting_value, description FROM site_settings")->fetchAll();
$settings = [];
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch Active Cities & Areas
$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$areas = $pdo->query("SELECT a.*, c.name as city_name FROM areas a JOIN cities c ON a.city_id = c.id ORDER BY a.name ASC")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <div class="section-subtitle"><a href="<?php echo url('admin/index.php'); ?>" style="color: var(--text-muted);">&larr; Admin Overview</a></div>
      <h1>Platform Configuration</h1>
      <p>Configure marketplace commission rates, booking deposits, support details, and service locations.</p>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 28px;">
    <!-- 1. Rates & Contact Settings -->
    <div class="card">
      <div class="card-header">
        <h3 style="margin: 0;">Marketplace Policy &amp; Rates</h3>
      </div>

      <form action="<?php echo url('actions/admin-action.php?action=update_settings'); ?>" method="POST">
        <?php echo csrf_input(); ?>

        <div class="form-group">
          <label class="form-label" for="cfg-commission">Platform Commission Rate (%) *</label>
          <input type="number" step="0.5" min="0" max="50" name="platform_commission_percent" id="cfg-commission" class="form-control" value="<?php echo e($settings['platform_commission_percent'] ?? '10.00'); ?>" required>
          <div class="form-hint">Deducted from creator payouts on completed bookings.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cfg-advance">Default Advance Deposit Required (%) *</label>
          <input type="number" step="1" min="5" max="100" name="default_advance_percent" id="cfg-advance" class="form-control" value="<?php echo e($settings['default_advance_percent'] ?? '20.00'); ?>" required>
          <div class="form-hint">Mandatory upfront deposit clients pay to confirm bookings.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cfg-phone">Official Support Phone Number *</label>
          <input type="text" name="support_phone" id="cfg-phone" class="form-control" value="<?php echo e($settings['support_phone'] ?? '+91 98765 43210'); ?>" required>
          <div class="form-hint">Displayed in footer and support desk.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cfg-email">Official Support Email Address *</label>
          <input type="email" name="support_email" id="cfg-email" class="form-control" value="<?php echo e($settings['support_email'] ?? 'support@shootkaro.local'); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="cfg-name">Platform Brand Name *</label>
          <input type="text" name="site_name" id="cfg-name" class="form-control" value="<?php echo e($settings['site_name'] ?? 'SHOOT KARO'); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Save Global Settings</button>
      </form>
    </div>

    <!-- 2. Areas Management -->
    <div>
      <!-- Add New Area -->
      <div class="card">
        <div class="card-header">
          <h3 style="margin: 0;">Add Supported Location / Area</h3>
        </div>

        <form action="<?php echo url('actions/admin-action.php?action=add_area'); ?>" method="POST">
          <?php echo csrf_input(); ?>

          <div class="form-group">
            <label class="form-label" for="area-city">City *</label>
            <select name="city_id" id="area-city" class="form-control" required>
              <?php foreach ($cities as $ct): ?>
                <option value="<?php echo $ct['id']; ?>"><?php echo e($ct['name']); ?> (<?php echo e($ct['state']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="area-name">Area / Neighborhood Name *</label>
            <input type="text" name="area_name" id="area-name" class="form-control" placeholder="e.g. Viman Nagar, Koregaon Park" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="area-zip">Postal Code</label>
            <input type="text" name="postal_code" id="area-zip" class="form-control" placeholder="e.g. 411014">
          </div>

          <button type="submit" class="btn btn-secondary btn-block">Add New Area</button>
        </form>
      </div>

      <!-- Current Areas in Pune -->
      <div class="card">
        <div class="card-header">
          <h3 style="margin: 0;">Currently Active Areas (<?php echo count($areas); ?>)</h3>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
          <?php foreach ($areas as $a): ?>
            <span class="service-tag" style="background: var(--bg-secondary);">
              <?php echo e($a['name']); ?> <?php echo !empty($a['postal_code']) ? '(' . e($a['postal_code']) . ')' : ''; ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
