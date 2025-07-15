<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Resources - Catdump: Guides & Support</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
            background-image: url('https://placehold.co/1920x900/d0e9e9/1a73e8?text=Customer+Resources+Hero');
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

        .resource-card {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .resource-card .icon-large {
            font-size: 3.5rem;
            color: #1a73e8; /* Blue for resources */
            margin-bottom: 1.5rem;
        }
        .resource-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }
        .resource-card p {
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
                    Customer Resources: <span class="text-blue-custom">Guides, Support & Tools</span>
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto animate-on-scroll delay-300">
                    Access a comprehensive suite of resources designed to help you streamline your projects, understand our services, and get the most out of Catdump.
                </p>
                <a href="#resource-categories" class="btn-primary inline-block animate-on-scroll delay-600">Explore Resources</a>
            </div>
        </section>

        <section id="resource-categories" class="container-box py-20 md:py-32">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Your Go-To Hub for All Things Catdump</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div class="resource-card animate-on-scroll delay-100">
                        <div class="icon-large">📚</div>
                        <h3>Guides & How-Tos</h3>
                        <p>Detailed articles and step-by-step guides on choosing equipment, optimizing your rental period, managing waste, and more.</p>
                        <a href="/Resources/Blog.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Read Guides &rarr;</a>
                    </div>
                    <div class="resource-card animate-on-scroll delay-200">
                        <div class="icon-large">❓</div>
                        <h3>FAQs & Help Center</h3>
                        <p>Find quick answers to common questions about pricing, delivery, payments, specific equipment, and troubleshooting tips.</p>
                        <a href="/Resources/FAQs.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Visit Help Center &rarr;</a>
                    </div>
                    <div class="resource-card animate-on-scroll delay-300">
                        <div class="icon-large">📞</div>
                        <h3>Direct Support</h3>
                        <p>Connect with our friendly and knowledgeable support team via chat, email, or phone for personalized assistance with your rentals.</p>
                        <a href="#" onclick="showAIChat('create-booking'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Chat with AI Assistant &rarr;</a>
                    </div>
                    <div class="resource-card animate-on-scroll delay-400">
                        <div class="icon-large">📊</div>
                        <h3>Pricing & Financing Details</h3>
                        <p>Understand our transparent pricing model, explore flexible payment options, and learn about financing solutions for larger projects.</p>
                        <a href="/Resources/Pricing-Finance.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">View Pricing & Finance &rarr;</a>
                    </div>
                    <div class="resource-card animate-on-scroll delay-500">
                        <div class="icon-large">✍️</div>
                        <h3>Blog & News</h3>
                        <p>Stay updated with the latest industry trends, company news, expert tips, and insights on equipment rental and waste management.</p>
                        <a href="/Resources/Blog.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Explore Blog &rarr;</a>
                    </div>
                    <div class="resource-card animate-on-scroll delay-600">
                        <div class="icon-large">📄</div>
                        <h3>Terms & Policies</h3>
                        <p>Access our comprehensive terms and conditions, privacy policy, and other legal documents for full transparency and understanding.</p>
                        <a href="/Terms and Conditions.html" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Read Policies &rarr;</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-20 animate-on-scroll">Frequently Asked Questions</h2>
                <div class="max-w-3xl mx-auto">
                    <div class="accordion-item animate-on-scroll delay-100">
                        <div class="accordion-header" data-accordion-toggle="faq-1">
                            How do I get an instant quote for equipment rental?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-1" class="accordion-content">
                            <p>You can get an instant quote by simply chatting with our AI assistant on the homepage. Tell it what you need, and it will gather details and provide competitive offers from local suppliers, often within minutes.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-200">
                        <div class="accordion-header" data-accordion-toggle="faq-2">
                            Can I manage all my rentals from one place?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-2" class="accordion-content">
                            <p>Yes! Our customer dashboard provides a centralized hub where you can track active rentals, view past orders, manage invoices, schedule extensions or pickups, and communicate directly with suppliers.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-300">
                        <div class="accordion-header" data-accordion-toggle="faq-3">
                            What kind of support is available if I have issues?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-3" class="accordion-content">
                            <p>We offer comprehensive support including an extensive FAQ section, detailed guides, and a dedicated support team accessible via chat, email, or phone. Our goal is to ensure your project runs smoothly.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-400">
                        <div class="accordion-header" data-accordion-toggle="faq-4">
                            How does Catdump ensure equipment reliability?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-4" class="accordion-content">
                            <p>We partner exclusively with pre-vetted, reputable local suppliers who are known for their well-maintained equipment and reliable service. Customer feedback is continuously monitored to ensure high standards are upheld.</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-16 animate-on-scroll delay-600">
                    <a href="/Resources/FAQs.php" class="btn-secondary inline-block">View All FAQs</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10 animate-on-scroll">Need More Assistance?</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto animate-on-scroll delay-100">
                    Our team is here to help you navigate every aspect of your equipment rental needs. Don't hesitate to reach out!
                </p>
                <a href="#" onclick="showAIChat('create-booking'); return false;" class="btn-primary inline-block shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 animate-on-scroll delay-200">Chat with AI Assistant for Support</a>
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

            // Counter animation for stats section (if applicable)
            const counterObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const endValue = parseFloat(target.dataset.target); // Use parseFloat for percentages
                        const duration = 2000;
                        let startTimestamp = null;

                        const step = (timestamp) => {
                            if (!startTimestamp) startTimestamp = timestamp;
                            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                            let currentValue;
                            let textSuffix = '';

                            if (target.dataset.target.includes('%')) {
                                currentValue = Math.floor(progress * endValue);
                                textSuffix = '%';
                            } else {
                                currentValue = Math.floor(progress * endValue);
                            }
                            target.textContent = currentValue.toLocaleString() + textSuffix;

                            if (progress < 1) {
                                window.requestAnimationFrame(step);
                            }
                        };
                        window.requestAnimationFrame(step);
                        observer.unobserve(target);
                    }
                });
            }, { threshold: 0.5 });

            document.querySelectorAll('[data-target]').forEach(counter => {
                counterObserver.observe(counter);
            });
        });
    </script>
</body>
</html>