<?php
// Services/Dumpster-Rentals.php

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
// Fetch company name from system settings
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Junk Removal Services - Catdump: Clear Your Space Easily</title>
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
            background-image: url('/assets/images/junk removal.png');
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

        .junk-item-category {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .junk-item-category:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .junk-item-category .icon-large {
            font-size: 3.5rem;
            color: #34a853;
            margin-bottom: 1.5rem;
        }
        .junk-item-category h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }
        .junk-item-category p {
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
                    Effortless Junk Removal: <span class="text-blue-custom">Clear Your Space, Stress-Free</span>
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto animate-on-scroll delay-300">
                    Say goodbye to unwanted clutter with our convenient, AI-powered junk removal service. Get instant quotes by simply showing us your junk!
                </p>
                <a href="../request_quote.php" class="btn-primary inline-block animate-on-scroll delay-600">Get Your Instant Quote!</a>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <div class="text-center mb-8 animate-on-scroll delay-100">
                    <span class="text-blue-custom text-lg font-semibold uppercase">Why Catdump for Junk Removal?</span>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-gray-800 mt-2">The Smartest Way to Dispose of Unwanted Items</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="feature-card animate-on-scroll delay-200">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 10000028861.png" width="80" height="80">
                        </div>
                        <h4>AI-Powered Photo Quoting</h4>
                        <p>Simply upload photos or videos of your junk, and our advanced AI will analyze it to provide you with an accurate, no-obligation quote instantly. No on-site estimates needed.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-300">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002887.png" width="80" height="80">
                        </div>
                        <h4>Transparent & Fair Pricing</h4>
                        <p>Our intelligent system ensures you get a fair and competitive price based on the actual volume and type of junk, with no hidden fees or surprises.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-400">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002888.png" width="80" height="80">
                        </div>
                        <h4>Flexible & Fast Scheduling</h4>
                        <p>Book your junk removal pickup at a time that suits you. We work with local, vetted crews to ensure prompt and efficient service, often with same-day or next-day availability.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-500">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002891.png" width="80" height="80">
                        </div>
                        <h4>Eco-Friendly Disposal</h4>
                        <p>We prioritize responsible disposal, aiming to donate usable items, recycle materials, and only landfill what's absolutely necessary, minimizing environmental impact.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-600">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002890.png" width="80" height="80">
                        </div>
                        <h4>Professional & Courteous Crews</h4>
                        <p>Our network comprises experienced and friendly junk removal specialists who handle your items with care and ensure a smooth, efficient cleanout process.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-700">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002889.png" width="80" height="80">
                        </div>
                        <h4>Handles a Wide Range of Items</h4>
                        <p>From old furniture and appliances to construction debris and yard waste, we can remove almost anything you need gone, simplifying your cleanup.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Our AI-Powered Quote System: Simply Point, Click & Clear!</h2>
                <div class="how-it-works-container">
                    <div class="how-it-works-row animate-on-scroll delay-100">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002841.png" alt="Upload Junk Photos">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 1</p>
                            <h3 class="how-it-works-step-title">Capture & Upload Your Junk</h3>
                            <p class="how-it-works-step-description">No need for an on-site visit! Simply use your smartphone or camera to take a few photos or a short video of the junk you want removed. Our intuitive platform makes it easy to upload them directly. Provide any additional details through our AI chat for maximum accuracy.</p>
                            <a href="#" onclick="showAIChat('junk-removal-service'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Upload Your Images &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-300">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002840.png" alt="AI Quote Analysis">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 2</p>
                            <h3 class="how-it-works-step-title">Instant AI Analysis & Quote</h3>
                            <p class="how-it-works-step-description">Our cutting-edge AI technology instantly analyzes the images and videos you've uploaded. It accurately assesses the volume, type, and complexity of your junk, then generates a precise and transparent quote in moments. You'll receive a detailed breakdown, so you know exactly what to expect.</p>
                            <a href="#" onclick="showAIChat('junk-removal-service'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Learn About AI Pricing &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-500">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002839.png" alt="Schedule Junk Pickup">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 3</p>
                            <h3 class="how-it-works-step-title">Confirm & Schedule Your Pickup</h3>
                            <p class="how-it-works-step-description">Review your instant quote on your personalized dashboard. If you're satisfied, simply confirm your booking and choose a convenient date and time for pickup. Our professional local crew will arrive promptly, remove your junk, and handle all the disposal, leaving your space clean and clutter-free.</p>
                            <a href="/customer/dashboard.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Book Your Pickup &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">What Kind of Junk Can We Remove for You?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div class="junk-item-category animate-on-scroll delay-100">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002892.png" width="80" height="80"></div>
                        <h3>Furniture & Appliances</h3>
                        <p>Old sofas, chairs, tables, beds, mattresses, refrigerators, washing machines, dryers, and more. We handle single items or full house clear-outs.</p>
                    </div>
                    <div class="junk-item-category animate-on-scroll delay-200">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002928.png" width="80" height="80"></div>
                        <h3>Construction & Demolition Debris</h3>
                        <p>Wood, drywall, concrete, bricks, tiles, roofing materials, scrap metal, and other debris from renovation or construction projects.</p>
                    </div>
                    <div class="junk-item-category animate-on-scroll delay-300">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002894.png" width="80" height="80"></div>
                        <h3>Yard Waste & Landscaping Debris</h3>
                        <p>Branches, leaves, brush, grass clippings, old fencing, landscaping timbers, and other garden waste. Keep your outdoor space tidy.</p>
                    </div>
                    <div class="junk-item-category animate-on-scroll delay-400">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002895.png" width="80" height="80"></div>
                        <h3>Electronics & E-Waste</h3>
                        <p>Old computers, monitors, TVs, printers, and other electronic devices. We ensure responsible recycling of e-waste to minimize environmental impact.</p>
                    </div>
                    <div class="junk-item-category animate-on-scroll delay-500">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002896.png" width="80" height="80"></div>
                        <h3>Household Clutter & Rubbish</h3>
                        <p>Boxes, old clothes, toys, books, general household junk, trash, and anything else taking up valuable space in your home or office.</p>
                    </div>
                    <div class="junk-item-category animate-on-scroll delay-600">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002897.png" width="80" height="80"></div>
                        <h3>Tires & Scrap Metal</h3>
                        <p>Used car tires, bicycle parts, metal scraps, and other metallic waste. Note: hazardous materials generally cannot be accepted.</p>
                    </div>
                </div>
                <div class="text-center mt-20 animate-on-scroll delay-700">
                    <p class="text-xl text-gray-700">Don't see your item listed? <a href="#" onclick="showAIChat('junk-removal-service'); return false;" class="text-blue-custom hover:underline font-medium">Ask our AI</a> or contact us for specific inquiries!</p>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="testimonials-section">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">What Our Customers Say About Our Junk Removal Service</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="testimonial-card animate-on-scroll delay-100">
                        <p class="testimonial-quote">"The AI quoting system for junk removal is genius! I snapped a few photos, got an instant price, and they picked up everything the next day. So simple and fair."</p>
                        <p class="testimonial-author">- Jessica R.</p>
                        <p class="testimonial-source">Homeowner, Garage Cleanout</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-200">
                        <p class="testimonial-quote">"We had a pile of construction debris that needed to go. Catdump's service was fast, efficient, and their crew was incredibly professional. Highly recommend for any cleanup job."</p>
                        <p class="testimonial-author">- Mark T.</p>
                        <p class="testimonial-source">Contractor, Project Site Cleanup</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-300">
                        <p class="testimonial-quote">"I loved that I could just upload a video of my old office furniture and get a quote without a hassle. It saved me so much time, and the disposal was handled responsibly."</p>
                        <p class="testimonial-author">- David L.</p>
                        <p class="testimonial-source">Business Owner, Office Clearance</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="faq-section">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Frequently Asked Questions About Junk Removal</h2>
                <div class="max-w-3xl mx-auto">
                    <div class="accordion-item animate-on-scroll delay-100">
                        <div class="accordion-header" data-accordion-toggle="faq-1">
                            How accurate is the AI-powered junk removal quote?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-1" class="accordion-content">
                            <p>Our AI system is highly accurate, leveraging advanced image and video recognition to assess the volume and type of junk. Quotes are typically very precise. In rare cases of significant discrepancies upon arrival, the crew will discuss any adjustments with you before proceeding, ensuring full transparency.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-200">
                        <div class="accordion-header" data-accordion-toggle="faq-2">
                            Do I need to sort or bag my junk before pickup?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-2" class="accordion-content">
                            <p>Generally, no. Our crews are equipped to handle mixed junk. While organizing similar items together can sometimes make the process quicker, it's not a requirement. Just ensure the items are accessible for our team to safely remove.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-300">
                        <div class="accordion-header" data-accordion-toggle="faq-3">
                            What happens to my junk after it's picked up?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-3" class="accordion-content">
                            <p>We partner with junk removal specialists committed to responsible disposal. This means items are sorted for recycling whenever possible, usable goods are donated to local charities, and only items that cannot be repurposed or recycled are sent to landfills. We strive to minimize environmental impact.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-400">
                        <div class="accordion-header" data-accordion-toggle="faq-4">
                            How quickly can you pick up my junk?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-4" class="accordion-content">
                            <p>Pickup times depend on local availability and your scheduling preferences. Many of our partners offer same-day or next-day service. You'll be able to see available pickup slots when you receive your quote and proceed to booking on your dashboard.</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-16 animate-on-scroll delay-600">
                    <a href="/Resources/FAQs.php" class="btn-secondary inline-block">View All Junk Removal FAQs</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10 animate-on-scroll">Ready to Say Goodbye to Your Junk?</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto animate-on-scroll delay-100">
                    Experience the easiest way to get rid of unwanted items. Get your instant quote and schedule a pickup today with Catdump.
                </p>
                <a href="../request_quote.php" class="btn-primary inline-block shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 animate-on-scroll delay-200">Start Your Free Junk Removal Quote!</a>
            </div>
        </section>
    </main>

    <div id="floating-chat-trigger" onclick="showAIChat('junk-removal-service');">
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