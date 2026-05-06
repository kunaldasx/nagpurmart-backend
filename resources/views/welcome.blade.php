<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hyperlocal - Multivendor Delivery Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #036fd1;
            --primary-dark: #025aa8;
            --primary-light: #1e88e5;
            --accent: #ff6b35;
            --dark: #0a1628;
            --gray-dark: #1a2332;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --white: #ffffff;
            --gradient: linear-gradient(135deg, #036fd1 0%, #0288d1 100%);
            /*--gradient-warm: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);*/
            --gradient-warm: linear-gradient(135deg, #FFC107 0%, #FF9800 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(to bottom, #0a1628 0%, #0f1f3a 50%, #1a2d4d 100%);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(3, 111, 209, 0.3);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(50px);
                opacity: 0;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        header {
            padding: 30px 0;
            animation: slideDown 0.6s ease-out;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--white);
            text-decoration: none;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 8px 24px rgba(3, 111, 209, 0.4);
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 60px 0 80px;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .badge {
            display: inline-block;
            padding: 10px 24px;
            background: rgba(3, 111, 209, 0.15);
            border: 1px solid rgba(3, 111, 209, 0.3);
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-light);
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 64px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #ffffff 0%, #a8d5ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 20px;
            color: var(--gray-light);
            max-width: 700px;
            margin: 0 auto 40px;
            line-height: 1.6;
            font-weight: 400;
        }

        .features-list {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 50px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 500;
            color: var(--white);
        }

        .feature-icon {
            width: 35px;
            height: 35px;
            background: var(--gradient);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Links Grid */
        .links-section {
            padding: 40px 0 80px;
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }

        .link-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 32px;
            text-decoration: none;
            color: var(--white);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .link-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .link-card:hover::before {
            opacity: 0.1;
        }

        .link-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 20px 60px rgba(3, 111, 209, 0.3);
        }

        .link-card-content {
            position: relative;
            z-index: 1;
        }

        .link-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(3, 111, 209, 0.4);
        }

        .link-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            font-family: 'Poppins', sans-serif;
        }

        .link-description {
            font-size: 15px;
            color: var(--gray-light);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .link-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--gradient);
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            color: white;
            cursor: pointer;
        }

        .buy-now-button {
            background: var(--gradient-warm) !important;
            text-decoration: none;
        }

        .link-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(3, 111, 209, 0.5);
        }

        .buy-now-button:hover {
            box-shadow: 0 8px 20px rgb(255 193 7 / 30%) !important;
        }

        .link-card:hover .link-button {
            transform: translateX(4px);
            box-shadow: 0 8px 20px rgba(3, 111, 209, 0.5);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 60px 0 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 40px;
        }

        .footer-text, .footer-text a {
            font-size: 15px;
            color: var(--gray);
            margin-bottom: 12px;
        }

        .developer-credit {
            font-size: 14px;
            opacity: 0.8;
        }

        .developer-credit a {
            color: var(--primary-light);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .developer-credit a:hover {
            color: var(--white);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-link {
            color: var(--gray-light);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: var(--primary-light);
        }

        /* Animations */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 42px;
            }

            .hero-subtitle {
                font-size: 18px;
            }

            .features-list {
                gap: 20px;
            }

            .feature-item {
                font-size: 14px;
            }

            .links-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 28px;
            }
        }

        /* Glow effect */
        .glow {
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(3, 111, 209, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .button-group {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 40px;
        }

        .glow-1 {
            top: -200px;
            left: -200px;
            animation: pulse 8s infinite ease-in-out;
        }

        .glow-2 {
            bottom: -200px;
            right: -200px;
            animation: pulse 8s infinite ease-in-out 4s;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 0.6;
                transform: scale(1.1);
            }
        }
        header{
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* App Store Buttons */
        .app-buttons {
            display: flex;
            gap: 16px;
            justify-content: start;
            align-items: start;
        }

        .store-button {
            display: inline-block;
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0.95;
        }

        .store-button:hover {
            transform: translateY(-4px);
            opacity: 1;
        }

        .store-badge {
            max-height: 40px;
            display: block;
        }

        @media (max-width: 480px) {
            .app-buttons {
                flex-direction: column;
                gap: 12px;
            }

            .store-badge {
                height: 45px;
            }
        }
    </style>
</head>
<body>
<!-- Background particles -->
<div class="particles" id="particles"></div>

<!-- Glow effects -->
<div class="glow glow-1"></div>
<div class="glow glow-2"></div>

<div class="container">
    <!-- Header -->
    <header>
        <a href="#" class="logo">
            <img
                src="{{!empty($systemSettings['logo'])?$systemSettings['logo'] : asset('logos/hyper-local-logo.png')}}"
                alt="{{$systemSettings['appName'] ?? ""}}" width="150px">
        </a>
        <a href="https://1.envato.market/APdKBR" target="_blank" class="link-button buy-now-button">
            Buy Now
            <span>→</span>
        </a>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="badge">🚀 CodeCanyon Premium Product</div>
        <h1>Launch Your Own<br>Hyperlocal Platform</h1>
        <p class="hero-subtitle">
            Build a complete multivendor delivery platform that connects local stores, customers,
            and delivery partners in one powerful ecosystem. Flutter apps, NextJS website & Laravel admin panel.
        </p>

        <div class="features-list" style="margin-bottom: 16px">
            <div class="feature-item">
                <div class="feature-icon">🛍️</div>
                <span>Grocery & Food</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🏥</div>
                <span>Pharmacy</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">👓</div>
                <span>Fashion</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🎧</div>
                <span>Electronics</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon">🏪</div>
                <span>Local Retail</span>
            </div>

        </div>
        <div class="features-list">
            <span>& many more business types — whatever your local market needs.</span>
        </div>
        <div class="button-group">
            <div>
                <a href="https://1.envato.market/APdKBR" target="_blank" class="link-button buy-now-button">
                    Buy Now
                    <span>→</span>
                </a>
            </div>
            <a href="https://wa.me/918799587762" target="_blank" class="link-button" style="text-decoration: none">
                Talk to Us
                <span>→</span>
            </a>
        </div>
    </section>

    <!-- Links Section -->
    <section class="links-section">
        <h2 class="section-title">Access Your Platform</h2>

        <div class="links-grid">
            <!-- Customer Website -->
            <a href="https://hyperlocal.eshopweb.store" target="_blank" rel="noopener noreferrer" class="link-card">
                <div class="link-card-content">
                    <div class="link-icon">🌐</div>
                    <h3 class="link-title">Customer Website</h3>
                    <p class="link-description">
                        Browse products, place orders, and track deliveries from your favorite local stores.
                    </p>
                    <div class="link-button">
                        Visit Website
                        <span>→</span>
                    </div>
                </div>
            </a>

            <!-- Customer Mobile App -->
            <div class="link-card">
                <div class="link-card-content">
                    <div class="link-icon">📱</div>
                    <h3 class="link-title">Customer App</h3>
                    <p class="link-description">
                        Download the Flutter mobile app for iOS and Android. Shop on-the-go with ease.
                    </p>
                    <div class="app-buttons">
                        <a href="https://play.google.com/store/apps/details?id=com.hyperLocal.customer" target="_blank" rel="noopener noreferrer" class="store-button">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play" class="store-badge">
                        </a>
                        <a href="https://testflight.apple.com/join/NmyU7Sa6" target="_blank" rel="noopener noreferrer" class="store-button">
                            <img src="https://tools.applemediaservices.com/api/badges/download-on-the-app-store/black/en-us?size=250x83&amp;releaseDate=1301616000" alt="Download on the App Store" class="store-badge">
                        </a>
                    </div>
                </div>
            </div>

            <!-- Vendor Website -->
            <a href="https://hyperlocal-backend.eshopweb.store/seller/login" target="_blank" rel="noopener noreferrer"
               class="link-card">
                <div class="link-card-content">
                    <div class="link-icon">🏬</div>
                    <h3 class="link-title">Seller Dashboard</h3>
                    <p class="link-description">
                        Manage your store, products, orders, and analytics from the seller portal.
                    </p>
                    <div class="link-button">
                        Seller Login
                        <span>→</span>
                    </div>
                </div>
            </a>

            <!-- Delivery Partner App -->
            <div class="link-card">
                <div class="link-card-content">
                    <div class="link-icon">🛵</div>
                    <h3 class="link-title">Delivery Partner App</h3>
                    <p class="link-description">
                        Real-time order tracking and navigation for delivery partners. Accept and complete deliveries.
                    </p>
                    <div class="app-buttons">
                        <a href="https://play.google.com/store/apps/details?id=com.hyperlocal.partner" target="_blank" rel="noopener noreferrer" class="store-button">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play" class="store-badge">
                        </a>
                        <a href="https://testflight.apple.com/join/tmK8cP9Y" target="_blank" rel="noopener noreferrer" class="store-button">
                            <img src="https://tools.applemediaservices.com/api/badges/download-on-the-app-store/black/en-us?size=250x83&amp;releaseDate=1301616000" alt="Download on the App Store" class="store-badge">
                        </a>
                    </div>
                </div>
            </div>

            <!-- Admin Panel -->
            <a href="https://hyperlocal-backend.eshopweb.store/admin/login" target="_blank" rel="noopener noreferrer"
               class="link-card">
                <div class="link-card-content">
                    <div class="link-icon">⚙️</div>
                    <h3 class="link-title">Admin Panel</h3>
                    <p class="link-description">
                        Laravel-powered admin dashboard to manage the entire platform, users, and transactions.
                    </p>
                    <div class="link-button">
                        Admin Access
                        <span>→</span>
                    </div>
                </div>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p class="footer-text">
            © {{ date('Y') }} Hyperlocal Platform. All rights reserved. | Available on <a
                href="https://1.envato.market/2aoKVD" target="_blank">CodeCanyon</a>
        </p>
        <p class="footer-text developer-credit">
            Designed & Developed with ❤️ by <a href="https://infinitietech.com" target="_blank" rel="noopener">Infinitietech</a>
        </p>
    </footer>
</div>

<script>
    // Create animated particles
    const particlesContainer = document.getElementById('particles');
    const particleCount = 50;

    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 20 + 's';
        particle.style.animationDuration = (15 + Math.random() * 10) + 's';
        particlesContainer.appendChild(particle);
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.link-card').forEach(card => {
        observer.observe(card);
    });
</script>
</body>
</html>
