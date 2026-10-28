<?php
/**
 * Shoot Karo - Customer Support & Complaint Desk
 */
$pageTitle = 'Contact Support & Complaint Form';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

$user = current_user();
$prefillName  = $user ? $user['name'] : '';
$prefillEmail = $user ? $user['email'] : '';

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section">
  <div style="max-width: 820px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 40px;">
      <div class="section-subtitle">Customer Care</div>
      <h1 style="font-size: 2.25rem; margin-bottom: 8px;">Contact &amp; Support Desk</h1>
      <p style="font-size: 1rem;">We are here to assist with shoot inquiries, schedule changes, or service disputes.</p>
    </div>

    <!-- Official Contact Placeholders -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px;">
      <div class="card" style="margin: 0; text-align: center; padding: 30px 20px;">
        <div style="font-size: 0.8125rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Official Phone Helpline</div>
        <h3 style="font-size: 1.5rem; margin-bottom: 4px;"><?php echo e(SUPPORT_PHONE); ?></h3>
        <p style="font-size: 0.8125rem; margin: 0;">Monday to Saturday: 9:00 AM - 8:00 PM IST</p>
      </div>

      <div class="card" style="margin: 0; text-align: center; padding: 30px 20px;">
        <div style="font-size: 0.8125rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Official Email Support</div>
        <h3 style="font-size: 1.5rem; margin-bottom: 4px;"><?php echo e(SUPPORT_EMAIL); ?></h3>
        <p style="font-size: 0.8125rem; margin: 0;">Guaranteed response within 24 hours</p>
      </div>
    </div>

    <!-- Complaint & Dispute Form Anchor -->
    <div id="complaint-form" class="card" style="box-shadow: var(--shadow-md); padding: 36px 32px;">
      <div class="card-header" style="margin-bottom: 24px;">
        <div>
          <h3 style="margin: 0;">Submit a Complaint / Dispute Ticket</h3>
          <p style="font-size: 0.875rem; margin-top: 4px;">Have an issue with a shoot, creator punctuality, or deliverable quality? File a ticket below.</p>
        </div>
      </div>

      <form action="<?php echo url('actions/complaint-action.php'); ?>" method="POST">
        <?php echo csrf_input(); ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label" for="cmp-name">Your Full Name *</label>
            <input type="text" name="name" id="cmp-name" class="form-control" value="<?php echo e($prefillName); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="cmp-email">Email Address *</label>
            <input type="email" name="email" id="cmp-email" class="form-control" value="<?php echo e($prefillEmail); ?>" required>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
          <div class="form-group">
            <label class="form-label" for="cmp-booking">Related Booking Reference (If applicable)</label>
            <input type="text" name="booking_reference" id="cmp-booking" class="form-control" placeholder="e.g. SK-BK-20261010-1001">
          </div>

          <div class="form-group">
            <label class="form-label" for="cmp-subject">Dispute Category / Subject *</label>
            <input type="text" name="subject" id="cmp-subject" class="form-control" placeholder="e.g. Shoot delay, Deliverable format issue" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cmp-message">Detailed Complaint Description *</label>
          <textarea name="message" id="cmp-message" class="form-control" rows="4" placeholder="Please describe the incident, creator communication, and expected resolution..." required></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">Submit Ticket to Administration</button>
      </form>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
