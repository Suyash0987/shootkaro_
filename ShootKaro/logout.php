<?php
/**
 * Shoot Karo - Logout Handler
 */
require_once __DIR__ . '/config/app.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

logout_user();
set_flash('info', 'You have been logged out successfully.');
redirect('login.php');
