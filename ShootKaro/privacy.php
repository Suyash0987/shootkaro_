<?php
/**
 * Shoot Karo - Privacy Policy
 */
$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="max-width: 820px; margin: 0 auto;">
    <div style="margin-bottom: 36px;">
      <div class="section-subtitle">Data Protection</div>
      <h1>Privacy Policy</h1>
      <p>Last updated: <?php echo date('F Y'); ?></p>
    </div>

    <div class="card" style="line-height: 1.8; color: var(--text-main);">
      <h3 style="margin-bottom: 12px;">1. Information We Collect</h3>
      <p style="margin-bottom: 20px;">We collect customer names, phone numbers, email addresses, and event venue coordinates exclusively to facilitate shoot matching and creator coordination.</p>

      <h3 style="margin-bottom: 12px;">2. Payment Information Security</h3>
      <p style="margin-bottom: 20px;">Shoot Karo does not store debit or credit card credentials. Payment confirmations are tokenized through secure bank gateways.</p>

      <h3 style="margin-bottom: 12px;">3. Private Shoot Deliverables</h3>
      <p style="margin-bottom: 20px;">Shoot deliverables uploaded to our system are secured. Only the client associated with the specific booking reference and the shoot creator have access to private download tokens.</p>

      <h3 style="margin-bottom: 12px;">4. Contact Us</h3>
      <p>For data privacy queries or account deletion requests, email our data desk at <code><?php echo e(SUPPORT_EMAIL); ?></code>.</p>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
