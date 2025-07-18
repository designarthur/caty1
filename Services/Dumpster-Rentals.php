<?php
// Services/Dumpster-Rentals.php

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
    <title>Dumpster Rentals - <?php echo htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #2d3748;
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
        .text-blue-custom {
            color: #1a73e8;
        }
        .text-green-custom {
            color: #34a853;
        }

        /* Testimonial styles */
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

        /* FAQ styles */
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

        /* Floating Chat Bubble */
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
        .hero-background {
            background-image: url('/assets/images/dumpster.png');
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
    </style>
</head>
<body class="antialiased">
    <?php include __DIR__ . '/../includes/public_header.php'; ?>

    <main>
        <section id="hero-section" class="hero-background py-32 md:py-48 relative">
            <div class="hero-overlay"></div>
            <div class="container-box hero-content text-center">
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold leading-tight mb-8 animate-on-scroll">
                    <span class="text-blue-custom">Efficient</span> Dumpster Rentals
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto animate-on-scroll delay-300">
                    Seamlessly find and book the perfect dumpster for your project. From home cleanouts to construction debris, we've got you covered with competitive pricing and reliable service.
                </p>
                <a href="#" class="btn-primary inline-block" onclick="showAIChat('junk-removal-service'); return false;" class="btn-primary inline-block animate-on-scroll delay-600">Get Your Instant Quote!</a>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-16">Our Dumpster Sizes & Uses</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="p-8 rounded-2xl shadow-xl flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002824.png" alt="10 Yard Dumpster" class="rounded-lg mb-6 shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">10 Yard Dumpster</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">Ideal for small projects like garage cleanouts, single room renovations, or dirt/concrete removal. Holds about 3 pickup truck loads.</p>
                        <ul class="text-left text-gray-700 w-full mb-6 space-y-2">
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Small cleanouts</li>
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Concrete/dirt removal (heavy items)</li>
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Bathroom remodels</li>
                        </ul>
                        <a href="#" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Get Quote for 10 Yard <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                    <div class="p-8 rounded-2xl shadow-xl flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002822.png" alt="20 Yard Dumpster" class="rounded-lg mb-6 shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">20 Yard Dumpster</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">Our most popular size, perfect for medium-sized renovations, decluttering projects, or general construction debris. Holds about 6 pickup truck loads.</p>
                        <ul class="text-left text-gray-700 w-full mb-6 space-y-2">
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Larger home cleanouts</li>
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Roofing projects (2500-3000 sq ft)</li>
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Deck removal</li>
                        </ul>
                        <a href="#" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Get Quote for 20 Yard <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                    <div class="p-8 rounded-2xl shadow-xl flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002823.png" alt="30 Yard Dumpster" class="rounded-lg mb-6 shadow-md border border-gray-300">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">30 Yard Dumpster</h3>
                        <p class="text-gray-600 leading-relaxed mb-6">Great for major home additions, large construction sites, or commercial cleanouts. Holds about 9 pickup truck loads.</p>
                        <ul class="text-left text-gray-700 w-full mb-6 space-y-2">
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Large remodeling projects</li>
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>New home construction</li>
                            <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>Commercial cleanouts</li>
                        </ul>
                        <a href="#" class="text-blue-custom hover:underline font-medium flex items-center justify-center">Get Quote for 30 Yard <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg></a>
                    </div>
                </div>
                <div class="text-center mt-16">
                    <a href="#" class="btn-secondary inline-block">Need help choosing a size?</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32 bg-gray-50">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-16">Why Choose <?php echo htmlspecialchars($companyName); ?> for Dumpster Rentals?</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                    <div class="p-8 rounded-2xl shadow-xl bg-white flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002904.png" height="80" width="80" class="mb-6">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">AI-Powered Efficiency</h3>
                        <p class="text-gray-600 leading-relaxed">Our advanced AI matches your project needs with the best local dumpster providers, ensuring you get the right size and the best price without hassle. Get a quote in minutes!</p>
                    </div>
                    <div class="p-8 rounded-2xl shadow-xl bg-white flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002905.png" height="80" width="80" class="mb-6">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Transparent & Competitive Pricing</h3>
                        <p class="text-gray-600 leading-relaxed">No hidden fees, no surprises. We provide upfront, competitive pricing from our network, so you know exactly what you're paying for. Best price guaranteed.</p>
                    </div>
                    <div class="p-8 rounded-2xl shadow-xl bg-white flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002907.png" height="80" width="80" class="mb-6">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Flexible Scheduling</h3>
                        <p class="text-gray-600 leading-relaxed">Need a dumpster for a day, a week, or longer? Our flexible rental periods and prompt delivery/pickup services fit your project timeline perfectly.</p>
                    </div>
                    <div class="p-8 rounded-2xl shadow-xl bg-white flex flex-col items-center text-center">
                        <img src="/assets/images/Group 1000002906.png" height="80" width="80" class="mb-6">
                        <h3 class="text-2xl font-semibold text-gray-800 mb-4">Reliable Local Partners</h3>
                        <p class="text-gray-600 leading-relaxed">We partner only with vetted, reputable local dumpster companies to ensure you receive reliable service and clean, well-maintained equipment every time.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10">Ready to Start Your Project?</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto">
                    Get your free, no-obligation quote today and experience the easiest way to rent a dumpster. Our AI assistant is ready to help you find the perfect solution.
                </p>
                <a href="#" class="btn-primary inline-block">Get a Free Dumpster Quote</a>
            </div>
        </section>

        <section class="container-box py-20 md:py-32 section-box-alt">
            <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-16">Frequently Asked Questions</h2>
            <div class="max-w-3xl mx-auto">
                <div class="accordion-item">
                    <div class="accordion-header" data-accordion-toggle="faq-dumpster-1">
                        What can I put in a rental dumpster?
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    <div id="faq-dumpster-1" class="accordion-content">
                        <p>Most common household debris, construction and demolition debris, and yard waste are accepted. Prohibited items generally include hazardous waste, chemicals, tires, and appliances with refrigerants. We'll provide a detailed list upon booking.</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header" data-accordion-toggle="faq-dumpster-2">
                        How long can I keep the dumpster?
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    <div id="faq-dumpster-2" class="accordion-content">
                        <p>Standard rental periods vary, typically from 7 to 14 days. However, we offer flexible scheduling, and you can usually extend your rental for an additional daily fee. Just let us know through your dashboard!</p>
                    </div>
                </div>
                <div class="accordion-item">
                    <div class="accordion-header" data-accordion-toggle="faq-dumpster-3">
                        Do I need a permit for a dumpster rental?
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                    <div id="faq-dumpster-3" class="accordion-content">
                        <p>If the dumpster will be placed on private property (like your driveway), a permit is usually not required. However, if it needs to be placed on public property (street, sidewalk), a city permit might be necessary. Our team can advise you on local regulations.</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-16">
                <a href="/Resources/FAQs.php" class="btn-secondary inline-block">View All FAQs</a>
            </div>
        </section>
    </main>

    <div id="floating-chat-trigger" onclick="showAIChat('create-booking');">
        <i class="fas fa-comment-dots"></i>
    </div>

    <?php include __DIR__ . '/../includes/public_footer.php'; ?>
</body>
</html>