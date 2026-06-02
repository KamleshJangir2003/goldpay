<?php
// index.php
$pageTitle = "Goldpay - Crypto Exchange";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goldpay - Crypto Exchange</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="icon" type="image/x-icon" href="../images/dollario-fav.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #02857F;
            --accent: #10b981;
            --background: #0f172a;
            --surface: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --gold: #D4AF37;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: url('/images/backgroundimg.png') no-repeat center center;
            background-size: cover;
            color: var(--text-primary);
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        .nav-links {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-style: normal;
        }

        /* Header Styles */
        header {
            padding: 1rem 2rem;
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            height: 100px;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header img {
            width: 150px;
            height: 210px;
            margin-top: -70px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            margin-top: -65px;
        }

        .rupeee img {
            width: 200px;
            height: auto;
        }

        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:hover {
            color: var(--gold);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            margin-top: -68px;
        }

        /* Hero Section */
        .hero {
            padding: 8rem 2rem 4rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            background: url('uploads/backgroundimg.png') no-repeat center center;
            background-size: cover;
            background-attachment: fixed;
            position: relative;
            color: #fff;
            z-index: 1;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-color: transparent;
            z-index: -1;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            align-items: center;
        }

        .hero-content {
            flex: 1;
            min-width: 300px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-content p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 500px;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gold);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .price-ticker {
            flex: 1;
            min-width: 300px;
            text-align: center;
            padding: 2rem;
        }

        .price {
            position: relative;
            padding: 10px 20px;
            font-size: 2.5rem;
            font-weight: 700;
            color: #008000;
            border-radius: 8px;
            z-index: 1;
            display: inline-block;
            background: #000;
            overflow: hidden;
        }

        .price::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
            background: conic-gradient(var(--gold), transparent, var(--gold));
            animation: rotateBorder 1.5s linear infinite;
            z-index: -1;
        }

        .price::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            bottom: 2px;
            background: #000;
            border-radius: 6px;
            z-index: -1;
        }

        @keyframes rotateBorder {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            background: black;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .feature-item {
            padding: 2rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .feature-icon {
            font-size: 1.5rem;
            color: #0f172a;
            width: 50px;
            height: 50px;
            background: beige;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .feature-content h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .feature-content p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* FAQ Section */
        .faq-section {
            background: black;
            padding: 4rem 2rem;
        }

        .faq-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .faq-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
            color: var(--gold);
            font-family: "Oleo Script", system-ui;
        }

        .faq-grid {
            display: grid;
            gap: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .faq-item {
            background: black;
            padding: 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }

        .faq-question {
            color: var(--gold);
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question::after {
            content: '+';
            font-size: 1.5rem;
            color: var(--gold);
        }

        .faq-item.active .faq-question::after {
            content: '-';
        }

        .faq-answer {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-answer {
            max-height: 300px;
            margin-top: 1rem;
        }

        /* Footer */
        footer {
            background: black;
            padding: 3rem 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-column {
            padding: 0 1rem;
        }

        .footer-column h3 {
            color: var(--gold);
            margin-bottom: 1rem;
            font-size: 1rem;

        }

        .footer-column p,
        .footer-column a {
            color: var(--gold);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 0.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: var(--gold);
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--gold);
        }

        .newsletter-input {
            display: flex;
            margin-top: 1rem;
            position: relative;
            width: 150px;
        }

        .newsletter-input input {
            padding: 0.6rem;
            border: 1px solid var(--gold);
            border-radius: 30px;
            background: black;
            /* color: white; */
            flex: 1;
            font-size: 0.85rem;


        }

        input[type=email] {
            outline: none;
        }

        .newsletter-input input[type=email]:focus {
            border: 2px solid var(--gold);
        }


        .line-hr {
            height: 36px;
            width: 1px;
            color: var(--gold);
            background-color: var(--gold);
            position: absolute;
            left: 75%;
            transform: translateY(0%);

        }

        .newsletter-input button {
            border: none;
            background: black;
            color: var(--gold);
            padding: 0 0.75rem;
            border-radius: 30px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: bold;
            position: absolute;
            left: 80%;
            transform: translateY(50%);

        }

        .footer-bottom {
            max-width: 1200px;
            margin: 3rem auto 0;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
        }

        .footer-logo {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1rem;
        }

        .footer-logo img {
            height: 40px;
        }

        .copyright {
            color: var(--gold);
            font-size: 0.85rem;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 90px;
                left: 0;
                right: 0;
                background: var(--background);
                flex-direction: column;
                padding: 1rem;
                text-align: center;
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero {
                padding: 6rem 1rem 2rem;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .price {
                font-size: 2rem;
            }

            .features {
                padding: 2rem 1rem;
            }

            .feature-item {
                flex-direction: column;
                text-align: center;
            }

            .faq-title {
                font-size: 2rem;
            }

            .footer-container {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-column {
                text-align: center;
                padding: 0;
            }

            .social-links {
                justify-content: center;
            }

            .newsletter-input {
                max-width: 300px;
                margin: 1rem auto 0;

            }

            .footer-logo img {
                height: 50px;
            }
        }

        @media (max-width: 480px) {
            .btn {
                width: 100%;
                justify-content: center;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .price {
                font-size: 1.75rem;
            }

            .footer-logo img {
                height: 60px;
            }
        }

        /* Theme Toggle Styles */
        .theme-toggle-container {
            display: flex;
            align-items: center;
            margin-left: 1rem;
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            background: transparent;
            border: 2px solid var(--gold);
            border-radius: 30px;
            padding: 6px;
            cursor: pointer;
            position: relative;
            width: 100px;
            height: 40px;
        }

        .theme-option {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50%;
            height: 100%;
            position: relative;
            z-index: 1;
            color: var(--gold);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .theme-option.active {
            color: black;
            background-color: var(--gold);
            border-radius: 20px;
        }

        /* Language Toggle Styles */
        .language-toggle {
            display: flex;
            align-items: center;
            background: transparent;
            border: 2px solid var(--gold);
            border-radius: 30px;
            padding: 6px;
            cursor: pointer;
            position: relative;
            width: 100px;
            height: 40px;
            margin-left: 1rem;
        }

        .language-option {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50%;
            height: 100%;
            position: relative;
            z-index: 1;
            color: var(--gold);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .language-option.active {
            color: black;
            background-color: var(--gold);
            border-radius: 20px;
        }

        /* Light Theme Variables */
        .light-theme {
            --primary: #6366f1;
            --secondary: #02857F;
            --accent: #10b981;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --gold: #D4AF37;
        }

        /* Light Theme Specific Styles */
        .light-theme header {
            background-color: rgba(248, 250, 252, 0.8);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .light-theme .nav-links a {
            color: black;
        }

        .light-theme .features,
        .light-theme .faq-section,
        .light-theme footer {
            background: var(--surface);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .light-theme .faq-item {
            background: var(--surface);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .light-theme .newsletter-input input {
            background: var(--surface);
            color: var(--text-primary);
        }

        .light-theme .footer-column h3,
        .light-theme .footer-column p,
        .light-theme .footer-column a,
        .light-theme .copyright {
            color: black;
        }

        .light-theme h2,
        .light-theme h3,
        .light-theme h4,
        .light-theme p,
        .light-theme .faq-answer,
        .light-theme .faq-question {
            color: #000;
        }

        .light-theme .theme-toggle {
            border: 2px solid black;
        }

        .light-theme .language-toggle {
            border: 2px solid black;
        }

        .light-theme .price,
        .light-theme .price {
            color: white;
        }

        .light-theme .social-links a {
            color: var(--text-primary);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .theme-toggle-container {
                margin: 0.5rem 0;
                justify-content: center;
            }

            .theme-toggle,
            .language-toggle {
                width: 100px;
            }
            .cta-buttons {
            display: flex;
            
            gap: 1rem;
            flex-wrap: wrap;
        }
        }
        

         /* Basic dropdown styles */
  .dropdown {
    position: relative;
    display: inline-block;
  }
  .dropdown-content {
    display: none;
    position: absolute;
    background-color: white;
    min-width: 140px;
    box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
    z-index: 1000;
    border-radius: 4px;
  }
  .dropdown-content a {
    color: black;
    padding: 10px 16px;
    text-decoration: none;
    display: block;
  }
  .dropdown-content a:hover {
    background-color: #f1f1f1;
  }
  .dropdown:hover .dropdown-content {
    display: block;
  }


   .price-ticker {
    background-image: url('images/download.png');
    background-size: cover;
    background-position: 0 0;
    background-repeat: repeat-x;
    animation: scrollBg 20s linear infinite;
    padding: 20px;
    border-radius: 10px;
    color: white;
    position: relative;
    z-index: 1;
    overflow: hidden;
}

/* Overlay for better readability (optional) */
.price-ticker::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
 /* background: rgba(0, 0, 0, 0.4); */
    z-index: 0;
    border-radius: 10px;
}

.price-ticker > * {
    position: relative;
    z-index: 1;
}

/* Animation keyframes for moving background */
@keyframes scrollBg {
    0% {
        background-position: 0 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

    </style>
</head>

<body>
    <header>
        <nav class="nav-container">
            <div class="sidebar-header">
                <img src="../User_dashboard/image/logo.png" alt="Goldpay Logo">
            </div>
            <div class="nav-links">
                <a href="#"><i class="fas fa-exchange-alt"></i> Trade</a>
                <a href="#"><i class="fas fa-chart-line"></i> Markets</a>
                <a href="#"><i class="fas fa-wallet"></i> Wallet</a>
               <div class="dropdown">
  <a href="#"><i class="fas fa-user"></i> Account <i class="fas fa-caret-down"></i></a>
  <div class="dropdown-content">
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </div>
</div>

                <!-- Theme Toggle -->
                <div class="theme-toggle-container">
                    <div class="theme-toggle">
                        <div class="theme-option active" id="dark-theme"><i class="fas fa-moon"></i></div>
                        <div class="theme-option" id="light-theme"><i class="fas fa-sun"></i></div>
                    </div>
                </div>

                <!-- Language Toggle -->
                <div class="language-toggle">
                    <div class="language-option active" id="hindi-lang">हिंदी</div>
                    <div class="language-option" id="english-lang">Eng</div>
                </div>
            </div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>
    <nav class="mobile-menu">
   
         <div class="nav-links">
                <a href="#"><i class="fas fa-exchange-alt"></i> Trade</a>
                <a href="#"><i class="fas fa-chart-line"></i> Markets</a>
                <a href="#"><i class="fas fa-wallet"></i> Wallet</a>
               <div class="dropdown">
  <a href="#"><i class="fas fa-user"></i> Account <i class="fas fa-caret-down"></i></a>
  <div class="dropdown-content" style="background-color: black;">
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  </div>
</div>
    
</nav>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');

    menuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('open');
    });
});
</script>
<style>
    /* Hide menu by default */
.mobile-menu {
    display: none;
    position: absolute;
    top: 60px; /* adjust based on your header height */
    left: 0;
    width: 100%;
    background-color: #fff;
    z-index: 999;
}

/* Show menu when .open class is added */
.mobile-menu.open {
    display: block;
}

.mobile-menu .nav-links {
    display: flex;
    flex-direction: column;
    padding: 1rem;
}

.mobile-menu .nav-links a {
    
    text-decoration: none;
    color: white;
}

.dropdown-content {
    display: none;
    flex-direction: column;
    margin-left: 1rem;
}

.dropdown:hover .dropdown-content {
    display: flex;
}
@media (min-width: 768px) {
    .mobile-menu-btn {
        display: none;
    }

    .mobile-menu {
        display: none !important; /* Hide mobile menu on desktop */
    }
 
}

</style>
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1 class="animate one">
                    <span>I</span><span>n</span><span>s</span><span>t</span><span>a</span><span>n</span><span>t</span>
                    <span> </span>
                    <span>C</span><span>r</span><span>y</span><span>p</span><span>t</span><span>o</span>
                    <span> </span>
                    <span>T</span><span>r</span><span>a</span><span>d</span><span>i</span><span>n</span><span>g</span>
                    <span> </span>
                    <span>P</span><span>l</span><span>a</span><span>t</span><span>f</span><span>o</span><span>r</span><span>m</span>
                </h1>
                <p>Buy and sell USDT with INR instantly using our secure, lightning-fast exchange platform.</p>
                <div class="cta-buttons">
                    <a href="/User-Registration.html"><button class="btn btn-primary">
                            <i class="fas fa-rocket"></i>
                            Get Started
                        </button></a>
                    <button class="btn btn-secondary">
                        <i class="fas fa-download"></i>
                        Download App
                    </button>
                </div>
            </div>
            <div class="price-ticker">
                <div class="rupeee">
                    <img src="uploads/golden-coin.png" alt="rupee-img">
                </div>
                <div class="price">₹83.24</div>
                <p>Current USDT/INR Price</p>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="features-container">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="feature-content">
                    <h3>Instant Trading</h3>
                    <p>Our advanced trading engine is built to deliver blazing-fast execution with sub-second latency,
                        ensuring your trades are placed the moment you hit the button. Whether you're executing a
                        market, limit, or stop-loss order, our system processes it in real-time—even during volatile
                        market conditions. Take advantage of our seamless trading interface to make informed decisions
                        and respond instantly to price movements across all major cryptocurrencies and tokens.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-content">
                    <h3>Secure Storage</h3>
                    <p>Security is our top priority. We implement multi-layered protection that includes military-grade
                        AES-256 encryption, two-factor authentication (2FA), biometric verification, and offline cold
                        wallet storage systems to guard user funds. More than 95% of our assets are stored in
                        geographically distributed cold wallets, making them virtually inaccessible to attackers. Our
                        systems undergo regular penetration testing and are compliant with global cybersecurity
                        standards to give you complete peace of mind.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="feature-content">
                    <h3>Low Fees</h3>
                    <p>We believe in fair and transparent pricing. Our platform charges a flat 0.1% trading fee—one of
                        the lowest in the industry—without any hidden charges. Whether you're a casual investor or a
                        high-frequency trader, our fee structure is designed to maximize your returns. Enjoy additional
                        discounts and rewards by using our native token or participating in our loyalty and referral
                        programs. The more you trade, the more you save.</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="feature-content">
                    <h3>24/7 Support</h3>
                    <p>We know that markets never sleep—and neither do we. Our dedicated support team is available 24/7
                        via live chat, email, and ticketing systems to assist with anything you need. Whether you're
                        facing a technical issue, need help with a transaction, or simply have a question, our
                        experienced team is here to help. We also provide an in-depth knowledge base, tutorials, and
                        video guides so you can navigate the platform confidently at any time.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="faq-container">
            <h2 class="faq-title">FAQ</h2>

            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">What is Goldpay?</div>
                    <div class="faq-answer">
                        Goldpay is a leading cryptocurrency exchange platform that allows you to buy and sell USDT
                        with INR instantly. We provide a secure, fast, and user-friendly platform for all your crypto
                        trading needs with competitive rates and low fees.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Do I need to download an app?</div>
                    <div class="faq-answer">
                        While we do offer a mobile app for both iOS and Android devices for your convenience, it's not
                        mandatory. You can access all our services through our fully responsive website on any device
                        with a web browser.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">What are the advantages of using Goldpay over your competitors?</div>
                    <div class="faq-answer">
                        Goldpay offers several advantages including instant transactions, industry-low fees (0.1%),
                        military-grade security, 24/7 customer support, and a user-friendly interface. We also provide
                        better liquidity and more competitive rates than most competitors.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">How can I invite my friends and family, or pay someone?</div>
                    <div class="faq-answer">
                        You can easily invite friends through our referral program (find it in your account dashboard).
                        For payments, simply go to the 'Send' section, enter the recipient's wallet address or phone
                        number, specify the amount, and confirm the transaction.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-column">
                <h3>Goldpay</h3>
                <p>support@Goldpay.in</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                </div>
            </div>

            <div class="footer-column">
                <h3>Legal</h3>
                <ul>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                    <li><a href="privacy-policey.php">Privacy Policy</a></li>
                     <li><a href="Faq.php">Faq</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Newsletter</h3>
                <p>Stay up to date</p>
                <div class="newsletter-input">
                    <input type="email">
                    <div class="line-hr"></div>
                    <button>Join</button>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-logo">
                <img src="../User_dashboard/image/logo.png" alt="Goldpay Logo">
            </div>
            <p class="copyright">© 2026 Goldpay. All Rights Reserved.</p>
        </div>
    </footer>

 <script>
  document.addEventListener('DOMContentLoaded', () => {
    // === Utility: Set textContent safely ===
    function setText(selector, text) {
      const el = document.querySelector(selector);
      if (el) el.textContent = text;
      else console.warn(`Missing element for selector: ${selector}`);
    }

    function setMultipleText(selectorsTexts) {
      selectorsTexts.forEach(({ selector, text }) => setText(selector, text));
    }

    // === Translation Functions ===
    function translateToHindi() {
      try {
        setMultipleText([
          { selector: '.hero-content h1', text: 'तुरंत क्रिप्टो ट्रेडिंग प्लेटफॉर्म' },
          { selector: '.hero-content p', text: 'हमारे सुरक्षित, बिजली-तेज एक्सचेंज प्लेटफॉर्म का उपयोग करके INR के साथ USDT को तुरंत खरीदें और बेचें।' },
          { selector: '.hero-content .btn-primary span', text: 'शुरू करें' },
          { selector: '.hero-content .btn-secondary span', text: 'ऐप डाउनलोड करें' },
          { selector: '.price-ticker p', text: 'वर्तमान USDT/INR मूल्य' },
          { selector: '.faq-title', text: 'सामान्य प्रश्न' },
          { selector: '.newsletter-input button', text: 'जुड़ें' },
          { selector: '.copyright', text: '© 2025 डॉलररियो. सर्वाधिकार सुरक्षित।' },
        ]);

        const features = document.querySelectorAll('.feature-content');
        if (features.length >= 4) {
          features[0].querySelector('h3').textContent = 'तुरंत ट्रेडिंग';
          features[0].querySelector('p').textContent = 'हमारा उन्नत ट्रेडिंग इंजन उप-सेकंड विलंबता के साथ ब्लेज़िंग-फास्ट निष्पादन प्रदान करता है।';

          features[1].querySelector('h3').textContent = 'सुरक्षित भंडारण';
          features[1].querySelector('p').textContent = 'हम बहुस्तरीय सुरक्षा लागू करते हैं जिससे आपके फंड पूरी तरह सुरक्षित रहते हैं।';

          features[2].querySelector('h3').textContent = 'कम फीस';
          features[2].querySelector('p').textContent = 'हम निष्पक्ष और पारदर्शी मूल्य निर्धारण में विश्वास करते हैं जिससे आप अधिक कमा सकें।';

          features[3].querySelector('h3').textContent = '24/7 समर्थन';
          features[3].querySelector('p').textContent = 'हमारी सहायता टीम 24/7 उपलब्ध है ताकि आपके सभी सवालों का समाधान तुरंत हो।';
        }

        const faqQ = document.querySelectorAll('.faq-question');
        const faqA = document.querySelectorAll('.faq-answer');
        if (faqQ.length >= 4 && faqA.length >= 4) {
          faqQ[0].textContent = 'डॉलररियो क्या है?';
          faqA[0].textContent = 'डॉलररियो INR के साथ USDT को तुरंत खरीदने और बेचने का प्लेटफॉर्म है।';

          faqQ[1].textContent = 'मैं खाता कैसे बनाऊं?';
          faqA[1].textContent = "बस 'शुरू करें' पर क्लिक करें, रजिस्ट्रेशन फॉर्म भरें और वेरिफिकेशन पूरा करें।";

          faqQ[2].textContent = 'क्या मेरी जानकारी सुरक्षित है?';
          faqA[2].textContent = 'हां, हम आपके डेटा की सुरक्षा को सर्वोच्च प्राथमिकता देते हैं।';

          faqQ[3].textContent = 'मैं सहायता कैसे प्राप्त करूं?';
          faqA[3].textContent = 'हमारी 24/7 सहायता टीम से संपर्क करें – हम आपकी मदद को सदैव तैयार हैं।';
        }

        const footers = document.querySelectorAll('.footer-column');
        if (footers.length >= 3) {
          footers[0].querySelector('h3').textContent = 'डॉलररियो';
          footers[1].querySelector('h3').textContent = 'कानूनी';
          const links = footers[1].querySelectorAll('li a');
          if (links.length >= 2) {
            links[0].textContent = 'उपयोग की शर्तें';
            links[1].textContent = 'गोपनीयता नीति';
          }
          footers[2].querySelector('h3').textContent = 'न्यूज़लेटर';
          footers[2].querySelector('p').textContent = 'अद्यतित रहें';
        }
      } catch (err) {
        console.warn("Hindi translation error:", err);
      }
    }

    function translateToEnglish() {
      try {
        setMultipleText([
          { selector: '.hero-content h1', text: 'Instant Crypto Trading Platform' },
          { selector: '.hero-content p', text: 'Buy and sell USDT with INR instantly using our secure, lightning-fast exchange platform.' },
          { selector: '.hero-content .btn-primary span', text: 'Get Started' },
          { selector: '.hero-content .btn-secondary span', text: 'Download App' },
          { selector: '.price-ticker p', text: 'Current USDT/INR Price' },
          { selector: '.faq-title', text: 'Frequently Asked Questions' },
          { selector: '.newsletter-input button', text: 'Join' },
          { selector: '.copyright', text: '© 2026 Goldpay. All Rights Reserved.' },
        ]);

        const features = document.querySelectorAll('.feature-content');
        if (features.length >= 4) {
          features[0].querySelector('h3').textContent = 'Instant Trading';
          features[0].querySelector('p').textContent = 'Our advanced trading engine offers blazing-fast execution with sub-second latency.';

          features[1].querySelector('h3').textContent = 'Secure Storage';
          features[1].querySelector('p').textContent = 'We implement multi-layered security to ensure your funds are always safe.';

          features[2].querySelector('h3').textContent = 'Low Fees';
          features[2].querySelector('p').textContent = 'We believe in fair, transparent pricing so you can keep more of your earnings.';

          features[3].querySelector('h3').textContent = '24/7 Support';
          features[3].querySelector('p').textContent = 'Our support team is available 24/7 to help you whenever you need.';
        }

        const faqQ = document.querySelectorAll('.faq-question');
        const faqA = document.querySelectorAll('.faq-answer');
        if (faqQ.length >= 4 && faqA.length >= 4) {
          faqQ[0].textContent = 'What is Dollario?';
          faqA[0].textContent = 'Dollario is a platform to instantly buy and sell USDT with INR.';

          faqQ[1].textContent = 'How do I create an account?';
          faqA[1].textContent = "Just click on 'Get Started', fill out the registration form, and complete verification.";

          faqQ[2].textContent = 'Is my data secure?';
          faqA[2].textContent = 'Yes, we prioritize data privacy and use advanced encryption.';

          faqQ[3].textContent = 'How can I get support?';
          faqA[3].textContent = "Contact our 24/7 support team — we're always here to help.";
        }

        const footers = document.querySelectorAll('.footer-column');
        if (footers.length >= 3) {
          footers[0].querySelector('h3').textContent = 'Dollario';
          footers[1].querySelector('h3').textContent = 'Legal';
          const links = footers[1].querySelectorAll('li a');
          if (links.length >= 2) {
            links[0].textContent = 'Terms of Use';
            links[1].textContent = 'Privacy Policy';
          }
          footers[2].querySelector('h3').textContent = 'Newsletter';
          footers[2].querySelector('p').textContent = 'Stay Updated';
        }
      } catch (err) {
        console.warn("English translation error:", err);
      }
    }

    // === Language Setup ===
    const hindiLangBtn = document.getElementById('hindi-lang');
    const englishLangBtn = document.getElementById('english-lang');
    const currentLanguage = localStorage.getItem('language') || 'en';

    if (currentLanguage === 'hi') {
      translateToHindi();
      hindiLangBtn?.classList.add('active');
      englishLangBtn?.classList.remove('active');
    } else {
      translateToEnglish();
      englishLangBtn?.classList.add('active');
      hindiLangBtn?.classList.remove('active');
    }

    hindiLangBtn?.addEventListener('click', () => {
      localStorage.setItem('language', 'hi');
      translateToHindi();
      hindiLangBtn.classList.add('active');
      englishLangBtn?.classList.remove('active');
    });

    englishLangBtn?.addEventListener('click', () => {
      localStorage.setItem('language', 'en');
      translateToEnglish();
      englishLangBtn.classList.add('active');
      hindiLangBtn?.classList.remove('active');
    });

    // ===== Theme Toggle =====
    const darkThemeBtn = document.getElementById('dark-theme');
    const lightThemeBtn = document.getElementById('light-theme');

    function applyTheme(theme) {
      if (theme === 'light') {
        document.body.classList.add('light-theme');
        darkThemeBtn?.classList.remove('active');
        lightThemeBtn?.classList.add('active');
      } else {
        document.body.classList.remove('light-theme');
        lightThemeBtn?.classList.remove('active');
        darkThemeBtn?.classList.add('active');
      }
    }

    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);

    darkThemeBtn?.addEventListener('click', () => {
      localStorage.setItem('theme', 'dark');
      applyTheme('dark');
    });

    lightThemeBtn?.addEventListener('click', () => {
      localStorage.setItem('theme', 'light');
      applyTheme('light');
    });
  });
</script>




</body>

</html>