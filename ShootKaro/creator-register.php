<?php
/**
 * Shoot Karo - Creator Registration Onboarding
 */
$pageTitle = 'Register as Creator';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

if (is_logged_in() && !is_admin()) {
    if (is_creator()) {
        redirect('dashboard/creator.php');
    }
}

$pdo = get_db_connection();
$cities = $pdo->query("SELECT id, name FROM cities WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$areas = $pdo->query("SELECT id, city_id, name FROM areas WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section" style="max-width: 720px;">
  <div class="card" style="box-shadow: var(--shadow-lg); padding: 40px 36px;">
    <div style="text-align: center; margin-bottom: 32px;">
      <img src="<?php echo url('assets/logos/logo.svg'); ?>" alt="Shoot Karo Logo" style="width: 50px; height: 50px; margin-bottom: 12px;">
      <h1 style="font-size: 2rem; margin-bottom: 8px;">Join the Shoot Karo Creator Network</h1>
      <p style="font-size: 0.9375rem;">Get discovered by local clients looking for top-tier photographers, videographers, reel makers, and certified drone pilots.</p>
    </div>

    <form action="<?php echo url('actions/creator-register-action.php'); ?>" method="POST" enctype="multipart/form-data">
      <?php echo csrf_input(); ?>

      <!-- 1. Personal & Contact Information -->
      <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-bottom: 18px; font-size: 1.125rem;">
        1. Creator &amp; Studio Identity
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="cr-name">Your Full Name *</label>
          <input type="text" name="name" id="cr-name" class="form-control" placeholder="e.g. Vikram Joshi" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="cr-studio">Studio / Brand Name *</label>
          <input type="text" name="studio_name" id="cr-studio" class="form-control" placeholder="e.g. Reel Craft Studios" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="cr-email">Email Address *</label>
          <input type="email" name="email" id="cr-email" class="form-control" placeholder="creator@example.com" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="cr-phone">Phone Number *</label>
          <input type="tel" name="phone" id="cr-phone" class="form-control" placeholder="10-digit phone number" pattern="[0-9]{10}" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="cr-password">Account Password *</label>
        <input type="password" name="password" id="cr-password" class="form-control" placeholder="At least 6 characters" minlength="6" required>
      </div>

      <!-- 2. Location & Base -->
      <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-top: 28px; margin-bottom: 18px; font-size: 1.125rem;">
        2. Location &amp; Primary Base
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="search-city">City *</label>
          <select name="city_id" id="search-city" class="form-control" required>
            <option value="">Select City</option>
            <?php foreach ($cities as $ct): ?>
              <option value="<?php echo $ct['id']; ?>"><?php echo e($ct['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="search-area">Primary Area *</label>
          <select name="area_id" id="search-area" class="form-control" required>
            <option value="">Select Area</option>
            <?php foreach ($areas as $ar): ?>
              <option value="<?php echo $ar['id']; ?>" data-city-id="<?php echo $ar['city_id']; ?>">
                <?php echo e($ar['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- 3. Shooting Services Provided -->
      <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-top: 28px; margin-bottom: 18px; font-size: 1.125rem;">
        3. Supported Shooting Services (Select All That Apply) *
      </h3>

      <div class="choice-card-grid" style="margin-bottom: 24px;">
        <label class="choice-card">
          <input type="checkbox" name="services[]" value="camera" checked>
          <div class="choice-title">Professional Camera</div>
          <div class="choice-desc">DSLR / Mirrorless full-frame camera gear for candid events, portraits, and weddings.</div>
        </label>

        <label class="choice-card">
          <input type="checkbox" name="services[]" value="mobile">
          <div class="choice-title">Mobile Photography / Reels</div>
          <div class="choice-desc">High-speed iPhone 4K ProRes video, trending reels, gimbal work, and rapid delivery.</div>
        </label>

        <label class="choice-card">
          <input type="checkbox" name="services[]" value="drone">
          <div class="choice-title">Drone Shooting</div>
          <div class="choice-desc">Certified aerial drone cinematography, 4K master shots, real estate and venue sweeps.</div>
        </label>
      </div>

      <!-- 4. Pricing & Experience -->
      <h3 style="border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin-top: 28px; margin-bottom: 18px; font-size: 1.125rem;">
        4. Rates &amp; Professional Background
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="cr-starting-price">Starting Shoot Price (₹ INR) *</label>
          <input type="number" name="starting_price" id="cr-starting-price" class="form-control" min="1000" step="500" value="3500" required>
          <div class="form-hint">Minimum package rate displayed to clients.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cr-experience">Years of Experience *</label>
          <input type="number" name="experience_years" id="cr-experience" class="form-control" min="1" max="40" value="3" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="cr-bio">Short Professional Bio *</label>
        <textarea name="bio" id="cr-bio" class="form-control" rows="3" placeholder="Tell clients about your shooting style, key gear, and highlights..." required></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="cr-portfolio">Portfolio Website / Cloud Link</label>
          <input type="url" name="portfolio_url" id="cr-portfolio" class="form-control" placeholder="https://yourportfolio.com">
        </div>

        <div class="form-group">
          <label class="form-label" for="cr-instagram">Instagram Profile Handle / Link</label>
          <input type="text" name="instagram_url" id="cr-instagram" class="form-control" placeholder="https://instagram.com/yourhandle">
        </div>
      </div>

      <!-- 5. Profile Picture Upload -->
      <div class="form-group">
        <label class="form-label" for="cr-photo">Profile Photo or Studio Logo (JPG, PNG, WEBP)</label>
        <input type="file" name="profile_image" id="cr-photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        <div class="form-hint">Maximum upload size: 10MB.</div>
      </div>

      <div style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px; margin: 24px 0; font-size: 0.8125rem; color: var(--text-muted);">
        <strong>Verification Note:</strong> Newly registered creator profiles remain in <strong>Pending</strong> status until verified and approved by the Shoot Karo administrator. You will be able to log in once your application is approved.
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Creator Application</button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.875rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 20px;">
      Already a registered creator? <a href="<?php echo url('login.php'); ?>" style="font-weight: 700; color: var(--primary-black);">Sign In Here</a>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
