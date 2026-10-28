<?php
/**
 * Global Header Component for Shoot Karo
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/functions.php';

$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | ' : ''; ?><?php echo e(APP_NAME); ?> - Professional Photography &amp; Film Creators</title>
  <link rel="icon" type="image/svg+xml" href="<?php echo url('assets/logos/logo.svg'); ?>">
  <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>

<header class="site-header">
  <div class="container header-container">
    <a href="<?php echo url('index.php'); ?>" class="brand-logo-wrap" title="<?php echo e(APP_NAME); ?>">
      <img src="<?php echo url('assets/logos/logo.svg'); ?>" alt="Shoot Karo Logo" class="brand-icon">
      <span><?php echo e(APP_NAME); ?></span>
    </a>

    <button type="button" class="mobile-menu-btn" aria-label="Toggle navigation">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
      </svg>
    </button>

    <nav class="main-nav">
      <a href="<?php echo url('index.php'); ?>" class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">Home</a>
      <a href="<?php echo url('creators.php'); ?>" class="nav-link <?php echo ($currentPage === 'creators.php' || $currentPage === 'creator-profile.php') ? 'active' : ''; ?>">Find Creators</a>
      <a href="<?php echo url('about.php'); ?>" class="nav-link <?php echo ($currentPage === 'about.php') ? 'active' : ''; ?>">About Us</a>
      <a href="<?php echo url('contact.php'); ?>" class="nav-link <?php echo ($currentPage === 'contact.php') ? 'active' : ''; ?>">Contact / Support</a>

      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?php echo url('admin/index.php'); ?>" class="nav-link <?php echo (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? 'active' : ''; ?>">Admin Panel</a>
        <?php elseif ($user['role'] === 'creator'): ?>
          <a href="<?php echo url('dashboard/creator.php'); ?>" class="nav-link <?php echo ($currentPage === 'creator.php') ? 'active' : ''; ?>">Creator Studio</a>
        <?php else: ?>
          <a href="<?php echo url('dashboard/customer.php'); ?>" class="nav-link <?php echo ($currentPage === 'customer.php') ? 'active' : ''; ?>">My Bookings</a>
        <?php endif; ?>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <?php if ($user): ?>
        <div style="display: flex; align-items: center; gap: 10px;">
          <a href="<?php 
            echo ($user['role'] === 'admin') ? url('admin/index.php') : 
                 (($user['role'] === 'creator') ? url('dashboard/creator.php') : url('dashboard/customer.php')); 
          ?>" class="btn btn-secondary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <?php echo e(explode(' ', $user['name'])[0]); ?>
          </a>
          <a href="<?php echo url('logout.php'); ?>" class="btn btn-outline btn-sm" title="Log out">Logout</a>
        </div>
      <?php else: ?>
        <a href="<?php echo url('login.php'); ?>" class="btn btn-secondary btn-sm">Login</a>
        <a href="<?php echo url('creator-register.php'); ?>" class="btn btn-primary btn-sm">Register as Creator</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php echo render_flash(); ?>

<main>
