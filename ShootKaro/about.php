<?php
/**
 * Shoot Karo - About Us & How It Works
 */
$pageTitle = 'About Shoot Karo & How It Works';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="max-width: 820px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 50px;">
      <div class="section-subtitle">Our Vision</div>
      <h1 style="font-size: 2.5rem; margin-bottom: 12px;">The Modern Photography Marketplace</h1>
      <p style="font-size: 1.125rem;">Built to simplify how clients discover, book, and collaborate with certified visual storytellers.</p>
    </div>

    <div class="card" style="margin-bottom: 36px;">
      <h3 style="margin-bottom: 14px;">Why Shoot Karo?</h3>
      <p style="font-size: 1rem; line-height: 1.8; margin-bottom: 16px; color: var(--text-main);">
        Inspired by the seamless on-demand experience of modern mobility apps like Ola and Uber, <strong>Shoot Karo</strong> brings structure, transparency, and trust to the fragmented freelance photography and cinematography sector.
      </p>
      <p style="font-size: 1rem; line-height: 1.8; color: var(--text-main);">
        Instead of endless phone calls, unclear quotes, and missed shoot schedules, Shoot Karo provides real-time location-based discovery across Pune, clear upfront estimates, customizable equipment packages (Camera, Mobile Reels, and 4K Drones), and secure advance escrow milestones.
      </p>
    </div>

    <!-- How It Works Section Anchor -->
    <div id="how-it-works" class="card" style="margin-bottom: 36px;">
      <h3 style="margin-bottom: 24px;">How the Workflow Works</h3>

      <div style="display: flex; flex-direction: column; gap: 24px;">
        <div style="display: flex; gap: 20px; align-items: flex-start;">
          <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: var(--primary-black); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem; flex-shrink: 0;">
            1
          </div>
          <div>
            <h4 style="margin-bottom: 6px;">Location-Based Discovery</h4>
            <p style="font-size: 0.9375rem; margin: 0;">Filter creators in your specific Pune area (Baner, Kothrud, Hinjawadi, Wakad, Hadapsar, Shivajinagar) and inspect genuine past work portfolios and verified reviews.</p>
          </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: flex-start;">
          <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: var(--primary-black); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem; flex-shrink: 0;">
            2
          </div>
          <div>
            <h4 style="margin-bottom: 6px;">Select Shooting Equipment &amp; Deliverables</h4>
            <p style="font-size: 0.9375rem; margin: 0;">Choose exactly what your shoot requires: Professional full-frame camera gear, high-velocity iPhone vertical reels, or certified 4K aerial drone passes.</p>
          </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: flex-start;">
          <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: var(--primary-black); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem; flex-shrink: 0;">
            3
          </div>
          <div>
            <h4 style="margin-bottom: 6px;">20% Advance Commitment</h4>
            <p style="font-size: 0.9375rem; margin: 0;">Lock in your shoot date with a 20% advance via UPI, Card, or direct discussion. The remaining balance is payable on shoot day.</p>
          </div>
        </div>

        <div style="display: flex; gap: 20px; align-items: flex-start;">
          <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: var(--primary-black); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem; flex-shrink: 0;">
            4
          </div>
          <div>
            <h4 style="margin-bottom: 6px;">Deliverables &amp; Verified Review</h4>
            <p style="font-size: 0.9375rem; margin: 0;">Once your shoot is completed, download your high-res color graded photo sets and 4K video reels directly from your dashboard, and leave an authentic review.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Creator Call to Action -->
    <div class="card" style="text-align: center; background: var(--bg-dark); color: #ffffff; border-color: #1f2937; padding: 40px;">
      <h3 style="color: #ffffff; margin-bottom: 8px;">Are You a Professional Visual Creator?</h3>
      <p style="color: #9ca3af; margin-bottom: 24px;">Join Pune's premier creator network and gain direct access to pre-qualified event, wedding, and commercial clients.</p>
      <a href="<?php echo url('creator-register.php'); ?>" class="btn btn-secondary btn-lg" style="background: #ffffff; color: #000;">
        Apply as a Creator
      </a>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
