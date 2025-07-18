<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Toilet Rentals - Catdump: Clean & Reliable Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #2d3748;
            overflow-x: hidden;
            line-height: 1.6;
        }

        .container-box {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        .section-box {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .section-box-alt {
            background-color: #eef2f6;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            margin-bottom: 3.5rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
            padding: 1.2rem 3.5rem;
            border-radius: 0.75rem;
            font-weight: 800;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(26, 115, 232, 0.4);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-primary:hover {
            background-color: #155bb5;
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(26, 115, 232, 0.6);
        }
        .btn-secondary {
            background-color: transparent;
            color: #1a73e8;
            padding: 1.2rem 3.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            border: 2px solid #1a73e8;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-secondary:hover {
            background-color: #1a73e8;
            color: white;
            transform: translateY(-7px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.2);
        }
        .icon-box {
            background-color: #34a853;
            color: white;
            border-radius: 50%;
            padding: 1.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .text-blue-custom {
            color: #1a73e8;
        }
        .text-green-custom {
            color: #34a853;
        }

        .hero-background {
            background-image: url('/assets/images/toilet.png');
            background-size: cover;
            background-position: center;
            position: relative;
            z-index: 0;
            padding-top: 10rem;
            padding-bottom: 10rem;
        }
        .hero-overlay {
            background: linear-gradient(to right, rgba(248, 249, 250, 0.9), rgba(248, 249, 250, 0.6));
            position: absolute;
            inset: 0;
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            color: #2d3748;
        }

        .animate-on-scroll {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
            transition: opacity 1s ease-out, transform 1s ease-out;
        }

        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .delay-100 { transition-delay: 0.1s; animation-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; animation-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; animation-delay: 0.3s; }
        .delay-400 { transition-delay: 0.4s; animation-delay: 0.4s; }
        .delay-500 { transition-delay: 0.5s; animation-delay: 0.5s; }
        .delay-600 { transition-delay: 0.6s; animation-delay: 0.6s; }
        .delay-700 { transition-delay: 0.7s; animation-delay: 0.7s; }
        .delay-800 { transition-delay: 0.8s; animation-delay: 0.8s; }
        .delay-900 { transition-delay: 0.9s; animation-delay: 0.9s; }
        .delay-1000 { transition-delay: 1s; animation-delay: 1s; }

        .card-hover-effect {
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            background-color: #ffffff;
            color: #2d3748;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transform-style: preserve-3d;
        }
        .card-hover-effect:hover {
            transform: translateY(-10px) scale(1.04) rotateX(2deg) rotateY(2deg);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
            background-color: #f8f9fa;
            border-color: #1a73e8;
        }
        .card-hover-effect .icon-box {
            background-color: #34a853;
            color: white;
        }

        .testimonial-card {
            background-color: #ffffff;
            border-radius: 1rem;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .testimonial-quote {
            font-size: 1.25rem;
            font-style: italic;
            color: #4a5568;
            margin-bottom: 1.5rem;
        }
        .testimonial-author {
            font-weight: 600;
            color: #1a73e8;
        }
        .testimonial-source {
            color: #718096;
            font-size: 0.9rem;
        }

        .how-it-works-container {
            display: flex;
            flex-direction: column;
            gap: 3rem;
        }

        .how-it-works-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            margin-bottom: 4rem; /* Added spacing between rows */
        }

        .how-it-works-image-box {
            background-color: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .how-it-works-image-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .how-it-works-image-box img {
            max-width: 90%;
            height: auto;
            border-radius: 0.5rem;
        }

        .how-it-works-content {
            flex: 1;
            text-align: center;
        }

        .how-it-works-step-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a73e8;
            margin-bottom: 0.5rem;
        }

        .how-it-works-step-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .how-it-works-step-description {
            color: #4a5568;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        @media (min-width: 768px) {
            .how-it-works-row {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            .how-it-works-row:nth-child(even) {
                flex-direction: row-reverse;
            }
            .how-it-works-content {
                text-align: left;
            }
            .how-it-works-image-box {
                width: 50%;
            }
        }

        .accordion-item {
            border-bottom: 1px solid #e2e8f0;
        }
        .accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 1rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.25rem;
            color: #2d3748;
            transition: background-color 0.2s ease;
        }
        .accordion-header:hover {
            background-color: #f0f4f8;
        }
        .accordion-header svg {
            transition: transform 0.3s ease;
        }
        .accordion-header.active svg {
            transform: rotate(180deg);
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            padding: 0 1rem;
            color: #4a5568;
        }
        .accordion-content.open {
            max-height: 200px; /* Adjust as needed */
            padding-bottom: 1.5rem;
        }

        .feature-card .icon-wrapper {
            background-color: #eef2f6;
            border-radius: 50%;
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .feature-card .icon {
            font-size: 2rem;
            color: #1a73e8;
        }

        .unit-type-card {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        .unit-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .unit-type-card img {
            max-width: 100%;
            height: 180px; /* Consistent image height */
            object-fit: contain;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .unit-type-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a73e8;
            margin-bottom: 0.75rem;
        }
        .unit-type-card p {
            font-size: 1rem;
            line-height: 1.6;
            color: #4a5568;
        }

        /* Floating Chat Bubble for service pages */
        #floating-chat-trigger {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background-color: #1a73e8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 999;
            transform: scale(1); /* Always visible on service pages */
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        #floating-chat-trigger svg {
            color: white;
            width: 32px;
            height: 32px;
        }
    </style>
</head>
<body class="antialiased">

    <?php include '../includes/public_header.php'; ?>

    <main>
        <section id="hero-section" class="hero-background py-32 md:py-48 relative">
            <div class="hero-overlay"></div>
            <div class="container-box hero-content text-center">
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold leading-tight mb-8 animate-on-scroll">
                    Clean & Reliable Temporary Toilets for <span class="text-blue-custom">Any Event or Site</span>
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto animate-on-scroll delay-300">
                    Ensure comfort and sanitation with our wide range of portable toilets, perfectly suited for construction sites, outdoor events, and emergency needs.
                </p>
                <a href="#" onclick="showAIChat('create-booking'); return false;" class="btn-primary inline-block animate-on-scroll delay-600">Get Your Portable Toilet Quote!</a>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <div class="text-center mb-8 animate-on-scroll delay-100">
                    <span class="text-blue-custom text-lg font-semibold uppercase">Why Catdump for Toilets?</span>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-gray-800 mt-2">Your Trusted Partner for Portable Sanitation</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="feature-card animate-on-scroll delay-200">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002898.png" width="80" height="80">
                        </div>
                        <h4>Exceptional Cleanliness</h4>
                        <p>Our portable toilets are meticulously cleaned and sanitized before delivery and serviced regularly, guaranteeing a hygienic experience for all users.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-300">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002899.png" width="80" height="80">
                        </div>
                        <h4>Diverse Unit Selection</h4>
                        <p>Choose from standard units, ADA-compliant accessible toilets, luxury flushing options, and separate handwashing stations to meet specific event or site needs.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-400">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002900.png" width="80" height="80">
                        </div>
                        <h4>Rapid Delivery & Setup</h4>
                        <p>Our network of local providers ensures timely delivery and professional setup of your portable toilets, so they're ready for use exactly when you need them.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-500">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002903.png" width="80" height="80">
                        </div>
                        <h4>Competitive, Transparent Pricing</h4>
                        <p>Leverage our marketplace to compare quotes from top local suppliers and secure the most cost-effective solution for your portable sanitation requirements.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-600">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002902.png" width="80" height="80">
                        </div>
                        <h4>Seamless Management</h4>
                        <p>Manage your portable toilet rentals, schedule servicing, and communicate with suppliers easily through your intuitive Catdump dashboard or mobile app.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-700">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002901.png" width="80" height="80">
                        </div>
                        <h4>Dedicated Support Team</h4>
                        <p>Our expert support team is always available to help you determine the right number and type of units, ensuring your event or project runs smoothly.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Our Range of Portable Toilet Solutions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div class="unit-type-card animate-on-scroll delay-100">
                        <img src="/assets/images/Group 1000002834.png" alt="Standard Portable Toilet">
                        <h3>Standard Portable Toilets</h3>
                        <p>The most common and versatile option, perfect for construction sites, festivals, or any event needing basic, reliable sanitation facilities.</p>
                    </div>
                    <div class="unit-type-card animate-on-scroll delay-200">
                        <img src="/assets/images/Group 1000002834.png" alt="ADA Compliant Portable Toilet">
                        <h3>ADA Compliant Units</h3>
                        <p>Spacious, wheelchair-accessible portable toilets designed to meet ADA requirements, ensuring comfort and accessibility for all guests and workers.</p>
                    </div>
                    <div class="unit-type-card animate-on-scroll delay-300">
                        <img src="/assets/images/Group 1000002834.png" alt="Flushing Portable Toilet">
                        <h3>Flushing Portable Toilets</h3>
                        <p>Offer a more upscale experience with a foot-pump flushing system, ideal for weddings, corporate events, or longer-term projects requiring enhanced comfort.</p>
                    </div>
                    <div class="unit-type-card animate-on-scroll delay-400">
                        <img src="/assets/images/Group 1000002834.png" alt="Handwashing Station">
                        <h3>Portable Handwashing Stations</h3>
                        <p>Essential for hygiene, these standalone units provide fresh water, soap dispensers, and paper towel dispensers, perfect for pairing with any portable toilet setup.</p>
                    </div>
                    <div class="unit-type-card animate-on-scroll delay-500">
                        <img src="/assets/images/Group.png" alt="Restroom Trailer">
                        <h3>Luxury Restroom Trailers</h3>
                        <p>For high-end events, these trailers feature multiple stalls, sinks with running water, air conditioning, and enhanced interiors for a premium experience.</p>
                    </div>
                </div>
                <div class="text-center mt-20 animate-on-scroll delay-600">
                    <a href="#" onclick="showAIChat('create-booking'); return false;" class="btn-secondary inline-block">Unsure which type you need? Let our AI guide you!</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Your 3-Step Process to Clean Toilets</h2>
                <div class="how-it-works-container">
                    <div class="how-it-works-row animate-on-scroll delay-100">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002833.png" alt="Chat for Toilet Needs">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 1</p>
                            <h3 class="how-it-works-step-title">Specify Your Requirements</h3>
                            <p class="how-it-works-step-description">Use our AI chat to tell us your event type, number of guests/workers, and duration. We'll help determine the right number and type of temporary toilets for you.</p>
                            <a href="#" onclick="showAIChat('create-booking'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Start Planning &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-300">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002832.png" alt="Compare Toilet Prices">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 2</p>
                            <h3 class="how-it-works-step-title">Get & Compare Top Local Quotes</h3>
                            <p class="how-it-works-step-description">Instantly receive competitive quotes from our trusted local portable toilet suppliers, ensuring you get the best deal for clean and reliable units.</p>
                            <a href="/Resources/Pricing-Finance.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">View Quotes &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-500">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002831.png" alt="Book & Track Toilet">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 3</p>
                            <h3 class="how-it-works-step-title">Confirm, Deliver & Service</h3>
                            <p class="how-it-works-step-description">Book your chosen units, make a secure payment, and we'll handle the timely delivery and scheduled servicing, all managed from your dashboard.</p>
                            <a href="/customer/dashboard.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Access Dashboard &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="testimonials-section">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">What Our Clients Say About Our Portable Toilets</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="testimonial-card animate-on-scroll delay-100">
                        <p class="testimonial-quote">"We host outdoor concerts, and Catdump's portable toilets were a game-changer. Clean, well-maintained, and delivered on time. Our attendees truly appreciated the quality!"</p>
                        <p class="testimonial-author">- Lisa K.</p>
                        <p class="testimonial-source">Event Organizer, Harmony Fest</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-200">
                        <p class="testimonial-quote">"For our construction site, reliable sanitation is crucial. Catdump's quick delivery and consistent servicing meant our crew always had clean facilities. Excellent service!"</p>
                        <p class="testimonial-author">- Robert S.</p>
                        <p class="testimonial-source">Construction Foreman, BuildRight Inc.</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-300">
                        <p class="testimonial-quote">"The ADA-compliant units we rented for our community fair were perfect. Catdump made the process of finding and booking them incredibly easy and affordable."</p>
                        <p class="testimonial-author">- Maria G.</p>
                        <p class="testimonial-source">Community Coordinator, Green Valley</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="faq-section">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Frequently Asked Questions About Temporary Toilets</h2>
                <div class="max-w-3xl mx-auto">
                    <div class="accordion-item animate-on-scroll delay-100">
                        <div class="accordion-header" data-accordion-toggle="faq-1">
                            How often are the portable toilets serviced?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-1" class="accordion-content">
                            <p>Standard servicing for portable toilets on construction sites is typically once a week. For events, servicing frequency depends on the duration and expected attendance, often multiple times during multi-day events. You can discuss specific servicing needs with your provider during the quoting process.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-200">
                        <div class="accordion-header" data-accordion-toggle="faq-2">
                            How many portable toilets do I need for my event/site?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-2" class="accordion-content">
                            <p>The number of units needed depends on factors like the number of attendees/workers, duration of the event/project, and presence of food/beverages. Our AI chat can provide recommendations based on industry standards, or our support team can help you calculate the optimal number for your specific needs.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-300">
                        <div class="accordion-header" data-accordion-toggle="faq-3">
                            Do you offer portable handwashing stations?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-3" class="accordion-content">
                            <p>Yes, we do! Portable handwashing stations are highly recommended for enhanced hygiene, especially at events with food, or on construction sites. You can easily add these to your rental request through our platform, and our suppliers will include them in your quote.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-400">
                        <div class="accordion-header" data-accordion-toggle="faq-4">
                            What are the delivery and pickup procedures?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-4" class="accordion-content">
                            <p>Once you confirm your order, our supplier will coordinate with you for a convenient delivery time. Units are typically placed on a flat, accessible surface. For pickup, simply notify us through your dashboard when the rental period is complete or units are no longer needed, and the supplier will arrange removal.</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-16 animate-on-scroll delay-600">
                    <a href="/Resources/FAQs.php" class="btn-secondary inline-block">View All Portable Toilet FAQs</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10 animate-on-scroll">Ready to Ensure Sanitation & Comfort?</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto animate-on-scroll delay-100">
                    Get clean, reliable portable toilets quickly and affordably. Catdump makes sanitation simple, so your event or project can focus on success.
                </p>
                <a href="#" onclick="showAIChat('create-booking'); return false;" class="btn-primary inline-block shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 animate-on-scroll delay-200">Get Your Free Portable Toilet Quote Today!</a>
            </div>
        </section>
    </main>

    <div id="floating-chat-trigger" onclick="showAIChat('create-booking');">
        <i class="fas fa-comment-dots"></i>
    </div>

    <?php include '../includes/public_footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animateOnScrollElements = document.querySelectorAll('.animate-on-scroll');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Apply delay if specified, otherwise add immediately
                        const delay = parseFloat(getComputedStyle(entry.target).transitionDelay || 0);
                        if (delay > 0) {
                            setTimeout(() => {
                                entry.target.classList.add('is-visible');
                            }, delay * 1000); // Convert seconds to milliseconds
                        } else {
                            entry.target.classList.add('is-visible');
                        }
                        observer.unobserve(entry.target); // Stop observing once visible
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            animateOnScrollElements.forEach(element => {
                observer.observe(element);
            });

            const heroSection = document.getElementById('hero-section');
            if (heroSection) {
                window.addEventListener('scroll', () => {
                    const scrollPosition = window.pageYOffset;
                    heroSection.style.backgroundPositionY = -scrollPosition * 0.3 + 'px';
                });
            }
            
            // Accordion functionality for FAQs and Quick Solutions
            document.querySelectorAll('.accordion-header').forEach(header => {
                header.addEventListener('click', () => {
                    const content = document.getElementById(header.dataset.accordionToggle);
                    const isActive = header.classList.contains('active');

                    // Close all open accordions first (within the same section to prevent unintended closing)
                    // This logic assumes you only want one accordion open at a time within its immediate parent group
                    const parentSection = header.closest('.faq-category-section') || header.closest('.section-box') || header.closest('.section-box-alt');
                    parentSection.querySelectorAll('.accordion-header.active').forEach(activeHeader => {
                        if (activeHeader !== header) { // Don't close the currently clicked one
                            activeHeader.classList.remove('active');
                            document.getElementById(activeHeader.dataset.accordionToggle).classList.remove('open');
                        }
                    });

                    // Toggle the clicked accordion
                    if (!isActive) {
                        header.classList.add('active');
                        content.classList.add('open');
                    }
                });
            });
        });
    </script>
</body>
</html>