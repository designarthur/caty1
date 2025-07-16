<?php
// Company/Testimonials.php

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Fetch company name from system settings
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Testimonials - <?php echo htmlspecialchars($companyName); ?></title>
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
            background-image: url('/assets/images/hero image for about us page.png');
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

        .testimonial-card-large {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 4rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .testimonial-card-large .quote-icon {
            font-size: 3rem;
            color: #1a73e8;
            margin-bottom: 1.5rem;
        }
        .testimonial-card-large .quote-text {
            font-size: 1.75rem;
            font-style: italic;
            color: #2d3748;
            line-height: 1.4;
            margin-bottom: 2rem;
        }
        .testimonial-card-large .author-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .testimonial-card-large .author-info img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.25rem;
            border: 3px solid #1a73e8;
        }
        .testimonial-card-large .author-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a73e8;
        }
        .testimonial-card-large .author-title {
            font-size: 1rem;
            color: #718096;
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
                    Real Stories: <span class="text-blue-custom">Customer Testimonials</span>
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto animate-on-scroll delay-300">
                    Hear directly from our satisfied customers about their experiences with Catdump's efficient, transparent, and reliable equipment rental and waste management services.
                </p>
                <a href="#customer-stories" class="btn-primary inline-block animate-on-scroll delay-600">Read Their Stories</a>
            </div>
        </section>

        <section id="customer-stories" class="container-box py-20 md:py-32">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">What Our Customers Love About Catdump</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="testimonial-card animate-on-scroll delay-100">
                        <p class="testimonial-quote">"Catdump transformed how we manage waste on our job sites. The AI quoting is lightning fast, and getting the right dumpster at the best price, delivered precisely on time, has made our operations so much smoother. Absolute game-changer!"</p>
                        <p class="testimonial-author">- Michael S.</p>
                        <p class="testimonial-source">Project Manager, Horizon Construction</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-200">
                        <p class="testimonial-quote">"I needed a portable toilet for my outdoor event, and Catdump made it incredibly easy. The unit was spotless, and the delivery/pickup was seamless. Truly stress-free sanitation. Highly recommend their reliability!"</p>
                        <p class="testimonial-author">- Sarah L.</p>
                        <p class="testimonial-source">Event Coordinator, Elite Events</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-300">
                        <p class="testimonial-quote">"The junk removal service was beyond simple. I just uploaded a few photos, got an instant quote, and their professional crew had everything cleared out in no time. It saved me hours of hauling. Fantastic service and eco-friendly too!"</p>
                        <p class="testimonial-author">- David R.</p>
                        <p class="testimonial-source">Homeowner, Major Cleanout</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-400">
                        <p class="testimonial-quote">"Managing multiple construction sites means constant equipment needs. Catdump's dashboard gives me total control, and the ability to easily extend rentals or swap units is invaluable. It keeps my projects on schedule and on budget."</p>
                        <p class="testimonial-author">- Emily C.</p>
                        <p class="testimonial-source">Site Supervisor, BuildRight Corp.</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-500">
                        <p class="testimonial-quote">"The transparency in pricing is what sets Catdump apart. No hidden fees, just clear, competitive quotes that allowed me to save significantly on my storage container rental. Their customer support is also top-notch!"</p>
                        <p class="testimonial-author">- Chris P.</p>
                        <p class="testimonial-source">Business Owner, Inventory Management</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-600">
                        <p class="testimonial-quote">"For our large community fair, we needed various rentals, and Catdump delivered perfectly. Their ability to handle diverse needs from one platform, with such efficiency, was truly impressive. Our go-to for all future events."</p>
                        <p class="testimonial-author">- Jessica B.</p>
                        <p class="testimonial-source">Community Organizer, Green Valley Fairs</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">A Word from Our Valued Partners</h2>
                <div class="max-w-4xl mx-auto">
                    <div class="testimonial-card-large animate-on-scroll delay-100">
                        <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                        <p class="quote-text">"Partnering with Catdump has been a game-changer for our local dumpster business. Their AI-driven platform seamlessly connects us with qualified customers, optimizing our routes and reducing idle time. It’s truly the future of equipment rental logistics."</p>
                        <div class="author-info">
                            <img src="https://placehold.co/70x70/1a73e8/ffffff?text=Partner+CEO" alt="Partner CEO">
                            <div>
                                <p class="author-name">Robert Johnson</p>
                                <p class="author-title">CEO, City Dumpsters Inc.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10 animate-on-scroll">Share Your Catdump Experience!</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto animate-on-scroll delay-100">
                    Your feedback helps us grow and serve you better. We'd love to hear about your successful projects and how Catdump made a difference.
                </p>
                <a href="#" onclick="showAIChat('create-booking'); return false;" class="btn-primary inline-block shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 animate-on-scroll delay-200">Submit Your Testimonial</a>
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
            
            // Accordion functionality for FAQs (if applicable)
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