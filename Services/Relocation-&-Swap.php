<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relocation & Swap Services - Catdump: Adaptable Equipment Rentals</title>
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
            background-image: url('/assets/images/relocation and swap.png');
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

        .scenario-card {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }
        .scenario-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .scenario-card .icon-large {
            font-size: 3.5rem;
            color: #1a73e8;
            margin-bottom: 1.5rem;
        }
        .scenario-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.75rem;
        }
        .scenario-card p {
            font-size: 1.0rem; /* Adjusted for consistency */
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
                    Flexible Rental Solutions: <span class="text-blue-custom">Relocation & Swap Services</span>
                </h1>
                <p class="text-xl md:text-2xl lg:text-3xl text-gray-700 mb-12 max-w-5xl mx-auto animate-on-scroll delay-300">
                    Project running longer? Need a different size unit? Catdump adapts to your changing needs with seamless rental extensions, unit swaps, and on-site relocations.
                </p>
                <a href="../request_quote.php" class="btn-primary inline-block animate-on-scroll delay-600">Request a Service Change Now!</a>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <div class="text-center mb-8 animate-on-scroll delay-100">
                    <span class="text-blue-custom text-lg font-semibold uppercase">Why Catdump for Flexibility?</span>
                    <h2 class="text-4xl md:text-5xl font-extrabold text-gray-800 mt-2">Adapt Your Rentals, Keep Your Project on Track</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="feature-card animate-on-scroll delay-200">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002917.png" width="80" height="80">
                        </div>
                        <h4>Seamless Project Continuity</h4>
                        <p>Avoid delays and disruptions. Our services ensure your equipment needs evolve with your project, preventing downtime and keeping work flowing smoothly.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-300">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002914.png" width="80" height="80">
                        </div>
                        <h4>Cost-Efficient Adjustments</h4>
                        <p>Modifying existing rentals can often be more cost-effective than starting new ones. Our system helps you find the most economical solution for changes.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-400">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002915.png" width="80" height="80">
                        </div>
                        <h4>Dashboard-Driven Simplicity</h4>
                        <p>Request extensions, swaps, or relocations directly through your intuitive Catdump dashboard with just a few clicks, making management effortless.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-500">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 10000029191.png" width="80" height="80">
                        </div>
                        <h4>Wide Range of Adaptability</h4>
                        <p>Whether it's a longer rental, a different size dumpster, or moving a storage container, we offer solutions to fit diverse project evolutions.</p>
                    </div>

                    <div class="feature-card animate-on-scroll delay-600">
                        <div class="icon-wrapper">
                            <img src="/assets/images/Group 1000002916.png" width="80" height="80">
                        </div>
                        <h4>Expert Support On-Demand</h4>
                        <p>Our dedicated team is ready to guide you through any rental adjustment, ensuring clear communication and a smooth transition for your equipment.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">How Catdump's Relocation & Swap Services Work</h2>
                <div class="how-it-works-container">
                    <div class="how-it-works-row animate-on-scroll delay-100">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002843.png" alt="Extend Rental Period">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Scenario 1</p>
                            <h3 class="how-it-works-step-title">Extend Your Rental Period</h3>
                            <p class="how-it-works-step-description">Project taking longer than expected? No problem. Simply log into your Catdump dashboard and select the "Extend Rental" option for your active equipment. Specify your new desired end date. We'll instantly provide updated pricing for the extension, and once confirmed, your rental period will be seamlessly adjusted without any hassle, keeping your project on schedule.</p>
                            <a href="#" onclick="showAIChat('create-booking'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Extend My Rental &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-300">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002842.png" alt="Swap Unit Sizes">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Scenario 2</p>
                            <h3 class="how-it-works-step-title">Swap Your Equipment for a Different Size</h3>
                            <p class="how-it-works-step-description">If your project's scope changes and you need a larger or smaller dumpster, storage container, or different type of portable toilet, use the "Request Swap" feature in your dashboard. Tell us the new specifications, and we'll provide new quotes from local suppliers. Once approved, we'll coordinate the efficient exchange: your current unit will be picked up, and the new one delivered promptly, minimizing disruption.</p>
                            <a href="#" onclick="showAIChat('create-booking'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Request a Swap &rarr;</a>
                        </div>
                    </div>

                    <div class="how-it-works-row animate-on-scroll delay-500">
                        <div class="how-it-works-image-box">
                            <img src="/assets/images/Group 1000002844.png" alt="Relocate Unit On-Site">
                        </div>
                        <div class="how-it-works-content">
                            <p class="how-it-works-step-number">Scenario 3</p>
                            <h3 class="how-it-works-step-title">Relocate Your Unit On-Site</h3>
                            <p class="how-it-works-step-description">Need your dumpster or storage container moved to a different spot on your property or job site? Our "Relocate Unit" option in the dashboard allows you to request an internal move. Provide the new desired location, and our team will confirm the logistics and any applicable fees. A professional crew will then safely and efficiently move your rental, ensuring it's exactly where you need it for optimal workflow.</p>
                            <a href="#" onclick="showAIChat('create-booking'); return false;" class="text-blue-custom hover:underline font-medium mt-4 inline-block">Initiate Relocation &rarr;</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">When Catdump's Flexibility Becomes Your Advantage</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div class="scenario-card animate-on-scroll delay-100">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002918.png" height="80" width="80"></div>
                        <h3>Project Runs Longer</h3>
                        <p>Unexpected delays happen. Seamlessly extend your rental period for days, weeks, or months directly from your dashboard to match your revised timeline.</p>
                    </div>
                    <div class="scenario-card animate-on-scroll delay-200">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002922.png" height="80" width="80"></div>
                        <h3>Need a Different Size</h3>
                        <p>Whether you underestimated or overestimated, easily swap your current unit for a larger or smaller one to perfectly fit the evolving demands of your project.</p>
                    </div>
                    <div class="scenario-card animate-on-scroll delay-300">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002921.png" height="80" width="80"></div>
                        <h3>Changing Site Layout</h3>
                        <p>As work progresses, your equipment might need to move. Request on-site relocation of dumpsters or containers to maintain optimal accessibility and workflow.</p>
                    </div>
                    <div class="scenario-card animate-on-scroll delay-400">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002919.png" height="80" width="80"></div>
                        <h3>Unexpected Volume/Waste</h3>
                        <p>When debris volume exceeds initial estimates, a quick swap to a larger dumpster ensures you can continue work without interruption or overflow issues.</p>
                    </div>
                    <div class="scenario-card animate-on-scroll delay-500">
                        <div class="icon-large" style="display:flex; justify-content:center;"><img src="/assets/images/Group 1000002920.png" height="80" width="80"></div>
                        <h3>Equipment Issues/Replacement</h3>
                        <p>In the rare event of an issue, quickly arrange for a swap or replacement unit to minimize downtime and keep your project moving forward without a hitch.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="testimonials-section">
            <div class="section-box-alt">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">What Our Customers Say About Our Flexible Services</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <div class="testimonial-card animate-on-scroll delay-100">
                        <p class="testimonial-quote">"Our renovation hit a snag, and we needed our dumpster for an extra week. Extending it through Catdump's dashboard was incredibly easy, literally a few clicks!"</p>
                        <p class="testimonial-author">- Alex G.</p>
                        <p class="testimonial-source">Homeowner, Extended Project</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-200">
                        <p class="testimonial-quote">"We underestimated our storage needs. Catdump arranged a swap for a larger container the very next day. Their adaptability saved our project a huge headache."</p>
                        <p class="testimonial-author">- Rachel S.</p>
                        <p class="testimonial-source">Site Coordinator, Commercial Build</p>
                    </div>
                    <div class="testimonial-card animate-on-scroll delay-300">
                        <p class="testimonial-quote">"Having our dumpster moved to a new section of the job site was crucial for efficiency. Catdump handled the relocation swiftly and professionally, no fuss at all."</p>
                        <p class="testimonial-author">- Ben L.</p>
                        <p class="testimonial-source">Foreman, Demolition Project</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="faq-section">
            <div class="section-box">
                <h2 class="text-4xl md:text-5xl font-bold text-center text-gray-800 mb-20 animate-on-scroll">Frequently Asked Questions About Relocation & Swap</h2>
                <div class="max-w-3xl mx-auto">
                    <div class="accordion-item animate-on-scroll delay-100">
                        <div class="accordion-header" data-accordion-toggle="faq-1">
                            What's the difference between "relocation" and "swap"?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-1" class="accordion-content">
                            <p>A "relocation" means moving the same rental unit (e.g., dumpster, storage container) from one spot to another on the same property or job site. A "swap" means exchanging your current rental unit for a different one, usually of a different size or type, typically at the same location. Both are easily requested via your dashboard.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-200">
                        <div class="accordion-header" data-accordion-toggle="faq-2">
                            Are there extra fees for extending, swapping, or relocating a unit?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-2" class="accordion-content">
                            <p>Yes, these services typically involve additional fees. Extensions are usually charged at a daily or weekly rate. Swaps and relocations incur a service fee, which covers the logistics and transport. All applicable costs will be clearly presented for your approval on your dashboard before you confirm the service change.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-300">
                        <div class="accordion-header" data-accordion-toggle="faq-3">
                            How much notice do I need to give for these services?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-3" class="accordion-content">
                            <p>For extensions, requests can often be made up to the last day of your rental, though earlier notice is always appreciated. For swaps and relocations, we recommend providing at least 24-48 hours notice to allow our partners to schedule efficiently and ensure timely service. Emergency requests may be possible at an additional charge.</p>
                        </div>
                    </div>
                    <div class="accordion-item animate-on-scroll delay-400">
                        <div class="accordion-header" data-accordion-toggle="faq-4">
                            Can I swap for a different type of equipment (e.g., dumpster to storage container)?
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                        <div id="faq-4" class="accordion-content">
                            <p>Currently, our swap service is designed for exchanging units of the same equipment type (e.g., dumpster for dumpster, storage container for storage container). If you need a completely different type of equipment, you would typically end your current rental and place a new order for the desired equipment. Our support team can assist you with this process.</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-16 animate-on-scroll delay-600">
                    <a href="/Resources/FAQs.php" class="btn-secondary inline-block">View All Flexible Service FAQs</a>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32">
            <div class="section-box-alt text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-10 animate-on-scroll">Your Project, Your Terms: Adapt with Catdump!</h2>
                <p class="text-xl text-gray-700 mb-12 max-w-3xl mx-auto animate-on-scroll delay-100">
                    Don't let changing project needs disrupt your progress. Catdump's flexible relocation and swap services keep you in control.
                </p>
                <a href="/customer/dashboard.php" class="btn-primary inline-block shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition duration-300 animate-on-scroll delay-200">Go to Customer Portal to Manage Rentals</a>
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