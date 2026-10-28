<?php
/**
 * Shoot Karo - Login Portal
 */
$pageTitle = 'Login to Shoot Karo';
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';

if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'customer';
    if ($role === 'admin') redirect('admin/index.php');
    if ($role === 'creator') redirect('dashboard/creator.php');
    redirect('dashboard/customer.php');
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container section" style="max-width: 480px;">
  <div class="card" style="box-shadow: var(--shadow-lg); padding: 36px 32px;">
    <div style="text-align: center; margin-bottom: 28px;">
      <img src="<?php echo url('assets/logos/logo.svg'); ?>" alt="Shoot Karo Logo" style="width: 44px; height: 44px; margin-bottom: 12px;">
      <h1 style="font-size: 1.75rem; margin-bottom: 6px;">Sign In</h1>
      <p style="font-size: 0.875rem;">Access your bookings, shoots, or creator studio.</p>
    </div>

    <!-- Quick Demo Accounts Helper -->
    <div style="background: var(--bg-secondary); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; margin-bottom: 24px; font-size: 0.8125rem;">
      <strong style="display: block; margin-bottom: 4px; color: var(--text-main);">Demo Credentials for Testing:</strong>
      <div style="display: flex; flex-direction: column; gap: 4px; color: var(--text-muted);">
        <div><strong>Admin:</strong> <code>admin@shootkaro.local</code> / <code>Admin@12345</code></div>
        <div><strong>Creator:</strong> <code>aditya@shootkaro.local</code> / <code>Creator@12345</code></div>
        <div><strong>Customer:</strong> <code>customer@shootkaro.local</code> / <code>Customer@12345</code></div>
      </div>
    </div>

    <form action="<?php echo url('actions/auth-action.php?action=login'); ?>" method="POST">
      <?php echo csrf_input(); ?>

      <div class="form-group">
        <label class="form-label" for="login-email">Email Address</label>
        <input type="email" name="email" id="login-email" class="form-control" placeholder="name@example.com" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="login-password">Password</label>
        <input type="password" name="password" id="login-password" class="form-control" placeholder="Enter your password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 24px;">Sign In</button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 0.875rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 20px;">
      Need a customer account? <a href="<?php echo url('register.php'); ?>" style="font-weight: 700; color: var(--primary-black);">Register as Customer</a>
      <div style="margin-top: 10px;">
        Are you a creator? <a href="<?php echo url('creator-register.php'); ?>" style="font-weight: 700; color: var(--primary-black);">Apply to Join as Creator</a>
      </div>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
