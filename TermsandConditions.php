<?php
// TermsandConditions.php - Terms and Conditions Page

// Ensure session is started and other PHP includes are at the very top
// No whitespace or HTML should precede this block.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php'; // This file contains session_start()

// Set page title for the header
$pageTitle = "Catdump - Terms and Conditions";

// Fetch company name from system settings if needed in the body
$companyName = getSystemSetting('company_name');
if (!$companyName) {
    $companyName = 'Catdump'; // Fallback if not set in DB
}

// Now include the public header, which can safely use session variables
include 'includes/public_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
        .text-blue-custom {
            color: #1a73e8;
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

        /* Specific styles for Terms and Conditions */
        .terms-heading {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a73e8;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .terms-subheading {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2d3748;
            margin-top: 2.5rem;
            margin-bottom: 1.25rem;
        }
        .terms-paragraph {
            font-size: 1.1rem;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 1.25rem;
        }
        .terms-list {
            list-style-type: disc;
            margin-left: 2rem;
            margin-bottom: 1.25rem;
            color: #4a5568;
        }
        .terms-list li {
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }
        .terms-list-nested {
            list-style-type: circle;
            margin-left: 1.5rem;
            margin-top: 0.5rem;
        }
        .terms-link {
            color: #1a73e8;
            text-decoration: underline;
            transition: color 0.2s ease;
        }
        .terms-link:hover {
            color: #155bb5;
        }
        .last-updated {
            text-align: center;
            font-size: 0.9rem;
            color: #718096;
            margin-top: 3rem;
            margin-bottom: 2rem;
        }
        .legal-notice {
            background-color: #f0f4f8;
            border-left: 5px solid #1a73e8;
            padding: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            color: #2d3748;
        }
        .legal-notice strong {
            color: #1a73e8;
        }

        /* Table of Contents Specific Styles */
        .toc-container {
            background-color: #f8f9fa;
            border-radius: 1rem;
            padding: 2.5rem;
            margin-bottom: 3.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .toc-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .toc-list {
            list-style-type: none;
            padding-left: 0;
        }
        .toc-list li {
            margin-bottom: 0.75rem;
        }
        .toc-list a {
            color: #1a73e8;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex; /* Use flexbox for icon/text alignment */
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        .toc-list a:hover {
            background-color: #eef2f6;
            color: #155bb5;
            transform: translateX(5px);
        }
        .toc-list .sub-item {
            padding-left: 1.5rem;
            font-size: 1rem;
            font-weight: 400;
            color: #4a5568;
        }
        .toc-list .sub-item:hover {
            color: #155bb5;
            background-color: #eef2f6; /* Apply hover background to sub-items too */
        }
        .toc-list .main-item-icon {
            margin-right: 0.75rem;
            color: #1a73e8; /* Icon color for main items */
        }
        .toc-list .sub-item-dash {
            margin-right: 0.5rem;
            color: #34a853; /* Dash color for sub items */
        }

        /* Contact Form Styles (from Homepage) */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }
        @media (min-width: 1024px) {
            .contact-grid {
                grid-template-columns: 1fr 1.5fr;
            }
        }

        .contact-info-box {
            background-color: #1a73e8;
            color: white;
            border-radius: 1.5rem;
            padding: 3rem;
            box-shadow: 0 15px 40px rgba(26, 115, 232, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 450px;
        }
        .contact-info-box h2 {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }
        .contact-info-box p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
        }
        .contact-info-box .testimonial-quote-contact {
            font-style: italic;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        .contact-info-box .testimonial-author-contact {
            font-weight: 600;
            color: white;
            margin-top: 0.5rem;
        }

        .contact-form-box {
            background-color: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 3rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .contact-form-box .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            color: #2d3748;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .contact-form-box .form-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        .contact-form-box textarea.form-input {
            min-height: 120px;
            resize: vertical;
        }
        .contact-form-box .form-label {
            display: block;
            font-size: 1rem;
            font-weight: 500;
            color: #4a5568;
        }
        .contact-form-box .privacy-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 1.5rem;
        }
        .contact-form-box .privacy-text a {
            color: #1a73e8;
            text-decoration: underline;
        }
        .contact-form-box .btn-submit {
            background-color: #1a73e8;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.4);
            width: 100%;
            margin-top: 2rem;
        }
        .contact-form-box .btn-submit:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
        }
    </style>
</head>
<body class="antialiased">
    <main>
        <section class="container-box py-20 md:py-32">
            <div class="section-box">
                <h1 class="terms-heading">Catdump Terms and Conditions</h1>
                <p class="last-updated">Last Updated: July 7, 2025</p>

                <p class="terms-paragraph">Welcome to Catdump! These Terms and Conditions ("Terms") govern your access to and use of the Catdump website, mobile applications, services, and products (collectively, the "Services"). By accessing or using our Services, you agree to be bound by these Terms and our Privacy Policy, which is incorporated herein by reference. If you do not agree to these Terms, please do not use our Services.</p>

                <div class="legal-notice">
                    <strong>Important Notice:</strong> These Terms contain a binding arbitration provision and a class action waiver. Please read them carefully. They affect your legal rights and remedies.
                </div>

                <div class="toc-container">
                    <h2 class="toc-title">Table of Contents</h2>
                    <ul class="toc-list">
                        <li><a href="#acceptance-of-terms"><span class="main-item-icon">&#x2022;</span> 1. Acceptance of Terms</a></li>
                        <li><a href="#description-of-services"><span class="main-item-icon">&#x2022;</span> 2. Description of Services</a></li>
                        <li>
                            <a href="#user-accounts"><span class="main-item-icon">&#x2022;</span> 3. User Accounts</a>
                            <ul class="toc-list-nested">
                                <li><a href="#account-registration" class="sub-item"><span class="sub-item-dash">&mdash;</span> 3.1 Account Registration</a></li>
                                <li><a href="#account-security" class="sub-item"><span class="sub-item-dash">&mdash;</span> 3.2 Account Security</a></li>
                                <li><a href="#eligibility" class="sub-item"><span class="sub-item-dash">&mdash;</span> 3.3 Eligibility</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#ordering-and-quotation-process"><span class="main-item-icon">&#x2022;</span> 4. Ordering and Quotation Process</a>
                            <ul class="toc-list-nested">
                                <li><a href="#requesting-a-quote" class="sub-item"><span class="sub-item-dash">&mdash;</span> 4.1 Requesting a Quote</a></li>
                                <li><a href="#quote-generation-and-presentation" class="sub-item"><span class="sub-item-dash">&mdash;</span> 4.2 Quote Generation and Presentation</a></li>
                                <li><a href="#quote-acceptance-and-order-confirmation" class="sub-item"><span class="sub-item-dash">&mdash;</span> 4.3 Quote Acceptance and Order Confirmation</a></li>
                                <li><a href="#pricing-and-fees" class="sub-item"><span class="sub-item-dash">&mdash;</span> 4.4 Pricing and Fees</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#payments-and-billing"><span class="main-item-icon">&#x2022;</span> 5. Payments and Billing</a>
                            <ul class="toc-list-nested">
                                <li><a href="#payment-methods" class="sub-item"><span class="sub-item-dash">&mdash;</span> 5.1 Payment Methods</a></li>
                                <li><a href="#payment-due-dates" class="sub-item"><span class="sub-item-dash">&mdash;</span> 5.2 Payment Due Dates</a></li>
                                <li><a href="#financing-options" class="sub-item"><span class="sub-item-dash">&mdash;</span> 5.3 Financing Options</a></li>
                                <li><a href="#refunds-and-disputes" class="sub-item"><span class="sub-item-dash">&mdash;</span> 5.4 Refunds and Disputes</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="#cancellations-changes-and-returns"><span class="main-item-icon">&#x2022;</span> 6. Cancellations, Changes, and Returns</a>
                            <ul class="toc-list-nested">
                                <li><a href="#cancellation-policy" class="sub-item"><span class="sub-item-dash">&mdash;</span> 6.1 Cancellation Policy</a></li>
                                <li><a href="#changes-to-orders" class="sub-item"><span class="sub-item-dash">&mdash;</span> 6.2 Changes to Orders (Relocation & Swap)</a></li>
                                <li><a href="#returns-and-pickups" class="sub-item"><span class="sub-item-dash">&mdash;</span> 6.3 Returns and Pickups</a></li>
                            </ul>
                        </li>
                        <li><a href="#user-responsibilities-and-conduct"><span class="main-item-icon">&#x2022;</span> 7. User Responsibilities and Conduct</a></li>
                        <li><a href="#intellectual-property"><span class="main-item-icon">&#x2022;</span> 8. Intellectual Property</a></li>
                        <li>
                            <a href="#disclaimers-and-limitations-of-liability"><span class="main-item-icon">&#x2022;</span> 9. Disclaimers and Limitation of Liability</a>
                            <ul class="toc-list-nested">
                                <li><a href="#disclaimer-of-warranties" class="sub-item"><span class="sub-item-dash">&mdash;</span> 9.1 Disclaimer of Warranties</a></li>
                                <li><a href="#limitation-of-liability" class="sub-item"><span class="sub-item-dash">&mdash;</span> 9.2 Limitation of Liability</a></li>
                            </ul>
                        </li>
                        <li><a href="#indemnification"><span class="main-item-icon">&#x2022;</span> 10. Indemnification</a></li>
                        <li>
                            <a href="#governing-law-and-dispute-resolution"><span class="main-item-icon">&#x2022;</span> 11. Governing Law and Dispute Resolution</a>
                            <ul class="toc-list-nested">
                                <li><a href="#governing-law" class="sub-item"><span class="sub-item-dash">&mdash;</span> 11.1 Governing Law</a></li>
                                <li><a href="#binding-arbitration" class="sub-item"><span class="sub-item-dash">&mdash;</span> 11.2 Binding Arbitration</a></li>
                                <li><a href="#class-action-waiver" class="sub-item"><span class="sub-item-dash">&mdash;</span> 11.3 Class Action Waiver</a></li>
                            </ul>
                        </li>
                        <li><a href="#privacy-policy"><span class="main-item-icon">&#x2022;</span> 12. Privacy Policy</a></li>
                        <li><a href="#severability"><span class="main-item-icon">&#x2022;</span> 13. Severability</a></li>
                        <li><a href="#entire-agreement"><span class="main-item-icon">&#x2022;</span> 14. Entire Agreement</a></li>
                        <li><a href="#force-majeure"><span class="main-item-icon">&#x2022;</span> 15. Force Majeure</a></li>
                        <li><a href="#feedback-and-contact-information"><span class="main-item-icon">&#x2022;</span> 16. Feedback and Contact Information</a></li>
                    </ul>
                </div>

                <h2 class="terms-subheading" id="acceptance-of-terms">1. Acceptance of Terms</h2>
                <p class="terms-paragraph">By creating an account, accessing our website, utilizing our AI booking system, requesting quotes, or engaging in any transaction through Catdump, you acknowledge that you have read, understood, and agree to be bound by these Terms. This agreement is effective immediately upon your first use of the Services and continues for as long as you use the Services. These Terms constitute a legally binding agreement between you ("User," "Client," or "Customer") and Catdump ("Catdump," "we," "us," or "our"). We reserve the right to update or modify these Terms at any time without prior notice. Your continued use of the Services following any such changes constitutes your acceptance of the new Terms. We recommend reviewing these Terms periodically to stay informed of any updates.</p>

                <h2 class="terms-subheading" id="description-of-services">2. Description of Services</h2>
                <p class="terms-paragraph">Catdump operates as an online marketplace connecting users with local, vetted equipment rental and related service providers ("Suppliers"). Our Services include, but are not limited to, the facilitation of:</p>
                <ul class="terms-list">
                    <li><strong>Dumpster Rentals:</strong> Various sizes and types for waste disposal.</li>
                    <li><strong>Temporary Toilets:</strong> Portable sanitation solutions for events and construction sites.</li>
                    <li><strong>Storage Containers:</strong> Secure, on-site storage units.</li>
                    <li><strong>Junk Removal Services:</strong> Facilitated through AI-powered visual quotation systems.</li>
                    <li><strong>Relocation & Swap Services:</strong> For existing rental units.</li>
                    <li><strong>AI Booking System:</strong> An intelligent chat interface for initial inquiries and quote requests.</li>
                    <li><strong>Customer Dashboard:</strong> A personalized portal for managing orders, tracking deliveries, viewing invoices, and communicating with Suppliers.</li>
                    <li><strong>Payment Processing:</strong> Secure and flexible payment options, including financing.</li>
                </ul>
                <p class="terms-paragraph">Catdump acts solely as an intermediary and marketplace provider. While we strive to connect you with reliable Suppliers and ensure competitive pricing, we do not directly own, operate, or provide the rental equipment or services ourselves. The actual rental agreements and service execution are between you and the respective Supplier. Catdump's responsibility is limited to facilitating the connection and providing the platform tools.</p>

                <h2 class="terms-subheading" id="user-accounts">3. User Accounts</h2>
                <h3 class="terms-subheading" id="account-registration" style="font-size: 1.3rem;">3.1 Account Registration</h3>
                <p class="terms-paragraph">To access certain features of our Services, such as requesting quotes, placing orders, and managing rentals, you must register for a Catdump account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete. Failure to do so may result in the suspension or termination of your account.</p>
                <h3 class="terms-subheading" id="account-security" style="font-size: 1.3rem;">3.2 Account Security</h3>
                <p class="terms-paragraph">You are responsible for maintaining the confidentiality of your account password and for all activities that occur under your account. You agree to notify Catdump immediately of any unauthorized use of your account or any other breach of security. Catdump cannot and will not be liable for any loss or damage arising from your failure to comply with this security obligation.</p>
                <h3 class="terms-subheading" id="eligibility" style="font-size: 1.3rem;">3.3 Eligibility</h3>
                <p class="terms-paragraph">You must be at least 18 years old and capable of forming a binding contract to use our Services. By registering an account, you represent and warrant that you meet these eligibility requirements. Catdump reserves the right to refuse service, terminate accounts, or cancel orders at its sole discretion.</p>

                <h2 class="terms-subheading" id="ordering-and-quotation-process">4. Ordering and Quotation Process</h2>
                <h3 class="terms-subheading" id="requesting-a-quote" style="font-size: 1.3rem;">4.1 Requesting a Quote</h3>
                <p class="terms-paragraph">Our AI booking system facilitates the initial request for quotes. You will provide details about your equipment needs, project, location, and timeline. This information is crucial for our system to accurately process your request and for our network of Suppliers to provide relevant quotes. For services like junk removal, you may be required to upload visual media (images, videos) for AI analysis and quotation generation. You warrant that all information provided is accurate and that any uploaded media accurately represents the items for removal.</p>
                <h3 class="terms-subheading" id="quote-generation-and-presentation" style="font-size: 1.3rem;">4.2 Quote Generation and Presentation</h3>
                <p class="terms-paragraph">Upon receiving your request, Catdump's system will engage with our network of vetted local Suppliers to obtain competitive pricing. These quotes will be presented to you on your personalized customer dashboard. Each quote will include details such as pricing, rental duration, delivery fees, pickup fees, and any specific terms or conditions from the individual Supplier. Catdump strives to present the best available options based on your criteria, but we do not guarantee the lowest price from all possible providers outside our network.</p>
                <h3 class="terms-subheading" id="quote-acceptance-and-order-confirmation" style="font-size: 1.3rem;">4.3 Quote Acceptance and Order Confirmation</h3>
                <p class="terms-paragraph">You can review and accept a quote directly from your dashboard. By accepting a quote, you are entering into a direct contractual agreement with the respective Supplier for the provision of the equipment or service. Catdump's role shifts from a facilitator of quotes to a manager of the transaction on your behalf. An invoice will be generated and made available on your dashboard for payment. Order confirmation will be sent to you via email and reflected in your dashboard.</p>
                <h3 class="terms-subheading" id="pricing-and-fees" style="font-size: 1.3rem;">4.4 Pricing and Fees</h3>
                <p class="terms-paragraph">All prices quoted are subject to change until an order is confirmed and paid for. Prices typically include the rental fee for the specified period, delivery, and initial pickup. Additional fees may apply for extended rental periods, overweight charges, prohibited items, damage to equipment, difficult access, or cancellation fees. These potential additional fees will be outlined in the Supplier's terms, which you agree to review and accept upon order confirmation. Catdump will clearly communicate all known fees before payment.</p>

                <h2 class="terms-subheading" id="payments-and-billing">5. Payments and Billing</h2>
                <h3 class="terms-subheading" id="payment-methods" style="font-size: 1.3rem;">5.1 Payment Methods</h3>
                <p class="terms-paragraph">Catdump accepts various secure payment methods, including major credit cards (Visa, MasterCard, American Express, Discover) and ACH transfers. All transactions are processed through secure, encrypted payment gateways. We do not store your full credit card details on our servers.</p>
                <h3 class="terms-subheading" id="payment-due-dates" style="font-size: 1.3rem;">5.2 Payment Due Dates</h3>
                <p class="terms-paragraph">Payment for rental services is generally due in full at the time of order confirmation, unless a specific financing plan or payment schedule has been agreed upon. For services with variable costs (e.g., junk removal based on weight), an initial deposit may be required, with the final payment due upon completion of the service and accurate assessment of final charges.</p>
                <h3 class="terms-subheading" id="financing-options" style="font-size: 1.3rem;">5.3 Financing Options</h3>
                <p class="terms-paragraph">For eligible clients and larger projects, Catdump may offer flexible financing plans. The terms and conditions of such financing will be governed by a separate agreement, which must be reviewed and accepted by you prior to availing the financing. Eligibility for financing is determined solely by Catdump and/or its third-party financing partners.</p>
                <h3 class="terms-subheading" id="refunds-and-disputes" style="font-size: 1.3rem;">5.4 Refunds and Disputes</h3>
                <p class="terms-paragraph">Refunds are issued in accordance with our Cancellation Policy (see Section 6). If you have a billing dispute, you must notify Catdump within 30 days of the invoice date. We will investigate the dispute in collaboration with the relevant Supplier to reach a fair resolution. Excessive or unfounded disputes may result in account suspension or termination.</p>

                <h2 class="terms-subheading" id="cancellations-changes-and-returns">6. Cancellations, Changes, and Returns</h2>
                <h3 class="terms-subheading" id="cancellation-policy" style="font-size: 1.3rem;">6.1 Cancellation Policy</h3>
                <p class="terms-paragraph">Cancellation requests must be made through your Catdump dashboard or by contacting our support team. The following cancellation fees may apply:</p>
                <ul class="terms-list terms-list-nested">
                    <li>Cancellations made 24 hours or more before the scheduled delivery time: Full refund, minus a small processing fee (if applicable).</li>
                    <li>Cancellations made less than 24 hours before the scheduled delivery time: A cancellation fee equivalent to a percentage of the total rental cost or a flat fee may be charged to cover dispatch and administrative costs incurred by the Supplier.</li>
                    <li>Same-day cancellations or refusal of delivery upon arrival: May result in no refund and the full charge of the rental.</li>
                </ul>
                <p class="terms-paragraph">Specific Supplier cancellation policies may vary and will be communicated at the time of booking. It is your responsibility to review these policies.</p>
                <h3 class="terms-subheading" id="changes-to-orders" style="font-size: 1.3rem;">6.2 Changes to Orders (Relocation & Swap)</h3>
                <p class="terms-paragraph">Requests for relocation or swapping of rental units must be submitted through your dashboard or by contacting support. Such requests are subject to Supplier availability and may incur additional fees, including but not limited to, transportation fees, new rental period charges, or restocking fees for swapped units. Catdump will provide a revised quote for any changes, which must be accepted by you before the change is implemented.</p>
                <h3 class="terms-subheading" id="returns-and-pickups" style="font-size: 1.3rem;">6.3 Returns and Pickups</h3>
                <p class="terms-paragraph">Equipment pickup will be scheduled based on your initial rental period. It is your responsibility to ensure the equipment is accessible and ready for pickup at the agreed-upon time. Failure to do so may result in additional charges, including trip fees or extended rental fees. Please ensure the equipment is emptied (for dumpsters) and in the same condition as received, reasonable wear and tear excepted.</p>

                <h2 class="terms-subheading" id="user-responsibilities-and-conduct">7. User Responsibilities and Conduct</h2>
                <p class="terms-paragraph">As a user of Catdump Services, you agree to:</p>
                <ul class="terms-list">
                    <li>Provide accurate and truthful information in all communications and transactions.</li>
                    <li>Comply with all applicable local, state, and federal laws and regulations regarding the use of rented equipment and waste disposal.</li>
                    <li>Use the rented equipment responsibly and in accordance with the Supplier's guidelines and instructions.</li>
                    <li>Not place prohibited materials (e.g., hazardous waste, tires, batteries, medical waste, paint) into dumpsters. Violation will result in significant fines and fees, for which you will be solely responsible.</li>
                    <li>Ensure safe and clear access for delivery and pickup of equipment. Obstructions or unsafe conditions may result in delayed service and additional charges.</li>
                    <li>Be solely responsible for any damage to the rented equipment beyond normal wear and tear, or any damage caused to your property or surrounding areas during the rental period due to your negligence or misuse.</li>
                    <li>Treat all Suppliers and Catdump staff with respect and professionalism.</li>
                    <li>Not use the Services for any unlawful, fraudulent, or malicious purpose.</li>
                    <li>Not attempt to disrupt or interfere with the integrity or performance of the Services.</li>
                </ul>

                <h2 class="terms-subheading" id="intellectual-property">8. Intellectual Property</h2>
                <p class="terms-paragraph">All content on the Catdump website and mobile applications, including text, graphics, logos, images, software, and the compilation thereof, is the property of Catdump or its content suppliers and protected by copyright and other intellectual property laws. The Catdump name, logo, and other trademarks are proprietary trademarks of Catdump. You may not use these without our prior written permission.</p>
                <p class="terms-paragraph">You retain ownership of any content you submit or upload to the Services (e.g., images for junk removal quotes). However, by submitting content, you grant Catdump a worldwide, non-exclusive, royalty-free, transferable license to use, reproduce, distribute, prepare derivative works of, display, and perform the content in connection with the Services and Catdump's (and its successors' and affiliates') business, including for promoting and redistributing part or all of the Services (and derivative works thereof) in any media formats and through any media channels.</p>

                <h2 class="terms-subheading" id="disclaimers-and-limitations-of-liability">9. Disclaimers and Limitation of Liability</h2>
                <h3 class="terms-subheading" id="disclaimer-of-warranties" style="font-size: 1.3rem;">9.1 Disclaimer of Warranties</h3>
                <p class="terms-paragraph">The Services are provided on an "as is" and "as available" basis, without any warranties of any kind, either express or implied, including, but not limited to, implied warranties of merchantability, fitness for a particular purpose, non-infringement, or course of performance. Catdump does not warrant that the Services will be uninterrupted, secure, or error-free, that defects will be corrected, or that the Services or the servers that make them available are free of viruses or other harmful components.</p>
                <p class="terms-paragraph">Catdump does not guarantee the availability, quality, or suitability of any equipment or services provided by Suppliers. While we vet our Suppliers, we are not responsible for their direct actions, negligence, or failures to deliver as promised. Any claims or disputes regarding the physical equipment or direct service provision must be directed to the respective Supplier.</p>
                <h3 class="terms-subheading" id="limitation-of-liability" style="font-size: 1.3rem;">9.2 Limitation of Liability</h3>
                <p class="terms-paragraph">To the fullest extent permitted by applicable law, in no event shall Catdump, its affiliates, directors, employees, agents, or licensors be liable for any indirect, punitive, incidental, special, consequential, or exemplary damages, including without limitation, damages for loss of profits, goodwill, use, data, or other intangible losses, arising out of or relating to your use of, or inability to use, the Services.</p>
                <p class="terms-paragraph">In no event shall Catdump's total liability to you for all damages, losses, and causes of action exceed the amount paid by you to Catdump for the specific Services giving rise to the liability in the twelve (12) months preceding the date of the claim.</p>
                <p class="terms-paragraph">This limitation of liability applies whether the alleged liability is based on contract, tort, negligence, strict liability, or any other basis, even if Catdump has been advised of the possibility of such damage. Some jurisdictions do not allow the exclusion of certain warranties or the limitation or exclusion of liability for incidental or consequential damages, so some of the above limitations may not apply to you.</p>

                <h2 class="terms-subheading" id="indemnification">10. Indemnification</h2>
                <p class="terms-paragraph">You agree to indemnify, defend, and hold harmless Catdump, its affiliates, officers, directors, employees, agents, and licensors from and against any and all claims, liabilities, damages, losses, costs, expenses, or fees (including reasonable attorneys' fees) arising from or relating to:</p>
                <ul class="terms-list">
                    <li>Your access to or use of the Services.</li>
                    <li>Your violation of these Terms.</li>
                    <li>Your violation of any rights of a third party, including Suppliers.</li>
                    <li>Any content or information you submit, post, or transmit through the Services.</li>
                    <li>Your negligence or willful misconduct in connection with the rented equipment or services.</li>
                </ul>

                <h2 class="terms-subheading" id="governing-law-and-dispute-resolution">11. Governing Law and Dispute Resolution</h2>
                <h3 class="terms-subheading" id="governing-law" style="font-size: 1.3rem;">11.1 Governing Law</h3>
                <p class="terms-paragraph">These Terms shall be governed by and construed in accordance with the laws of the jurisdiction where Catdump is registered, without regard to its conflict of law principles. For users in the United Arab Emirates, this will be the laws of the Emirate of Ajman and the federal laws of the United Arab Emirates.</p>
                <h3 class="terms-subheading" id="binding-arbitration" style="font-size: 1.3rem;">11.2 Binding Arbitration</h3>
                <p class="terms-paragraph">Any dispute, controversy, or claim arising out of or relating to these Terms or the breach, termination, or validity thereof, shall be finally settled by arbitration in accordance with the rules of the Dubai International Arbitration Centre (DIAC). The number of arbitrators shall be one. The seat of arbitration shall be Dubai, United Arab Emirates. The language of the arbitration shall be English.</p>
                <h3 class="terms-subheading" id="class-action-waiver" style="font-size: 1.3rem;">11.3 Class Action Waiver</h3>
                <p class="terms-paragraph">You agree that any arbitration or proceeding shall be limited to the dispute between Catdump and you individually. To the full extent permitted by law, (i) no arbitration or proceeding shall be joined with any other; (ii) there is no right or authority for any dispute to be arbitrated or resolved on a class-action basis or to utilize class action procedures; and (iii) there is no right or authority for any dispute to be brought in a purported representative capacity on behalf of the general public or any other persons. YOU AGREE THAT YOU MAY BRING CLAIMS AGAINST CATDUMP ONLY IN YOUR INDIVIDUAL CAPACITY, AND NOT AS A PLAINTIFF OR CLASS MEMBER IN ANY PURPORTED CLASS OR REPRESENTATIVE PROCEEDING.</p>

                <h2 class="terms-subheading" id="privacy-policy">12. Privacy Policy</h2>
                <p class="terms-paragraph">Your use of our Services is also governed by our Privacy Policy, which details how we collect, use, and protect your personal information. By using the Services, you consent to the data practices described in our <a href="#" class="terms-link">Privacy Policy</a>.</p>

                <h2 class="terms-subheading" id="severability">13. Severability</h2>
                <p class="terms-paragraph">If any provision of these Terms is found to be unenforceable or invalid, that provision will be limited or eliminated to the minimum extent necessary so that these Terms will otherwise remain in full force and effect and enforceable.</p>

                <h2 class="terms-subheading" id="entire-agreement">14. Entire Agreement</h2>
                <p class="terms-paragraph">These Terms, together with the Privacy Policy and any other legal notices published by Catdump on the Services, constitute the entire agreement between you and Catdump concerning the Services, and supersede all prior or contemporaneous agreements, communications, and proposals, whether oral or written, between you and Catdump (including, but not limited to, any prior versions of the Terms and Conditions).</p>

                <h2 class="terms-subheading" id="force-majeure">15. Force Majeure</h2>
                <p class="terms-paragraph">Catdump shall not be liable for any failure or delay in performance under these Terms for causes beyond its reasonable control, including, but not limited to, acts of God, war, terrorism, riots, embargoes, acts of civil or military authorities, fire, floods, accidents, strikes, or shortages of transportation facilities, fuel, energy, labor, or materials.</p>

                <h2 class="terms-subheading" id="feedback-and-contact-information">16. Feedback and Contact Information</h2>
                <p class="terms-paragraph">We welcome your feedback regarding these Terms or our Services. If you have any questions, concerns, or comments, please contact us through the methods provided on our Contact page or directly via email:</p>
                <ul class="terms-list">
                    <li>Email: <a href="mailto:support@catdump.com" class="terms-link">support@catdump.com</a></li>
                    <li>Physical Address: [Insert Catdump's Physical Address Here]</li>
                </ul>
                <p class="terms-paragraph">All legal notices or service of process must be sent to the physical address provided above.</p>

                <div class="text-center mt-12">
                    <p class="terms-paragraph">Thank you for choosing Catdump. We are committed to providing you with an excellent equipment rental experience.</p>
                </div>
            </div>
        </section>

        <section class="container-box py-20 md:py-32" id="contact-section">
            <div class="section-box-alt">
                <div class="contact-grid">
                    <div class="contact-info-box">
                        <div class="flex items-center mb-6">
                            <img src="/assets/images/logocatdump.png" alt="Catdump Icon" class="h-10 w-10 mr-3 rounded-full">
                            <span class="text-xl font-semibold">Catdump Support</span>
                        </div>
                        <h2 class="text-white">Request a call with our <br> Equipment Experts</h2>
                        <p>Request a call with our equipment experts, and let's bring your vision to life! Our team is ready to assist you in creating an unforgettable experience tailored to your needs.</p>
                        <div class="testimonial-quote-contact">
                            "Catdump made our event unforgettable! Their attention to detail were beyond impressive."
                            <p class="testimonial-author-contact">- Fiona Jonna</p>
                            <p class="testimonial-source-contact">PS Global Partner Services</p>
                        </div>
                    </div>
                    <div class="contact-form-box">
                        <div class="flex mb-8">
                            <button class="flex-1 py-3 px-4 rounded-lg font-semibold text-blue-custom border border-blue-custom bg-blue-50 w-full">Contact via email</button>
                        </div>
                        <form class="space-y-4">
                            <div>
                                <label for="contact-first-name" class="form-label">Your first name</label>
                                <input type="text" id="contact-first-name" name="first_name" class="form-input" placeholder="Enter your first name" required>
                            </div>
                            <div>
                                <label for="contact-last-name" class="form-label">Your last name</label>
                                <input type="text" id="contact-last-name" name="last_name" class="form-input" placeholder="Enter your last name" required>
                            </div>
                            <div>
                                <label for="contact-email" class="form-label">Email</label>
                                <input type="email" id="contact-email" name="email" class="form-input" placeholder="Enter your email" required>
                            </div>
                            <div>
                                <label for="contact-message" class="form-label">How can we help you?</label>
                                <textarea id="contact-message" name="message" class="form-input" placeholder="Tell us a little about your project"></textarea>
                            </div>
                            <button type="submit" class="btn-submit">Send message</button>
                        </form>
                        <p class="privacy-text">By clicking on "send message" button, you agree to our <a href="#">Privacy Policy</a>.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

</body>
<?php include 'includes/public_footer.php'; ?>
</html>