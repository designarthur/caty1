<?php
// How-it-works.php

// Include necessary files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

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
    <title>How Catdump Works - Your Seamless Rental Journey</title>
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
        .text-blue-custom {
            color: #1a73e8;
        }

        .hero-background {
            background-image: url('/assets/images/11328876_12212.png'); /* Placeholder */
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .hero-overlay {
            background: linear-gradient(to right, rgba(248, 249, 250, 0.9), rgba(248, 249, 250, 0.6));
            position: absolute;
            inset: 0;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }

        .how-it-works-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .how-it-works-image-box {
            background-color: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 450px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 250px;
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
            font-size: 2.25rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .how-it-works-step-description {
            color: #4a5568;
            font-size: 1.1rem;
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
                width: 45%;
            }
        }
        
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
            transition: transform 0.3s;
        }
        #floating-chat-trigger:hover {
            transform: scale(1.1);
        }
        #floating-chat-trigger i {
            color: white;
            font-size: 1.5rem;
        }

    </style>
</head>
<body class="antialiased">

    <?php include 'includes/public_header.php'; ?>

    <main>
        <section id="hero-section" class="hero-background py-32 md:py-48 relative">
            <div class="hero-overlay"></div>
            <div class="container-box hero-content text-center">
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-extrabold leading-tight mb-8">
                    How <?php echo htmlspecialchars($companyName); ?> Works
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto">
                    Discover our simple, efficient process for renting equipment. From initial inquiry to final pick-up, we make securing your essential rentals effortless and transparent.
                </p>
                <a href="./customer/register.php" onclick="showAIChat('general'); return false;" class="btn-primary">Get Started Now!</a>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20">Our Simple 4-Step Process</h2>
                <div class="how-it-works-container">
                    <div class="how-it-works-row">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002805.png" alt="AI Chat Interface">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 1</p>
                            <h3 class="how-it-works-step-title">Define Your Needs via AI Chat</h3>
                            <p class="how-it-works-step-description">Begin by interacting with our intelligent AI booking system. Simply tell us what equipment you need, your desired dates, location, and any specific project requirements. Our AI is designed to understand natural language, ensuring all essential details are captured accurately and efficiently.</p>
                            <a href="#" onclick="showAIChat('general'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Start Chatting Now &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002803.png" alt="Price Comparison">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 2</p>
                            <h3 class="how-it-works-step-title">Instantly Compare Top Local Offers</h3>
                            <p class="how-it-works-step-description">Once your requirements are clear, our proprietary AI engine gets to work. It instantly analyzes real-time availability and pricing from our extensive, vetted network of local suppliers and presents you with the best-matched, most competitive quotes directly in your dashboard.</p>
                            <a href="/customer/login.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">View Your Dashboard &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002801.png" alt="Dashboard Tracking">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 3</p>
                            <h3 class="how-it-works-step-title">Secure Your Rental & Track Delivery</h3>
                            <p class="how-it-works-step-description">Found the perfect quote? Confirm your order with a single click. You'll receive a secure payment link and your invoice will be conveniently added to your dashboard. For many partners, you can track the real-time delivery status of your equipment, keeping you informed every step of the way.</p>
                             <a href="/customer/login.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Confirm Your Order &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row">
                        <div class="how-it-works-image-box">
                             <img src="/assets/images/Group 1000002800.png" alt="Project Management">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Step 4</p>
                            <h3 class="how-it-works-step-title">Effortless Project Management & Support</h3>
                            <p class="how-it-works-step-description">Your dashboard remains your central command center. Extend a rental, arrange a swap, or schedule a pick-up with a few clicks. Our dedicated customer support team is always just a message or call away, ensuring any questions are addressed promptly.</p>
                            <a href="/Resources/Support-Center.php" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Contact Support &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10">Ready to Experience the Difference?</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto">
                    Simplify your equipment rental process today. Our intuitive platform and powerful AI are designed to save you time, money, and hassle.
                </p>
                <a href="./customer/register.php" onclick="showAIChat('general'); return false;" class="btn-primary inline-block">Start Your Rental Journey!</a>
            </div>
        </section>

    </main>

    <div id="floating-chat-trigger" onclick="showAIChat('general');">
        <i class="fas fa-comment-dots"></i>
    </div>

    <?php include 'includes/public_footer.php'; ?>

</body>
</html>