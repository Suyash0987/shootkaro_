<?php
/**
 * Shoot Karo - Customer Registration
 */
$pageTitle = 'Create Customer Account';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section" style="max-width: 520px;">
  <div class="card" style="box-shadow: var(--shadow-lg); padding: 36px 32px;">
    <div style="text-align: center; margin-bottom: 28px;">
      <img src="<?php echo url('assets/logos/logo.svg'); ?>" alt="Shoot Karo Logo" style="width: 44px; height: 44px; margin-bottom: 12px;">
      <h1 style="font-size: 1.75rem; margin-bottom: 6px;">Join Shoot Karo</h1>
      <p style="font-size: 0.875rem;">Create an account to book top creators and manage shoots.</p>
    </div>

    <form action="<?php echo url('actions/auth-action.php?action=register'); ?>" method="POST">
      <?php echo csrf_input(); ?>

      <div class="form-group">
        <label class="form-label" for="reg-name">Full Name *</label>
        <input type="text" name="name" id="reg-name" class="form-control" placeholder="e.g. Rahul Sharma" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="reg-email">Email Address *</label>
        <input type="email" name="email" id="reg-email" class="form-control" placeholder="name@example.com" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="reg-phone">Phone Number *</label>
        <input type="tel" name="phone" id="reg-phone" class="form-control" placeholder="10-digit mobile number" pattern="[0-9]{10}" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="reg-password">Password *</label>
        <input type="password" name="password" id="reg-password" class="form-control" placeholder="At least 6 characters" minlength="6" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="reg-confirm-password">Confirm Password *</label>
        <input type="password" name="confirm_password" id="reg-confirm-password" class="form-control" placeholder="Re-enter password" minlength="6" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 24px;">Create Customer Account</button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.875rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 20px;">
      Already have an account? <a href="<?php echo url('login.php'); ?>" style="font-weight: 700; color: var(--primary-black);">Log In</a>
      <div style="margin-top: 10px;">
        Want to offer your shooting services? <a href="<?php echo url('creator-register.php'); ?>" style="font-weight: 700; color: var(--primary-black);">Register as Creator</a>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
