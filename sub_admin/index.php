<?php
// index.php
$pageTitle = "DollaRio - Crypto Exchange";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #10b981;
            --background: #0f172a;
            --surface: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
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
            color: #D4AF37;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            margin-top: 30px;
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
            background: #D4AF37;
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

        :root {
            --accent: white;
            --gold: #D4AF37;
        }

        .price {
            position: relative;
            padding: 10px 20px;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent);
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

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
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
                <a href="#"><i class="fas fa-user"></i> Register</a>
            </div>
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Instant Crypto Trading Platform</h1>
                <p>Buy and sell USDT with INR instantly using our secure, lightning-fast exchange platform.</p>
                <div class="cta-buttons">
                    <a href="/User-Registration.php"><button class="btn btn-primary">
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

    <script>
        // Mobile Menu Toggle
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });

        // Live Price Simulation
        let currentPrice = 83.24;
        const priceElement = document.querySelector('.price');

        setInterval(() => {
            const change = (Math.random() - 0.5) * 2;
            currentPrice += change;
            priceElement.textContent = ₹${currentPrice.toFixed(2)};
        }, 3000);

        // Close mobile menu on click outside
        document.addEventListener('click', (e) => {
            if (!navLinks.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                navLinks.classList.remove('active');
            }
        });
    </script>
</body>

</html>
