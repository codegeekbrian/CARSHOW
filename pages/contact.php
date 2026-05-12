<?php 
require_once '../include/csrf.php';
$current_page = 'contact'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us — Own It &amp; Drive It</title>
  <?php $seo_title='Contact Us — Own It & Drive It'; $seo_desc='Get in touch with Own It & Drive It STORE. Send us a message about vehicles, pricing, or availability.'; include '../include/seo.php'; ?>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<?php include "../include/nav.php"; ?>

<div class="site-hero">
  <h1>GET IN <span>TOUCH</span></h1>
  <p>We'd love to help you find your perfect vehicle. Send us a message and we'll respond promptly.</p>
  <div class="hero-rule"></div>
</div>

<div class="page-content">
  <div class="contact-wrap">
    <h2>Send a Message</h2>
    <form action="../include/contact_handler.php" method="POST">
      <?php echo csrfField(); ?>
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Your name" required>
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" placeholder="Vehicle inquiry, pricing, etc.">
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" rows="5" placeholder="Tell us what you're looking for..." required></textarea>
      </div>
      <button type="submit" class="btn-submit">Send Message</button>
      <div id="response"></div>
    </form>
  </div>

  <div class="contact-info-strip">
    <div class="contact-info-item">
      <div class="ci-icon">📍</div>
      <div class="ci-label">Location</div>
      <div class="ci-val">Nairobi, Kenya</div>
    </div>
    <div class="contact-info-item">
      <div class="ci-icon">📧</div>
      <div class="ci-label">Email</div>
      <div class="ci-val">info@ownitdriveit.co.ke</div>
    </div>
    <div class="contact-info-item">
      <div class="ci-icon">⏰</div>
      <div class="ci-label">Hours</div>
      <div class="ci-val">Mon–Sat: 8am – 6pm</div>
    </div>
  </div>
</div>

<?php include "../include/footer.php"; ?>
</body>
</html>
