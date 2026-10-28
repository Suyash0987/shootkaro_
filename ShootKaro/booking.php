<?php
/**
 * Shoot Karo - 5-Step Interactive Booking Engine
 */
$pageTitle = 'Book Creator';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

$pdo = get_db_connection();
$creatorId = isset($_GET['creator_id']) ? (int) $_GET['creator_id'] : 0;

if ($creatorId <= 0) {
    set_flash('error', 'Please select a creator to initiate a booking.');
    redirect('creators.php');
}

// Fetch Creator Profile
$stmt = $pdo->prepare("
    SELECT 
        cp.*,
        u.name as creator_name,
        u.email as creator_email,
        u.phone as creator_phone,
        c.name as city_name,
        a.name as area_name
    FROM creator_profiles cp
    JOIN users u ON cp.user_id = u.id
    LEFT JOIN cities c ON cp.city_id = c.id
    LEFT JOIN areas a ON cp.area_id = a.id
    WHERE cp.id = ? AND cp.is_approved = 1 AND cp.is_available = 1 AND u.status = 'active'
");
$stmt->execute([$creatorId]);
$creator = $stmt->fetch();

if (!$creator) {
    set_flash('error', 'Selected creator is currently not available for new bookings.');
    redirect('creators.php');
}

// Fetch Active Services Offered by THIS Creator (Crucial requirement: show ONLY offered services)
$stmtSrv = $pdo->prepare("SELECT * FROM creator_services WHERE creator_id = ? AND is_active = 1");
$stmtSrv->execute([$creatorId]);
$offeredServices = $stmtSrv->fetchAll();

if (empty($offeredServices)) {
    set_flash('error', 'This creator has not configured active shoot services yet.');
    redirect('creators.php');
}

// Map of services offered
$servicesByType = [];
foreach ($offeredServices as $srv) {
    $servicesByType[$srv['service_type']] = $srv;
}

// Fetch cities and areas for venue selection
$cities = $pdo->query("SELECT id, name FROM cities WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$areas  = $pdo->query("SELECT id, city_id, name FROM areas WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Current User Details for Prefill
$user = current_user();
$prefillName  = $user ? $user['name'] : '';
$prefillEmail = $user ? $user['email'] : '';
$prefillPhone = $user ? $user['phone'] : '';

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section" style="max-width: 900px;">
  <!-- Creator Micro-Banner -->
  <div style="display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 30px; flex-wrap: wrap; gap: 14px;">
    <div style="display: flex; align-items: center; gap: 16px;">
      <img src="<?php echo !empty($creator['profile_image']) ? url('uploads/profiles/' . $creator['profile_image']) : url('assets/logos/logo.svg'); ?>" alt="<?php echo e($creator['creator_name']); ?>" style="width: 54px; height: 54px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border-color);">
      <div>
        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">Booking Shoot With</div>
        <h3 style="margin: 0; font-size: 1.25rem;"><?php echo e($creator['studio_name'] ?: $creator['creator_name']); ?></h3>
        <div style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo e($creator['area_name'] ?? 'Pune'); ?>, <?php echo e($creator['city_name'] ?? 'Pune'); ?></div>
      </div>
    </div>
    <div style="text-align: right;">
      <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Base Starting Rate</div>
      <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-black);"><?php echo format_inr($creator['starting_price']); ?></div>
    </div>
  </div>

  <!-- Stepper Indicator -->
  <div class="stepper-header">
    <div class="step-item active" data-step="1">
      <div class="step-number">1</div>
      <span>Event &amp; Venue</span>
    </div>
    <div class="step-item" data-step="2">
      <div class="step-number">2</div>
      <span>Equipment</span>
    </div>
    <div class="step-item" data-step="3">
      <div class="step-number">3</div>
      <span>Deliverables</span>
    </div>
    <div class="step-item" data-step="4">
      <div class="step-number">4</div>
      <span>Review &amp; Cost</span>
    </div>
    <div class="step-item" data-step="5">
      <div class="step-number">5</div>
      <span>Advance Payment</span>
    </div>
  </div>

  <!-- Booking Form Container -->
  <form id="booking-multistep-form" action="<?php echo url('actions/booking-action.php'); ?>" method="POST" class="card" style="padding: 36px 32px; box-shadow: var(--shadow-md);">
    <?php echo csrf_input(); ?>
    <input type="hidden" name="creator_id" value="<?php echo $creator['id']; ?>">
    <input type="hidden" id="calc-starting-price" value="<?php echo $creator['starting_price']; ?>">
    <input type="hidden" id="calc-advance-percent" value="<?php echo DEFAULT_ADVANCE_PERCENT; ?>">

    <!-- Calculated Financial Hidden Inputs -->
    <input type="hidden" name="calculated_equipment_charges" id="input_calculated_equipment_charges" value="0.00">
    <input type="hidden" name="calculated_duration_charges" id="input_calculated_duration_charges" value="0.00">
    <input type="hidden" name="calculated_total" id="input_calculated_total" value="<?php echo $creator['starting_price']; ?>">
    <input type="hidden" name="calculated_advance" id="input_calculated_advance" value="<?php echo round($creator['starting_price'] * (DEFAULT_ADVANCE_PERCENT / 100), 2); ?>">
    <input type="hidden" name="calculated_balance" id="input_calculated_balance" value="<?php echo round($creator['starting_price'] * (1 - (DEFAULT_ADVANCE_PERCENT / 100)), 2); ?>">

    <!-- ========================================================
         STEP 1: Customer Details & Event Location
         ======================================================== -->
    <div class="booking-step-panel" data-step="1">
      <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Step 1: Event &amp; Venue Details</h2>
      <p style="margin-bottom: 24px;">Tell us about your event, location, and preferred shoot timings.</p>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="bk-name">Your Full Name *</label>
          <input type="text" name="customer_name" id="bk-name" class="form-control" value="<?php echo e($prefillName); ?>" placeholder="e.g. Rohan Patil" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="bk-phone">Contact Phone *</label>
          <input type="tel" name="customer_phone" id="bk-phone" class="form-control" value="<?php echo e($prefillPhone); ?>" placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="bk-email">Email Address *</label>
        <input type="email" name="customer_email" id="bk-email" class="form-control" value="<?php echo e($prefillEmail); ?>" placeholder="name@example.com" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="search-city">Shoot City *</label>
          <select name="city_id" id="search-city" class="form-control" required>
            <?php foreach ($cities as $ct): ?>
              <option value="<?php echo $ct['id']; ?>" <?php echo ($creator['city_id'] == $ct['id']) ? 'selected' : ''; ?>>
                <?php echo e($ct['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="search-area">Shoot Area *</label>
          <select name="area_id" id="search-area" class="form-control" required>
            <?php foreach ($areas as $ar): ?>
              <option value="<?php echo $ar['id']; ?>" data-city-id="<?php echo $ar['city_id']; ?>" <?php echo ($creator['area_id'] == $ar['id']) ? 'selected' : ''; ?>>
                <?php echo e($ar['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="bk-venue">Exact Venue Address *</label>
        <input type="text" name="shoot_venue" id="bk-venue" class="form-control" placeholder="e.g. Oxford Golf Resort, Lavale Rd / Home Address" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="bk-date">Shoot Date *</label>
          <input type="date" name="shoot_date" id="bk-date" class="form-control" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="bk-time">Preferred Start Time *</label>
          <input type="time" name="shoot_time" id="bk-time" class="form-control" value="16:00" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="bk-occasion">Shoot Occasion *</label>
          <select name="occasion" id="bk-occasion" class="form-control" required>
            <option value="Pre-Wedding">Pre-Wedding Shoot</option>
            <option value="Wedding">Wedding / Engagement</option>
            <option value="Birthday">Birthday / Anniversary</option>
            <option value="Corporate Event">Corporate Event / Conference</option>
            <option value="Reels / Social">Reels &amp; Social Influencer</option>
            <option value="Product / Commercial">Product / Commercial</option>
            <option value="Maternity / Baby">Maternity / Baby Shoot</option>
            <option value="Other">Other Custom Shoot</option>
          </select>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
        <button type="button" class="btn btn-primary btn-lg btn-step-next">
          Continue to Step 2: Select Equipment &rarr;
        </button>
      </div>
    </div>

    <!-- ========================================================
         STEP 2: Select Shooting Equipment
         Only shows equipment offered by THIS creator
         ======================================================== -->
    <div class="booking-step-panel" data-step="2" style="display: none;">
      <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Step 2: Select Shooting Equipment</h2>
      <p style="margin-bottom: 24px;"><strong>What type of shoot do you need?</strong> Select one or more combinations offered by <?php echo e($creator['studio_name'] ?: $creator['creator_name']); ?>.</p>

      <div class="choice-card-grid">
        <!-- Professional Camera -->
        <?php if (isset($servicesByType['camera'])): ?>
          <?php $srv = $servicesByType['camera']; ?>
          <label class="choice-card">
            <input type="checkbox" name="selected_services[]" value="camera" data-price="<?php echo $srv['base_price']; ?>" data-title="Professional Camera" checked>
            <div class="choice-title">Professional Camera</div>
            <div class="choice-desc"><?php echo e($srv['description'] ?: 'High-end DSLR/Mirrorless camera for sharp 4K candid moments and portraiture.'); ?></div>
            <div class="choice-price"><?php echo format_inr($srv['base_price']); ?></div>
          </label>
        <?php endif; ?>

        <!-- Mobile Photography / Reels -->
        <?php if (isset($servicesByType['mobile'])): ?>
          <?php $srv = $servicesByType['mobile']; ?>
          <label class="choice-card">
            <input type="checkbox" name="selected_services[]" value="mobile" data-price="<?php echo $srv['base_price']; ?>" data-title="Mobile Reels &amp; Highlights">
            <div class="choice-title">Mobile Photography / Reels</div>
            <div class="choice-desc"><?php echo e($srv['description'] ?: 'iPhone 4K 60fps vertical reels, gimbal stabilization, and trending transitions.'); ?></div>
            <div class="choice-price"><?php echo format_inr($srv['base_price']); ?></div>
          </label>
        <?php endif; ?>

        <!-- Drone Shooting -->
        <?php if (isset($servicesByType['drone'])): ?>
          <?php $srv = $servicesByType['drone']; ?>
          <label class="choice-card">
            <input type="checkbox" name="selected_services[]" value="drone" data-price="<?php echo $srv['base_price']; ?>" data-title="4K Drone Operator">
            <div class="choice-title">Drone Shooting</div>
            <div class="choice-desc"><?php echo e($srv['description'] ?: 'Certified drone pilot with 4K aerial fly-bys, sweeping venue and landscape views.'); ?></div>
            <div class="choice-price"><?php echo format_inr($srv['base_price']); ?></div>
          </label>
        <?php endif; ?>
      </div>

      <div style="background-color: var(--bg-secondary); border-radius: var(--radius-sm); padding: 12px 16px; margin-top: 20px; font-size: 0.8125rem; color: var(--text-muted);">
        Note: You can combine multiple equipment types (e.g. Camera + Drone). Additional equipment is automatically bundled with a 25% discount.
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 28px;">
        <button type="button" class="btn btn-secondary btn-step-prev">&larr; Back</button>
        <button type="button" class="btn btn-primary btn-step-next">Continue to Step 3: Deliverables &rarr;</button>
      </div>
    </div>

    <!-- ========================================================
         STEP 3: Select Deliverable Type
         ======================================================== -->
    <div class="booking-step-panel" data-step="3" style="display: none;">
      <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Step 3: Select Deliverable Type</h2>
      <p style="margin-bottom: 24px;"><strong>What do you want the creator to capture?</strong> Specify photos, videos, and custom post-production requirements.</p>

      <div class="choice-card-grid" style="margin-bottom: 24px;">
        <label class="choice-card">
          <input type="radio" name="deliverable_type" value="both" checked>
          <div class="choice-title">Photos + Video</div>
          <div class="choice-desc">Comprehensive coverage: full photo album plus edited highlight cinematic video.</div>
        </label>

        <label class="choice-card">
          <input type="radio" name="deliverable_type" value="photos">
          <div class="choice-title">Photos Only</div>
          <div class="choice-desc">High-resolution color-graded photo album and raw previews.</div>
        </label>

        <label class="choice-card">
          <input type="radio" name="deliverable_type" value="video">
          <div class="choice-title">Video Only</div>
          <div class="choice-desc">Cinematic 4K teaser, highlight reel, and full-length edited event reel.</div>
        </label>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="booking_duration_hours">Expected Shoot Duration (Hours) *</label>
          <select name="duration_hours" id="booking_duration_hours" class="form-control" required>
            <option value="2" selected>2 Hours (Standard Session)</option>
            <option value="3">3 Hours (+35% extra rate)</option>
            <option value="4">4 Hours (Half Day)</option>
            <option value="6">6 Hours (Extended)</option>
            <option value="8">8 Hours (Full Day)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="bk-photo-count">Estimated Photo Count (If applicable)</label>
          <input type="text" name="photo_count_estimate" id="bk-photo-count" class="form-control" value="50-100 Color Graded Photos">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="bk-video-duration">Video Duration Preference (If applicable)</label>
        <input type="text" name="video_duration_estimate" id="bk-video-duration" class="form-control" value="1-3 Minutes Highlight Reel">
      </div>

      <div class="form-group">
        <label class="form-label" for="bk-editing">Editing &amp; Color Grading Preferences</label>
        <textarea name="editing_requirements" id="bk-editing" class="form-control" rows="2" placeholder="e.g. Natural skin tones, moody cinematic grade, specific music genre for reel..."></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="bk-instructions">Special Instructions for Creator</label>
        <textarea name="special_instructions" id="bk-instructions" class="form-control" rows="2" placeholder="e.g. Bring drone spare batteries, focus on candid guest moments..."></textarea>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 28px;">
        <button type="button" class="btn btn-secondary btn-step-prev">&larr; Back</button>
        <button type="button" class="btn btn-primary btn-step-next">Continue to Step 4: Review Cost &rarr;</button>
      </div>
    </div>

    <!-- ========================================================
         STEP 4: Review Booking & Financial Breakdown
         ======================================================== -->
    <div class="booking-step-panel" data-step="4" style="display: none;">
      <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Step 4: Review Booking Summary</h2>
      <p style="margin-bottom: 20px;">Review your shoot specifications and itemized cost breakdown before paying the advance.</p>

      <div style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; margin-bottom: 24px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 0.9375rem;">
          <div><strong style="color: var(--text-muted);">Creator:</strong> <?php echo e($creator['studio_name'] ?: $creator['creator_name']); ?></div>
          <div><strong style="color: var(--text-muted);">Selected Equipment:</strong> <span id="summary-display-equipment">Camera</span></div>
          <div><strong style="color: var(--text-muted);">Duration:</strong> <span id="summary-display-duration">2 Hours</span></div>
          <div><strong style="color: var(--text-muted);">Pricing Mode:</strong> Verified Dynamic Estimate</div>
        </div>
      </div>

      <!-- Itemized Price Breakdown -->
      <table class="summary-table">
        <thead>
          <tr>
            <th>Description</th>
            <th style="text-align: right;">Amount (INR)</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Creator Base Shoot Rate (Standard 2hr Session)</td>
            <td style="text-align: right;" id="summary-display-base"><?php echo format_inr($creator['starting_price']); ?></td>
          </tr>
          <tr>
            <td>Additional Bundled Equipment Charges (Camera / Mobile / Drone)</td>
            <td style="text-align: right;" id="summary-display-addons">₹0.00</td>
          </tr>
          <tr>
            <td>Extra Duration Surcharge (Over 2 Hours)</td>
            <td style="text-align: right;" id="summary-display-extra-duration">₹0.00</td>
          </tr>
          <tr class="total-row">
            <td><strong>Estimated Booking Total</strong></td>
            <td style="text-align: right; font-size: 1.25rem;" id="summary-display-total"><?php echo format_inr($creator['starting_price']); ?></td>
          </tr>
          <tr class="advance-row">
            <td>
              <strong>Advance Required (<?php echo DEFAULT_ADVANCE_PERCENT; ?>% to confirm booking)</strong><br>
              <span style="font-size: 0.8125rem; font-weight: normal; color: var(--text-muted);">Deducted from your total; locks creator schedule</span>
            </td>
            <td style="text-align: right; font-size: 1.25rem; font-weight: 800;" id="summary-display-advance">
              <?php echo format_inr($creator['starting_price'] * (DEFAULT_ADVANCE_PERCENT / 100)); ?>
            </td>
          </tr>
          <tr>
            <td><strong>Remaining Balance (Payable directly on shoot day)</strong></td>
            <td style="text-align: right; font-weight: 700;" id="summary-display-balance">
              <?php echo format_inr($creator['starting_price'] * (1 - (DEFAULT_ADVANCE_PERCENT / 100))); ?>
            </td>
          </tr>
        </tbody>
      </table>

      <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 20px;">
        * Notice: The booking total represents a confirmed platform estimate. Any extended shoot hours on the venue can be settled directly with the creator.
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 28px;">
        <button type="button" class="btn btn-secondary btn-step-prev">&larr; Back</button>
        <button type="button" class="btn btn-primary btn-step-next">Proceed to Step 5: Advance Payment &rarr;</button>
      </div>
    </div>

    <!-- ========================================================
         STEP 5: Advance Payment & Mock Gateway
         ======================================================== -->
    <div class="booking-step-panel" data-step="5" style="display: none;">
      <h2 style="font-size: 1.5rem; margin-bottom: 6px;">Step 5: Advance Payment</h2>
      <p style="margin-bottom: 20px;">Pay the mandatory <?php echo DEFAULT_ADVANCE_PERCENT; ?>% deposit to confirm your booking.</p>

      <!-- Test Mode Notice -->
      <div class="payment-mock-banner">
        <h4>Simulated Local Test Payment Gateway</h4>
        <p>This is a local demonstration running on XAMPP. Real card credentials are never requested or stored. Selecting an option simulates an instant verified transaction.</p>
      </div>

      <div class="form-group">
        <label class="form-label">Choose Payment Method *</label>
        <div class="choice-card-grid" style="grid-template-columns: 1fr 1fr 1fr;">
          <label class="choice-card">
            <input type="radio" name="payment_method" value="upi" checked>
            <div class="choice-title">UPI / QR Code</div>
            <div class="choice-desc">Instant mock pay via GPay, PhonePe, Paytm, or BHIM.</div>
          </label>

          <label class="choice-card">
            <input type="radio" name="payment_method" value="card">
            <div class="choice-title">Debit / Credit Card</div>
            <div class="choice-desc">Visa, Mastercard, RuPay mock payment simulator.</div>
          </label>

          <label class="choice-card">
            <input type="radio" name="payment_method" value="cash_pay_later">
            <div class="choice-title">Pay Later / Discuss</div>
            <div class="choice-desc">Submit request now; settle advance upon creator acceptance.</div>
          </label>
        </div>
      </div>

      <!-- UPI Simulation Box -->
      <div id="payment-details-upi" class="upi-qr-box">
        <div style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">Scan Test QR Code</div>
        <!-- Stylized QR Code SVG -->
        <svg width="140" height="140" viewBox="0 0 100 100" style="margin: 0 auto 10px; display: block; border: 4px solid #000; padding: 4px; background: #fff;">
          <rect x="10" y="10" width="24" height="24" fill="#000"/>
          <rect x="14" y="14" width="16" height="16" fill="#fff"/>
          <rect x="18" y="18" width="8" height="8" fill="#000"/>
          <rect x="66" y="10" width="24" height="24" fill="#000"/>
          <rect x="70" y="14" width="16" height="16" fill="#fff"/>
          <rect x="74" y="18" width="8" height="8" fill="#000"/>
          <rect x="10" y="66" width="24" height="24" fill="#000"/>
          <rect x="14" y="70" width="16" height="16" fill="#fff"/>
          <rect x="18" y="74" width="8" height="8" fill="#000"/>
          <rect x="42" y="14" width="8" height="20" fill="#000"/>
          <rect x="42" y="66" width="16" height="16" fill="#000"/>
          <rect x="66" y="66" width="20" height="8" fill="#000"/>
          <rect x="78" y="78" width="8" height="8" fill="#000"/>
        </svg>
        <div style="font-size: 0.875rem; font-weight: 600;">UPI ID: <code>shootkaro@icici</code></div>
        <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 4px;">Advance Payable: <strong id="pay-advance-label-upi" style="color: #000;"><?php echo format_inr($creator['starting_price'] * (DEFAULT_ADVANCE_PERCENT / 100)); ?></strong></div>
      </div>

      <!-- Card Simulation Box -->
      <div id="payment-details-card" style="display: none; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin: 16px 0;">
        <div style="font-size: 0.875rem; font-weight: 700; margin-bottom: 10px;">Simulated Card Details</div>
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 12px;">
          <div>
            <label class="form-label" style="font-size: 0.75rem;">Card Number</label>
            <input type="text" class="form-control" value="4111 2222 3333 4444" readonly>
          </div>
          <div>
            <label class="form-label" style="font-size: 0.75rem;">Expiry</label>
            <input type="text" class="form-control" value="12/28" readonly>
          </div>
          <div>
            <label class="form-label" style="font-size: 0.75rem;">CVV</label>
            <input type="text" class="form-control" value="999" readonly>
          </div>
        </div>
        <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 8px;">Advance Payable: <strong id="pay-advance-label-card" style="color: #000;"><?php echo format_inr($creator['starting_price'] * (DEFAULT_ADVANCE_PERCENT / 100)); ?></strong></div>
      </div>

      <!-- Pay Later Box -->
      <div id="payment-details-later" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 14px; margin: 16px 0; font-size: 0.875rem; color: #92400e;">
        <strong>Pay Later Selected:</strong> Your booking request will be placed in <em>Pending</em> status. You can coordinate custom shoot details with the creator before final payment confirmation.
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 28px;">
        <button type="button" class="btn btn-secondary btn-step-prev">&larr; Back</button>
        <button type="submit" class="btn btn-primary btn-lg">
          Confirm Booking &amp; Complete Advance &rarr;
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
