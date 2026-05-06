<!DOCTYPE html>
<html lang="en">
<head>
    <link
        rel="icon"
        type="image/png"
        sizes="32x32"
        href="{{asset('assets/landing/site-favicon.png')}}"
    />

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Hyperlocal - Multivendor eCommerce, Grocery, Food, Pharmacy Flutter Delivery app - Admin & Website</title>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@300;400;500;600;700&family=Source+Sans+3:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    />

    <!-- Tailwind CDN (temporary - replace with output.css after running CLI) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ["Lexend Deca", "sans-serif"],
                        body: ["Source Sans 3", "sans-serif"],
                    },
                },
            },
        };
    </script>

    <!-- Meta Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '980868264018226');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
                   src="https://www.facebook.com/tr?id=980868264018226&ev=PageView&noscript=1"
        /></noscript>
    <!-- End Meta Pixel Code -->

    <!-- Smooth Scrolling -->
    <style>
        html {
            scroll-behavior: smooth;
        }
        /* Mobile Menu Styles */
        .mobile-menu {
            display: none;
        }
        .mobile-menu.active {
            display: flex;
        }
        .store-badge {
            max-height: 40px;
            display: block;
        }
        @media (max-width: 480px) {
            .store-badge {
                height: 45px;
            }
        }
    </style>
</head>

<body class="bg-black text-white font-body">
<!-- NAVBAR -->
<header class="absolute top-0 left-0 w-full z-20">
    <div
        class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between"
    >
        <!-- Logo -->
        <div class="flex items-center gap-2 font-heading font-semibold text-lg">
            <img
                src="{{asset('assets/landing/images/logo.webp')}}"
                alt="Logo"
                class="h-12 sm:h-14 md:h-16"
            />
        </div>

        <!-- Mobile Menu Button -->
        <button
            id="mobile-menu-btn"
            class="md:hidden text-white p-2"
            aria-label="Toggle menu"
        >
            <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                />
            </svg>
        </button>

        <!-- Nav Links -->
        <nav class="hidden md:flex items-center gap-8 text-lg text-gray-300">
            <a href="#modules" class="hover:text-white" title="View our modules"
            >Modules</a
            >
            <a href="#features" class="hover:text-white" title="Explore features"
            >Features</a
            >
            <a
                href="#customization"
                class="hover:text-white"
                title="Learn about customization"
            >Customization</a
            >
            <a
                href="https://docs-hyper-local.vercel.app/"
                target="_blank"
                class="hover:text-white"
                title="Check resources and technology"
            >Resources</a
            >
        </nav>

        <!-- CTA -->
        <a
            target="_blank"
            href="https://codecanyon.net/item/hyperlocal-multivendor-delivery-platform-flutter-mobile-apps-nextjs-website-laravel-admin-panel/61119699&ref=infinitietech"
            class="hidden md:inline-block bg-white text-black font-semibold px-6 lg:px-10 py-2 rounded-lg text-base lg:text-lg"
            title="Purchase Hyperlocal on CodeCanyon"
        >
            BUY NOW
        </a>
    </div>

    <!-- Mobile Menu -->
    <div
        id="mobile-menu"
        class="mobile-menu md:hidden absolute top-full left-0 w-full bg-black/95 backdrop-blur-sm flex-col py-4"
    >
        <nav class="flex flex-col gap-4 px-6">
            <a
                href="#modules"
                class="text-gray-300 hover:text-white py-2 border-b border-gray-800"
                title="View our modules"
            >Modules</a
            >
            <a
                href="#features"
                class="text-gray-300 hover:text-white py-2 border-b border-gray-800"
                title="Explore features"
            >Features</a
            >
            <a
                href="#customization"
                class="text-gray-300 hover:text-white py-2 border-b border-gray-800"
                title="Learn about customization"
            >Customization</a
            >
            <a
                href="https://docs-hyper-local.vercel.app/"
                target="_blank"
                class="text-gray-300 hover:text-white py-2 border-b border-gray-800"
                title="Check resources and technology"
            >Resources</a
            >
            <a
                target="_blank"
                href="https://codecanyon.net/item/hyperlocal-multivendor-delivery-platform-flutter-mobile-apps-nextjs-website-laravel-admin-panel/61119699"
                class="bg-white text-black font-semibold px-6 py-2 rounded-lg text-center mt-2"
                title="Purchase Hyperlocal on CodeCanyon"
            >
                BUY NOW
            </a>
        </nav>
    </div>
</header>

<!-- HERO SECTION -->
<section
    class="relative min-h-screen flex items-center justify-center text-center
           bg-cover bg-center
           bg-[url('{{ asset('assets/landing/images/hero-bg-mobile.png') }}')]
           md:bg-[url('{{ asset('assets/landing/images/hero-bg.png') }}')]"
>
    <!-- Content -->
    <div
        class="relative z-10 max-w-4xl -translate-y-[12vh] md:-translate-y-[10vh] px-4 sm:px-6"
    >
        <h1
            class="font-heading text-2xl sm:text-4xl md:text-5xl font-bold leading-tight sm:leading-snug md:leading-normal"
        >
            Start a Local Online Delivery <br class="hidden sm:block" />
            Business in Your Area
        </h1>

        <p
            class="mt-4 sm:mt-6 text-gray-300 text-xs sm:text-base md:text-lg font-body leading-relaxed"
        >
            Launch your own local delivery platform that connects nearby stores,
            delivery partners, and customers in one place. Stores list products,
            customers place orders, and delivery partners handle deliveries — all
            managed smoothly through the system.
        </p>
        <a
            href="#modules"
            title="Download our customer app from Google Play Store"
        >
            <button
                class="mt-6 sm:mt-8 px-6 sm:px-8 py-2.5 sm:py-3 rounded-full bg-blue-600 hover:bg-blue-700 transition font-medium text-white text-sm sm:text-base"
            >
                Explore Demo
            </button></a
        >
    </div>
</section>

<!-- OUR MODULES SECTION -->
<section id="modules" class="py-12 sm:py-16 md:py-20 bg-white text-black">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Section Header -->
        <div class="text-center max-w-3xl mx-auto">
          <span
              class="inline-block px-4 py-1 text-xs sm:text-sm border border-blue-600 text-blue-600 rounded-full font-medium"
          >
            Our modules
          </span>

            <h2
                class="mt-4 sm:mt-6 text-2xl sm:text-3xl md:text-4xl font-heading font-bold leading-tight"
            >
                Everything You Need to Launch <br class="hidden sm:block" />
                a Hyperlocal Marketplace
            </h2>

            <p class="mt-3 sm:mt-4 text-sm sm:text-base text-gray-600 font-body">
                Connect nearby stores, delivery partners, and customers — all
                through one ready-to-launch hyperlocal platform.
            </p>
        </div>

        <!-- Cards Grid -->
        <div
            class="mt-8 sm:mt-10 md:mt-14 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8"
        >
            <!-- Customer App -->
            <div
                class="border rounded-xl p-4 sm:p-6 bg-white shadow-sm flex flex-col"
            >
                <h3 class="font-heading font-semibold text-base sm:text-lg">
                    Customer App
                </h3>
                <p class="mt-2 xs:text-xs text-gray-600 font-body leading-relaxed">
                    A fast and simple app where customers can browse, order, and track deliveries instantly.
                </p>

                <div
                    class="mt-4 sm:mt-6 h-40 sm:h-48 w-full overflow-hidden rounded-lg"
                >
                    <img
                        src="{{asset('assets/landing/images/customer-app.svg')}}"
                        alt="Customer App"
                        class="h-full w-full object-cover object-top"
                    />
                </div>

                <!-- Footer -->
                <div
                    class="mt-auto flex justify-between items-center border-t pt-3 text-sm font-medium text-blue-600"
                >
                    <a
                        href="https://play.google.com/store/apps/details?id=com.hyperLocal.customer"
                        class="flex-1 text-center hover:underline py-2 flex justify-center"
                        target="_blank"
                        title="Download Customer Android App"
                    >
                        <img src="{{asset('assets/landing/images/playstore.png')}}" alt="Get it on Google Play" class="store-badge">
                    </a>
                    <div class="w-px h-4 bg-gray-300"></div>
                    <a
                        href="https://testflight.apple.com/join/NmyU7Sa6"
                        class="flex-1 text-center hover:underline py-2 flex justify-center"
                        target="_blank"
                        title="Download Customer iOS App (TestFlight)"
                    >
                        <img src="{{asset('assets/landing/images/appstore.png')}}" alt="Download on the App Store" class="store-badge">
                    </a>
                </div>
            </div>

            <!-- Customer Web -->
            <div
                class="border rounded-xl p-4 sm:p-6 bg-white shadow-sm flex flex-col"
            >
                <h3 class="font-heading font-semibold text-base sm:text-lg">
                    Customer Web
                </h3>
                <p class="mt-2 xs:text-xs text-gray-600 font-body leading-relaxed">
                    A responsive website where customers can explore nearby stores, place orders, and track deliveries with ease.
                </p>

                <div
                    class="mt-4 sm:mt-6 h-40 sm:h-48 w-full overflow-hidden rounded-lg"
                >
                    <img
                        src="{{asset('assets/landing/images/customer-web.svg')}}"
                        alt="Customer Web"
                        class="h-full w-full object-cover object-top"
                    />
                </div>

                <div class="mt-auto border-t pt-3 text-center">
                    <a
                        href="https://hyperlocal.eshopweb.store"
                        target="_blank"
                        class="text-sm font-medium text-blue-600 hover:underline py-2 inline-block"
                        title="View Customer Web Demo"
                    >
                        <img src="{{asset('assets/landing/images/checkdemo.png')}}" alt="View Customer Web Demo" class="store-badge">
                    </a>
                </div>
            </div>

            <!-- Admin Panel -->
            <div
                class="border rounded-xl p-4 sm:p-6 bg-white shadow-sm flex flex-col"
            >
                <h3 class="font-heading font-semibold text-base sm:text-lg">
                    Admin Panel
                </h3>
                <p class="mt-2 xs:text-xs text-gray-600 font-body leading-relaxed">
                    A powerful dashboard to manage stores, products, orders, users, and platform operations from one place.
                </p>

                <div
                    class="mt-4 sm:mt-6 h-40 sm:h-48 w-full overflow-hidden rounded-lg"
                >
                    <img
                        src="{{asset('assets/landing/images/admin-panel.svg')}}"
                        alt="Admin Panel"
                        class="h-full w-full object-cover object-top"
                    />
                </div>

                <div class="mt-auto border-t pt-3 text-center">
                    <a
                        href="https://hyperlocal-backend.eshopweb.store/admin"
                        class="text-sm font-medium text-blue-600 hover:underline py-2 inline-block"
                        target="_blank"
                        title="View Admin Panel Demo"
                    >
                        <img src="{{asset('assets/landing/images/checkdemo.png')}}" alt="View Admin Panel Demo" class="store-badge">
                    </a>
                </div>
            </div>

            <!-- Seller Panel -->
            <div
                class="border rounded-xl p-4 sm:p-6 bg-white shadow-sm flex flex-col"
            >
                <h3 class="font-heading font-semibold text-base sm:text-lg">
                    Seller Panel
                </h3>
                <p class="mt-2 xs:text-xs text-gray-600 font-body leading-relaxed">
                    An easy-to-use panel for sellers to manage products, orders, earnings, and store operations efficiently.
                </p>

                <div
                    class="mt-4 sm:mt-6 h-40 sm:h-48 w-full overflow-hidden rounded-lg"
                >
                    <img
                        src="{{asset('assets/landing/images/seller-panel.svg')}}"
                        alt="Seller Panel"
                        class="h-full w-full object-cover object-top"
                    />
                </div>

                <div class="mt-auto border-t pt-3 text-center">
                    <a
                        href="https://hyperlocal-backend.eshopweb.store/seller"
                        class="text-sm font-medium text-blue-600 hover:underline py-2 inline-block"
                        target="_blank"
                        title="View Seller Panel Demo"
                    >
                        <img src="{{asset('assets/landing/images/checkdemo.png')}}" alt="View Seller Panel Demo" class="store-badge">

                    </a>
                </div>
            </div>

            <!-- Rider App -->
            <div
                class="border rounded-xl p-4 sm:p-6 bg-white shadow-sm flex flex-col"
            >
                <h3 class="font-heading font-semibold text-base sm:text-lg">
                    Rider App
                </h3>
                <p class="mt-2 xs:text-xs text-gray-600 font-body leading-relaxed">
                    A smart delivery app for riders to accept orders, navigate routes, and track earnings in real time.
                </p>

                <div
                    class="mt-4 sm:mt-6 h-40 sm:h-48 w-full overflow-hidden rounded-lg"
                >
                    <img
                        src="{{asset('assets/landing/images/rider-app.svg')}}"
                        alt="Rider App"
                        class="h-full w-full object-cover object-top"
                    />
                </div>
                <div
                    class="mt-auto flex justify-between items-center border-t pt-3 text-sm font-medium text-blue-600"
                >
                    <a
                        href="https://play.google.com/store/apps/details?id=com.hyperlocal.partner"
                        class="flex-1 text-center hover:underline py-2 flex justify-center"
                        target="_blank"
                        title="Download Rider Android App"
                    >
                        <img src="{{asset('assets/landing/images/playstore.png')}}" alt="Get it on Google Play" class="store-badge">
                    </a>
                    <div class="w-px h-4 bg-gray-300"></div>
                    <a
                        href="https://testflight.apple.com/join/tmK8cP9Y"
                        class="flex-1 text-center hover:underline py-2 flex justify-center"
                        target="_blank"
                        title="Download Rider iOS App (TestFlight)"
                    >
                        <img src="{{asset('assets/landing/images/appstore.png')}}" alt="Download on the App Store" class="store-badge">
                    </a>
                </div>
            </div>

            <!-- Seller App -->
            <div
                class="border rounded-xl p-4 sm:p-6 bg-white shadow-sm flex flex-col"
            >
                <h3 class="font-heading font-semibold text-base sm:text-lg">
                    Seller App
                </h3>
                <p class="mt-2 xs:text-xs text-gray-600 font-body leading-relaxed">
                    A simple mobile app for sellers to manage orders, update products, track earnings, and run their store on the go.
                </p>

                <div
                    class="mt-4 sm:mt-6 h-40 sm:h-48 w-full overflow-hidden rounded-lg"
                >
                    <img
                        src="{{asset('assets/landing/images/seller-app.svg')}}"
                        alt="Rider App"
                        class="h-full w-full object-cover object-top"
                    />
                </div>
                <div
                    class="mt-auto flex justify-between items-center border-t pt-3 text-sm font-medium text-blue-600"
                >
                    <a
                        href="https://play.google.com/store/apps/details?id=com.hyperLocal.seller&hl=en_IN"
                        class="flex-1 text-center hover:underline py-2 flex justify-center"
                        target="_blank"
                        title="Download Seller Android App"
                    >
                        <img src="{{asset('assets/landing/images/playstore.png')}}" alt="Get it on Google Play" class="store-badge">
                    </a>
                    <div class="w-px h-4 bg-gray-300"></div>
                    <a
                        href="https://testflight.apple.com/join/m7TQEuuX"
                        class="flex-1 text-center hover:underline py-2 flex justify-center"
                        target="_blank"
                        title="Download Seller iOS App (TestFlight)"
                    >
                        <img src="{{asset('assets/landing/images/appstore.png')}}" alt="Download on the App Store" class="store-badge">
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="py-12 sm:py-16 md:py-20 bg-white">
    <div class="max-w-full mx-auto">
        <!-- Section Header -->
        <div class="text-center max-w-full mx-auto">
            <h2
                class="text-2xl sm:text-3xl md:text-4xl font-heading font-bold text-black"
            >
                Powerful Features
            </h2>
            <p class="mt-3 sm:mt-4 text-sm sm:text-base text-gray-600 font-body">
                Smart features that run your hyperlocal business with minimal
                effort.
            </p>
            <img
                src="{{asset('assets/landing/images/features-mobile.svg')}}"
                alt="How Hyperlocal Works"
                class="md:hidden mx-auto block"
            />
            <img
                src="{{asset('assets/landing/images/Features.svg')}}"
                alt="How Hyperlocal Works"
                class="hidden md:block mx-auto h-full"
            />
        <div class="text-center">
            <p class="text-gray-500 font-body text-xs sm:text-sm">& many more</p>
        </div>
        </div>

        <!-- Footer Text -->
    </div>
</section>

<!-- HOW HYPERLOCAL WORKS SECTION -->
<section class="bg-gray-50 py-12 sm:py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Title and Description -->
        <div class="text-center mb-8 sm:mb-10 md:mb-12">
            <h2
                class="font-heading text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4"
            >
                How Hyperlocal Works
            </h2>
            <p
                class="text-gray-600 font-body text-sm sm:text-base md:text-lg max-w-3xl mx-auto"
            >
                A Simple Flow That Runs Your Local Delivery Business
            </p>
        </div>

        <!-- Image -->
        <div class="flex justify-center">
            <!-- Mobile image -->
            <img
                src="{{ asset('assets/landing/images/how-hyperlocal-works-mobile.svg') }}"
                alt="How Hyperlocal Works"
                class="rounded-lg md:hidden"
            />

            <!-- Desktop image -->
            <img
                src="{{ asset('assets/landing/images/how-hyperlocal-works.svg') }}"
                alt="How Hyperlocal Works"
                class="hidden w-full rounded-lg md:block"
            />
        </div>
    </div>
</section>

<!-- STEPS AFTER PURCHASING SECTION -->
<section class="bg-white py-12 sm:py-16 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Title and Description -->
        <div class="text-center mb-8 sm:mb-12 md:mb-16">
            <h2
                class="font-heading text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4"
            >
                Steps After Purchasing
            </h2>
            <p class="text-gray-600 font-body text-sm sm:text-base md:text-lg">
                Get Your Local Delivery Business Live in 4 Simple Steps
            </p>
        </div>

        <!-- Steps Grid -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex justify-center">
            <!-- Mobile -->
            <img
                src="{{ asset('assets/landing/images/steps--mobile.svg') }}"
                class="sm:hidden"
                alt="Steps"
            />

            <!-- Desktop -->
            <img
                src="{{ asset('assets/landing/images/steps-desktop.svg') }}"
                class="hidden w-full h-full sm:block"
                alt="Steps"
            />
        </div>
    </div>
</section>

<!-- NEED HELP SECTION -->
<section
    id="customization"
    class="relative py-12 sm:py-16 md:py-20 text-white"
    style="
        background-image: url('{{asset('assets/landing/images/black-bg.png')}}');
        background-size: cover;
        background-position: center;
      "
>
    <!-- Dark Overlay -->

    <!-- Content -->
    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h2
            class="font-heading text-2xl sm:text-3xl md:text-4xl font-bold mb-4 sm:mb-6 leading-tight"
        >
            Need Help with Setup or Customization?
        </h2>
        <p
            class="text-gray-300 font-body text-sm sm:text-base md:text-lg mb-6 sm:mb-8 max-w-3xl mx-auto leading-relaxed"
        >
            Our professional team helps you with everything needed to go live,
            including system setup, branding, feature configuration, and app store
            submission, so you can focus on growing your business.
        </p>
        <a
            href="https://wa.me/919974692496"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 px-6 sm:px-8 py-2.5 sm:py-3 bg-blue-600 hover:bg-blue-700 transition rounded-lg font-semibold text-white text-sm sm:text-base"
            title="Contact our expert team for setup and customization help"
        >
            TALK TO OUR EXPERT
            <span>→</span>
        </a>
    </div>
</section>

<!-- PRICING / LICENSE COMPARISON SECTION -->
<section class="bg-white py-12 md:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <!-- Title and Description -->
        <div class="text-center mb-8 md:mb-12">
            <h2
                class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 md:mb-4 leading-tight"
            >
                Find the Right Plan for your<br />Business Need
            </h2>
            <p class="text-gray-600 text-base md:text-lg">
                Get Your Local Delivery Business Live in 4 Simple Steps
            </p>
        </div>

        <!-- Comparison Table Wrapper -->
        <div class="overflow-x-auto mb-8 md:mb-12">
            <table
                class="w-full border-collapse border border-gray-200 min-w-[640px]"
            >
                <!-- Table Header -->
                <thead class="bg-gray-50">
                <tr class="border-b-2 border-gray-300">
                    <th
                        class="text-left py-3 md:py-4 px-3 md:px-6 font-semibold text-sm md:text-base text-gray-900 border-r border-gray-200 min-w-[200px]"
                    >
                        Features
                    </th>
                    <th
                        class="text-center py-3 md:py-4 px-3 md:px-6 font-semibold text-sm md:text-base text-gray-900 border-r border-gray-200 min-w-[140px]"
                    >
                        Regular License
                    </th>
                    <th
                        class="text-center py-3 md:py-4 px-3 md:px-6 relative bg-blue-50 min-w-[140px]"
                    >
                        <!-- Blue Ribbon -->
                        <div
                            class="absolute top-0 right-0 w-20 h-20 overflow-hidden pointer-events-none"
                        >
                            <div
                                class="absolute top-4 -right-5 w-24 bg-blue-600 text-white text-center py-1 text-[8px] font-bold tracking-wide transform rotate-45 shadow-md"
                            >
                                Recommended
                            </div>
                        </div>
                        <span
                            class="font-semibold text-sm md:text-base text-gray-900"
                        >
                    Extended License
                  </span>
                    </th>
                </tr>
                </thead>

                <!-- Table Body -->
                <tbody>
                <!-- Row 1 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Lifetime License Validity
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 2 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Permitted for 1 Domain
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 3 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        6 Months of Technical Support
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 4 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        All Premium Features
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 5 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Free Admin Panel Installation (One Time)
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 6 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Remote Support (AnyDesk)
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 7 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        For Personal Project
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 8 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Lifetime Free Updates
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 9 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Postman Collection for Rest API Documentation
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 10 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        For Commercial Projects
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 11 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        1 Year Priority Support
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 12 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        Free Website Setup
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>

                <!-- Row 13 -->
                <tr class="border-b border-gray-200">
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-sm md:text-base text-gray-700 border-r border-gray-200"
                    >
                        1 Logo Design
                    </td>
                    <td
                        class="py-3 md:py-4 px-3 md:px-6 text-center border-r border-gray-200"
                    >
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-red-100 text-red-600 text-lg md:text-xl"
                  >✕</span
                  >
                    </td>
                    <td class="py-3 md:py-4 px-3 md:px-6 text-center bg-blue-50">
                  <span
                      class="inline-flex items-center justify-center w-7 h-7 md:w-8 md:h-8 rounded-full bg-blue-100 text-blue-600 text-lg md:text-xl"
                  >✓</span
                  >
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Bottom CTA Section -->
        <div
            class="bg-blue-50 rounded-xl p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-4 md:gap-6"
        >
            <div class="text-center md:text-left">
                <h3 class="text-lg md:text-xl font-semibold text-gray-900">
                    Your All in one License for Commercial Project
                </h3>
            </div>
            <a
                href="https://codecanyon.net/item/hyperlocal-multivendor-delivery-platform-flutter-mobile-apps-nextjs-website-laravel-admin-panel/61119699?license=extended&ref=infinitietech"
                class="px-6 md:px-8 py-3 bg-blue-600 hover:bg-blue-700 transition-colors rounded-lg font-semibold text-white whitespace-nowrap text-sm md:text-base"
                target="_blank"
                title="Purchase Extended License for commercial projects"
            >
                Get Extended License Now
            </a>
        </div>
    </div>
</section>

<!-- TECHNOLOGY STACK SECTION -->
<section id="technology" class="bg-gray-50 py-12 sm:py-16 md:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <!-- Title and Description -->
        <div class="text-center mb-8 sm:mb-10 md:mb-12">
            <h2
                class="font-heading text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4 leading-tight"
            >
                Built with Modern & Reliable<br class="hidden sm:block" />Technology
            </h2>
            <p
                class="text-gray-600 font-body text-sm sm:text-base md:text-lg max-w-4xl mx-auto"
            >
                Hyperlocal is built with modern, trusted technologies for fast
                performance, strong security, and easy scalability.
            </p>
        </div>

        <!-- Technology Icons Grid -->
        <div
            class="grid grid-cols-3 sm:grid-cols-3 md:grid-cols-6 gap-4 sm:gap-6"
        >
            <!-- Flutter -->
            <a
                href="https://flutter.dev/"
                target="_blank"
                rel="noopener noreferrer"
                title="Learn more about Flutter"
                class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 flex items-center justify-center hover:shadow-lg transition-shadow"
            >
                <img
                    src="{{asset('assets/landing/images/flutter-image.png')}}"
                    alt="Flutter"
                    class="w-full h-10 sm:h-12 md:h-16 object-contain"
                />
            </a>

            <!-- Laravel -->
            <a
                href="https://laravel.com/"
                target="_blank"
                rel="noopener noreferrer"
                title="Learn more about Laravel"
                class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 flex items-center justify-center hover:shadow-lg transition-shadow"
            >
                <img
                    src="{{asset('assets/landing/images/laravel-image.png')}}"
                    alt="Laravel"
                    class="w-full h-10 sm:h-12 md:h-16 object-contain"
                />
            </a>

            <!-- Next.js -->
            <a
                href="https://nextjs.org/"
                target="_blank"
                rel="noopener noreferrer"
                title="Learn more about Next.js"
                class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 flex items-center justify-center hover:shadow-lg transition-shadow"
            >
                <img
                    src="{{asset('assets/landing/images/nextjs-image.png')}}"
                    alt="Next.js"
                    class="w-full h-10 sm:h-12 md:h-16 object-contain"
                />
            </a>

            <!-- Firebase -->
            <a
                href="https://firebase.google.com/"
                target="_blank"
                rel="noopener noreferrer"
                title="Learn more about Firebase"
                class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 flex items-center justify-center hover:shadow-lg transition-shadow"
            >
                <img
                    src="{{asset('assets/landing/images/firebase-image.png')}}"
                    alt="Firebase"
                    class="w-full h-10 sm:h-12 md:h-16 object-contain"
                />
            </a>

            <!-- Bootstrap -->
            <a
                href="https://getbootstrap.com/"
                target="_blank"
                rel="noopener noreferrer"
                title="Learn more about Bootstrap"
                class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 flex items-center justify-center hover:shadow-lg transition-shadow"
            >
                <img
                    src="{{asset('assets/landing/images/bootstrap-image.png')}}"
                    alt="Bootstrap"
                    class="w-full h-10 sm:h-12 md:h-16 object-contain"
                />
            </a>

            <!-- Tailwind -->
            <a
                href="https://tailwindcss.com/"
                target="_blank"
                rel="noopener noreferrer"
                title="Learn more about Tailwind CSS"
                class="bg-white border border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-6 md:p-8 flex items-center justify-center hover:shadow-lg transition-shadow"
            >
                <img
                    src="{{asset('assets/landing/images/tailwind-image.png')}}"
                    alt="Tailwind CSS"
                    class="w-full h-10 sm:h-12 md:h-16 object-contain"
                />
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-gray-900 text-white py-8 sm:py-10 md:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Footer Content Grid -->
        <div
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 mb-6 sm:mb-8"
        >
            <!-- Logo & Company Name -->
            <div class="lg:col-span-1">
                <div class="flex items-center gap-3 mb-4">
                    <img
                        src="{{asset('assets/landing/images/footer-logo.png')}}"
                        alt="Logo"
                        class="h-10"
                    />
                </div>
            </div>

            <!-- Company Column -->
            <div>
                <h4 class="font-heading font-semibold text-base mb-4">Company</h4>
                <ul class="space-y-2">
                    <li>
                        <a
                            href="https://infinitietech.com/about-us"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Learn more about Infinitie Technologies"
                        >About us</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://infinitietech.com/contact-us"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Contact us for your project needs"
                        >Hire us</a
                        >
                    </li>
                </ul>
            </div>

            <!-- Services Column -->
            <div>
                <h4 class="font-heading font-semibold text-base mb-4">Services</h4>
                <ul class="space-y-2">
                    <li>
                        <a
                            href="https://infinitietech.com/services/app-development"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Explore our app development services"
                        >App Development</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://infinitietech.com/services/web-development"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Explore our web development services"
                        >Web Development</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://infinitietech.com/services/ui-ux-service"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Explore our UI/UX design services"
                        >UI/UX Design</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://infinitietech.com/services/digital-marketing"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Explore our digital marketing services"
                        >Digital Marketing</a
                        >
                    </li>
                    <li>
                        <a
                            href="https://infinitietech.com/services/custom-solutions"
                            target="_blank"
                            class="text-gray-400 hover:text-white text-sm transition-colors"
                            title="Explore our customization and custom solution services"
                        >Customization</a
                        >
                    </li>
                </ul>
            </div>

            <!-- Contact Us Column -->
            <div>
                <h4 class="font-heading font-semibold text-base mb-4">
                    Contact Us
                </h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-2 text-gray-400 text-sm">
                        <svg
                            class="w-5 h-5 mt-0.5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        <span
                        >#237, Time Square Empire, Bhuj,<br />370001, Kutch Gujarat
                  India</span
                        >
                    </li>
                    <li class="flex items-center gap-2 text-gray-400 text-sm">
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                            />
                        </svg>
                        <a
                            href="tel:+919974692496"
                            class="hover:text-white transition-colors"
                            title="Call us at +919974692496"
                        >+919974692496</a
                        >
                    </li>
                    <li class="flex items-center gap-2 text-gray-400 text-sm">
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                        <a
                            href="mailto:info@infinitietech.com"
                            class="hover:text-white transition-colors"
                            title="Email us at info@infinitietech.com"
                        >info@infinitietech.com</a
                        >
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div
            class="border-t border-gray-800 pt-6 sm:pt-8 flex flex-col md:flex-row justify-between items-center gap-4"
        >
            <p class="text-gray-400 text-xs sm:text-sm text-center md:text-left">
                Copyright © {{date('Y')}}
                <a
                    href="https://infinitietech.com/"
                    target="_blank"
                    class="text-white hover:underline"
                    title="Visit Infinitie Technologies"
                >Infinitie Technologies</a
                >
            </p>

            <!-- Social Media Icons -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a
                    href="https://in.linkedin.com/company/infinitie-technologies"
                    target="_blank"
                    class="w-10 h-10 bg-white text-gray-900 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors"
                    title="Follow us on LinkedIn"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"
                        />
                    </svg>
                </a>
                <a
                    href="https://www.instagram.com/infinitietech/"
                    target="_blank"
                    class="w-10 h-10 bg-white text-gray-900 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors"
                    title="Follow us on Instagram"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"
                        />
                    </svg>
                </a>
                <a
                    href="https://api.whatsapp.com/send?phone=919974692496&text=Hello,%20I%20am%20inquiring%20from%20your%20website."
                    target="_blank"
                    class="w-10 h-10 bg-white text-gray-900 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors"
                    title="Contact us on WhatsApp"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20.52 3.48A11.82 11.82 0 0012.02 0C5.4 0 .02 5.38.02 12c0 2.11.55 4.17 1.6 6L0 24l6.18-1.6a11.93 11.93 0 005.84 1.49h.01c6.62 0 12-5.38 12-12a11.86 11.86 0 00-3.5-8.41zM12.03 21.3a9.29 9.29 0 01-4.74-1.3l-.34-.2-3.67.95.98-3.58-.22-.37a9.29 9.29 0 01-1.43-4.98c0-5.14 4.19-9.33 9.33-9.33a9.27 9.27 0 016.6 2.73 9.28 9.28 0 012.73 6.6c0 5.14-4.19 9.33-9.34 9.33zm5.12-6.98c-.28-.14-1.65-.81-1.9-.9-.25-.09-.43-.14-.61.14-.18.28-.7.9-.86 1.08-.16.18-.32.21-.6.07-.28-.14-1.18-.43-2.25-1.38-.83-.74-1.39-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.12-.12.28-.32.42-.48.14-.16.18-.28.28-.46.09-.18.05-.35-.02-.49-.07-.14-.61-1.47-.83-2.01-.22-.53-.44-.46-.61-.47h-.52c-.18 0-.46.07-.7.35-.25.28-.92.9-.92 2.2 0 1.3.95 2.55 1.08 2.73.14.18 1.87 2.85 4.53 4 2.66 1.15 2.66.77 3.14.72.48-.05 1.55-.63 1.77-1.24.22-.61.22-1.13.16-1.24-.07-.11-.25-.18-.53-.32z"
                        />
                    </svg>
                </a>
                <a
                    href="https://www.youtube.com/@infinitietech"
                    target="_blank"
                    class="w-10 h-10 bg-white text-gray-900 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors"
                    title="Subscribe to our YouTube channel"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"
                        />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile Menu Script -->
<script>
    const mobileMenuBtn = document.getElementById("mobile-menu-btn");
    const mobileMenu = document.getElementById("mobile-menu");

    mobileMenuBtn.addEventListener("click", () => {
        mobileMenu.classList.toggle("active");
    });

    // Close menu when clicking on a link
    const mobileMenuLinks = mobileMenu.querySelectorAll("a");
    mobileMenuLinks.forEach((link) => {
        link.addEventListener("click", () => {
            mobileMenu.classList.remove("active");
        });
    });
</script>
</body>
</html>
