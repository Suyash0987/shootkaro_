</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Col 1: Brand & Bio -->
      <div class="footer-brand">
        <div class="brand-logo-wrap" style="color: #ffffff; margin-bottom: 16px;">
          <img src="<?php echo url('assets/logos/logo.svg'); ?>" alt="Shoot Karo Logo" class="brand-icon" style="filter: invert(1);">
          <span><?php echo e(APP_NAME); ?></span>
        </div>
        <p>Premium platform connecting discerning clients with elite local photographers, cinematic videographers, viral mobile reel creators, and certified drone pilots.</p>
      </div>

      <!-- Col 2: Navigation Links -->
      <div class="footer-col">
        <h4>Explore</h4>
        <ul class="footer-links">
          <li><a href="<?php echo url('index.php'); ?>">Home</a></li>
          <li><a href="<?php echo url('creators.php'); ?>">Find Creators</a></li>
          <li><a href="<?php echo url('about.php'); ?>">About Shoot Karo</a></li>
          <li><a href="<?php echo url('about.php#how-it-works'); ?>">How It Works</a></li>
          <li><a href="<?php echo url('creator-register.php'); ?>">Register as Creator</a></li>
        </ul>
      </div>

      <!-- Col 3: Customer Care -->
      <div class="footer-col">
        <h4>Support &amp; Trust</h4>
        <ul class="footer-links">
          <li><a href="<?php echo url('contact.php'); ?>">Customer Support</a></li>
          <li><a href="<?php echo url('contact.php#complaint-form'); ?>">Submit a Complaint</a></li>
          <li><a href="<?php echo url('terms.php'); ?>">Terms &amp; Conditions</a></li>
          <li><a href="<?php echo url('privacy.php'); ?>">Privacy Policy</a></li>
        </ul>
      </div>

      <!-- Col 4: Official Contact -->
      <div class="footer-col">
        <h4>Direct Contact</h4>
        <p class="footer-contact-item">
          <strong style="color: #ffffff;">Helpline:</strong><br>
          <?php echo e(SUPPORT_PHONE); ?>
        </p>
        <p class="footer-contact-item">
          <strong style="color: #ffffff;">Email Support:</strong><br>
          <?php echo e(SUPPORT_EMAIL); ?>
        </p>
        <p class="footer-contact-item">
          <strong style="color: #ffffff;">Operations:</strong><br>
          Pune, Maharashtra, India
        </p>
      </div>
    </div>

    <div class="footer-bottom">
      <div>&copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?>. All rights reserved.</div>
      <div>Designed for fast local deployment on XAMPP. Built with PHP, MySQL, and vanilla standards.</div>
    </div>
  </div>
</footer>

<script src="<?php echo url('assets/js/app.js'); ?>"></script>
</body>
</html>
