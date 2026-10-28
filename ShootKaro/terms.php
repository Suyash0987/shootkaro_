<?php
/**
 * Shoot Karo - Terms & Conditions
 */
$pageTitle = 'Terms & Conditions';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="max-width: 820px; margin: 0 auto;">
    <div style="margin-bottom: 36px;">
      <div class="section-subtitle">Legal Agreement</div>
      <h1>Terms &amp; Conditions</h1>
      <p>Last updated: <?php echo date('F Y'); ?></p>
    </div>

    <div class="card" style="line-height: 1.8; color: var(--text-main);">
      <h3 style="margin-bottom: 12px;">1. Acceptance of Terms</h3>
      <p style="margin-bottom: 20px;">By using the <strong>Shoot Karo</strong> platform, clients and visual creators agree to comply with these terms, governing service discovery, booking agreements, payments, and deliverables delivery.</p>

      <h3 style="margin-bottom: 12px;">2. Booking Milestones &amp; Advance Deposits</h3>
      <p style="margin-bottom: 20px;">To confirm a shoot, clients deposit a mandatory <?php echo DEFAULT_ADVANCE_PERCENT; ?>% advance. This deposit is non-refundable within 48 hours of scheduled shoot commencement to safeguard creator availability and gear reservations. The remaining balance is payable on shoot day.</p>

      <h3 style="margin-bottom: 12px;">3. Creator Obligations &amp; Quality Standards</h3>
      <p style="margin-bottom: 20px;">Creators are independent contractors responsible for professional conduct, punctuality, bringing verified camera/drone/mobile equipment, and delivering post-processed deliverables within the agreed timeline.</p>

      <h3 style="margin-bottom: 12px;">4. Drone Safety &amp; Legal Compliance</h3>
      <p style="margin-bottom: 20px;">All drone operations must comply with Indian civil aviation regulations (DGCA). Flights in restricted or sensitive zones without client venue permissions are strictly prohibited.</p>

      <h3 style="margin-bottom: 12px;">5. Deliverables &amp; Intellectual Property</h3>
      <p>Clients receive non-exclusive rights to use delivered photos and videos for personal or commercial brand purposes. Creators retain portfolio display rights unless an NDA or exclusivity agreement is formally executed.</p>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
