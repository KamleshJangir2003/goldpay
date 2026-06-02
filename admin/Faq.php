<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FAQ - Goldpay</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", sans-serif;
      background-color: #000;
      color: #D4AF37;
    }

    header {
      background: #000;
      color: #D4AF37;
      padding: 40px 20px;
      text-align: center;
      border-bottom: 1px solid #333;
    }

    header h1 {
      margin: 0;
      font-size: 36px;
    }

    .breadcrumb {
      font-size: 14px;
      margin-top: 8px;
      color: #aaa;
    }

    .faq-container {
      max-width: 1000px;
      margin: 30px auto;
      padding: 0 20px 60px;
    }

    .faq-section {
      background-color: #111;
      border: 1px solid #333;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 0 10px rgba(255, 204, 0, 0.15);
    }

    .faq-question {
      font-size: 18px;
      font-weight: bold;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .faq-question:hover {
      color: #fff;
    }

    .faq-answer {
      display: none;
      margin-top: 15px;
      font-size: 15px;
      color: #ddd;
      line-height: 1.6;
    }

    .faq-section.active .faq-answer {
      display: block;
    }

    .faq-section.active .toggle-icon {
      transform: rotate(45deg);
    }

    .toggle-icon {
      font-size: 20px;
      transition: transform 0.3s ease;
    }

    footer {
      text-align: center;
      padding: 20px;
      background: #000;
      color: #888;
      border-top: 1px solid #333;
    }

    a {
      color: #D4AF37;
      text-decoration: underline;
    }
    .breadcrumb{
      color: #D4AF37;
    }
  </style>
</head>
<body>

  <header>
    <h1>Frequently Asked Questions</h1>
    <div class="breadcrumb"><a href="index.php">Home</a> / FAQ</div>
  </header>

  <div class="faq-container">

    <div class="faq-section">
      <div class="faq-question">What is Goldpay?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        Goldpay is a secure cryptocurrency exchange that allows users to buy and sell USDT with INR instantly. We offer low fees, strong security, real-time trading, and a user-friendly platform suitable for both beginners and professionals.
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">How do I create an account?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        Creating an account is simple:
        <ol>
          <li>Go to our registration page.</li>
          <li>Enter your basic details (name, email, phone).</li>
          <li>Verify your email and complete KYC (upload Aadhaar/PAN).</li>
          <li>Start using the platform.</li>
        </ol>
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">Is my data secure on Goldpay?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        Absolutely. We use AES-256 encryption for stored data, SSL certificates for data transmission, and 2FA for account security. We do not share your personal information with third parties without your consent.
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">How can I deposit INR?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        You can deposit INR using:
        <ul>
          <li>UPI</li>
          <li>Bank Transfer (IMPS/NEFT/RTGS)</li>
          <li>Payment gateways (coming soon)</li>
        </ul>
        Deposits are typically processed within 5-10 minutes.
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">What fees does Goldpay charge?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        Our standard trading fee is 0.1%. There are no fees on deposits. A small withdrawal fee applies based on network congestion. All fees are displayed clearly before you confirm any transaction.
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">Can I use Goldpay on mobile?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        Yes, our platform is fully mobile responsive. Additionally, native apps for Android and iOS are under development and will be released soon.
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">How do I invite others and earn rewards?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        Visit your account dashboard and go to the "Referral" section. Copy your referral link and share it. When someone signs up and trades using your link, you get a commission on their fees.
      </div>
    </div>

    <div class="faq-section">
      <div class="faq-question">Who do I contact for support?<span class="toggle-icon">+</span></div>
      <div class="faq-answer">
        You can reach out to our 24/7 support team by:
        <ul>
          <li>Email: <a href="mailto:support@Goldpay.com">support@Goldpay.com</a></li>
          <li>Live chat from the website</li>
          <li>Help Center (Coming Soon)</li>
        </ul>
      </div>
    </div>

  </div>

  <footer style="color: #D4AF37;">
    © 2026 Goldpay. All Rights Reserved.
  </footer>

  <script>
    document.querySelectorAll('.faq-question').forEach(q => {
      q.addEventListener('click', () => {
        q.parentElement.classList.toggle('active');
      });
    });
  </script>

</body>
</html>
