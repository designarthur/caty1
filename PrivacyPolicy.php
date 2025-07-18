<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Catdump</title>
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
        .header-scrolled {
            background-color: rgba(255, 255, 255, 0.98);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
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
        .mobile-nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .mobile-nav-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        .mobile-nav-content {
            background-color: #ffffff;
            padding: 3rem;
            border-radius: 1.5rem;
            text-align: center;
            transform: translateY(-50px);
            opacity: 0;
            transition: transform 0.5s ease, opacity 0.5s ease;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .mobile-nav-overlay.open .mobile-nav-content {
            transform: translateY(0);
            opacity: 1;
        }
        .mobile-nav-content a {
            color: #2d3748;
            transition: color 0.3s ease;
            font-size: 2rem;
            font-weight: 600;
        }
        .mobile-nav-content a:hover {
            color: #1a73e8;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #ffffff;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0.5rem;
            overflow: hidden;
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
        }
        .dropdown-content a {
            color: #2d3748;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
            font-weight: 500;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .dropdown-content a:hover {
            background-color: #eef2f6;
            color: #1a73e8;
        }
        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        .mobile-dropdown-content {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .mobile-dropdown-content.open {
            max-height: 300px;
            opacity: 1;
        }
        .mobile-dropdown-content a {
            padding: 0.75rem 0;
            color: #4a5568;
            font-size: 1.5rem;
        }
        .mobile-dropdown-content a:hover {
            color: #1a73e8;
        }
    </style>
</head>
<body class="antialiased">
    <?php include 'includes/public_header.php'; ?>

    <!-- Mobile Navigation Overlay -->
    <div id="mobile-nav-overlay" class="mobile-nav-overlay">
        <div class="mobile-nav-content">
            <button id="close-mobile-menu" class="absolute top-6 right-6 p-3 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-custom">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <nav class="flex flex-col space-y-6">
                <a href="Homepage.html" class="text-gray-700 hover:text-blue-custom">How It Works</a>
                
                <div class="relative">
                    <a href="#" class="text-gray-700 hover:text-blue-custom flex items-center justify-center" data-dropdown-toggle="mobile-services-dropdown">
                        Services
                        <svg class="w-6 h-6 ml-2 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-dropdown-arrow="mobile-services-dropdown"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div id="mobile-services-dropdown" class="mobile-dropdown-content flex flex-col items-center">
                        <a href="#">Dumpster Rentals</a>
                        <a href="#">Temporary Toilets</a>
                        <a href="#">Storage Containers</a>
                        <a href="#">Junk Removal</a>
                        <a href="#">Relocation & Swap</a>
                    </div>
                </div>

                <div class="relative">
                    <a href="#" class="text-gray-700 hover:text-blue-custom flex items-center justify-center" data-dropdown-toggle="mobile-company-dropdown">
                        Company
                        <svg class="w-6 h-6 ml-2 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-dropdown-arrow="mobile-company-dropdown"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div id="mobile-company-dropdown" class="mobile-dropdown-content flex flex-col items-center">
                        <a href="#">About Us</a>
                        <a href="#">Careers</a>
                        <a href="#">Press/Media</a>
                        <a href="#">Sustainability</a>
                        <a href="#">Testimonials</a>
                    </div>
                </div>

                <div class="relative">
                    <a href="#" class="text-gray-700 hover:text-blue-custom flex items-center justify-center" data-dropdown-toggle="mobile-resources-dropdown">
                        Resources
                        <svg class="w-6 h-6 ml-2 transform transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-dropdown-arrow="mobile-resources-dropdown"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                    <div id="mobile-resources-dropdown" class="mobile-dropdown-content flex flex-col items-center">
                        <a href="#">Pricing & Finance</a>
                        <a href="#">Customer Resources</a>
                        <a href="#">Blog/News</a>
                        <a href="#">FAQs</a>
                        <a href="#">Support Center</a>
                        <a href="#">Contact</a>
                    </div>
                </div>
                <a href="#" class="btn-primary py-2.5 px-5 text-base shadow-md hover:shadow-lg transition duration-300">Customer Portal</a>
            </nav>
        </div>
    </div>

    <main class="py-20 md:py-32">
        <section class="container-box">
            <div class="section-box">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800 mb-8 text-center">Privacy Policy</h1>
                <p class="text-gray-600 mb-12 text-center">Last updated: July 7, 2025</p>

                <div class="prose max-w-none text-gray-700 leading-relaxed">
                    <nav class="mb-12 p-6 bg-blue-50 rounded-lg shadow-inner">
                        <h2 class="text-2xl font-bold text-blue-custom mb-4">Table of Contents</h2>
                        <ul class="list-disc pl-6 space-y-2 text-lg">
                            <li><a href="#interpretation-definitions" class="hover:underline text-blue-700">Interpretation and Definitions</a></li>
                            <li><a href="#collecting-using-data" class="hover:underline text-blue-700">Collecting and Using Your Personal Data</a></li>
                            <li><a href="#retention-data" class="hover:underline text-blue-700">Retention of Your Personal Data</a></li>
                            <li><a href="#transfer-data" class="hover:underline text-blue-700">Transfer of Your Personal Data</a></li>
                            <li><a href="#disclosure-data" class="hover:underline text-blue-700">Disclosure of Your Personal Data</a></li>
                            <li><a href="#security-data" class="hover:underline text-blue-700">Security of Your Personal Data</a></li>
                            <li><a href="#data-protection-rights" class="hover:underline text-blue-700">Your Data Protection Rights (e.g., CCPA)</a></li>
                            <li><a href="#childrens-privacy" class="hover:underline text-blue-700">Children's Privacy</a></li>
                            <li><a href="#links-to-other-websites" class="hover:underline text-blue-700">Links to Other Websites</a></li>
                            <li><a href="#changes-to-policy" class="hover:underline text-blue-700">Changes to this Privacy Policy</a></li>
                            <li><a href="#contact-us" class="hover:underline text-blue-700">Contact Us</a></li>
                        </ul>
                    </nav>

                    <p class="mb-6">This Privacy Policy describes how CAT Dump ("Company," "We," "Us," or "Our") collects, uses, and shares the personal data of our users (referred to as "Members" and "Visitors") through our website, applications, and software (collectively, the "Service"). It also informs You about Your privacy rights and how the law protects You.</p>
                    <p class="mb-6">By using the Service, You agree to the collection and use of information in accordance with this Privacy Policy. We use Your Personal Data to provide, support, personalize, and develop our services.</p>

                    <h2 id="interpretation-definitions" class="text-3xl font-bold text-gray-800 mt-10 mb-6">1. Interpretation and Definitions</h2>
                    <h3 class="text-2xl font-semibold text-gray-800 mt-6 mb-4">1.1 Interpretation</h3>
                    <p class="mb-6">The words of which the initial letter is capitalized have meanings defined under the following conditions. The following definitions shall have the same meaning regardless of whether they appear in singular or in plural. This section ensures clarity and consistency in the terminology used throughout this document, preventing ambiguity and facilitating a better understanding of our data practices.</p>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-6 mb-4">1.2 Definitions</h3>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Account:</strong> A unique digital profile created for You to access and utilize specific features and functionalities within our Service, such as managing rental orders, viewing invoices, and communicating with suppliers.</li>
                        <li><strong>Affiliate:</strong> Any entity that directly or indirectly controls, is controlled by, or is under common control with the Company. "Control" in this context means the ownership of 50% or more of the shares, equity interest, or other securities entitled to vote for the election of directors or other managing authority. Our affiliates are bound by similar privacy commitments.</li>
                        <li><strong>Company:</strong> Refers to CAT Dump, a business entity with its primary operations located at 9330 LBJ Freeway Suite 900 Dallas TX 75243. This is the legal entity responsible for the collection and processing of your personal data as described herein.</li>
                        <li><strong>Cookies:</strong> Small data files placed on Your computer, mobile device, or any other device by a website. These files contain information about Your browsing history on that website and serve various purposes, including remembering Your preferences, analyzing site usage, and facilitating targeted advertising.</li>
                        <li><strong>Country:</strong> Specifically refers to the state of Texas, within the United States. This designation is important for understanding the primary legal jurisdiction governing this Privacy Policy.</li>
                        <li><strong>Device:</strong> Any electronic apparatus capable of accessing the Service, including but not limited to desktop computers, laptops, mobile phones, tablets, and other internet-enabled devices.</li>
                        <li><strong>Member:</strong> An individual user who has successfully registered and created an Account with CAT Dump, thereby gaining access to a broader range of personalized services and features.</li>
                        <li><strong>Personal Data:</strong> Any information that relates to an identified or identifiable natural person. This includes, but is not limited to, names, addresses, email addresses, financial details, and any other data that can directly or indirectly identify an individual.</li>
                        <li><strong>Service:</strong> Encompasses the entire suite of offerings provided by CAT Dump, including our official website, any associated mobile applications, and proprietary software platforms designed for equipment rental, junk removal, and related services.</li>
                        <li><strong>Service Provider:</strong> Any natural or legal person, company, or third-party entity engaged by the Company to process data on its behalf. This includes external companies or individuals assisting the Company in providing, facilitating, or analyzing the Service, such as payment processors, analytics providers, and customer support platforms.</li>
                        <li><strong>Usage Data:</strong> Information collected automatically, either generated by Your active use of the Service (e.g., pages visited, features used) or from the Service's underlying infrastructure (e.g., server logs, IP addresses). This data helps us understand user behavior and improve service delivery.</li>
                        <li><strong>Visitor:</strong> An individual user who accesses and browses the Service without having registered or created an Account.</li>
                        <li><strong>You:</strong> Refers to the individual accessing or using the Service, or the company, or other legal entity on behalf of which such individual is accessing or using the Service, as applicable. This policy is addressed to You, the user of our services.</li>
                    </ul>

                    <h2 id="collecting-using-data" class="text-3xl font-bold text-gray-800 mt-10 mb-6">2. Collecting and Using Your Personal Data</h2>
                    <p class="mb-6">Our commitment to Your privacy begins with transparency about the data we collect and why. This section details the types of information we gather and the specific purposes for which this data is utilized to provide, support, personalize, and continually enhance Your experience with CAT Dump.</p>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-6 mb-4">2.1 Types of Data Collected</h3>

                    <h4 class="text-xl font-semibold text-gray-800 mt-4 mb-3">2.1.1 Personal Data</h4>
                    <p class="mb-6">When You engage with Our Service, particularly when creating an account, setting up Your profile, or utilizing specific features, We may request certain personally identifiable information. This data is crucial for us to identify You, provide tailored services, and ensure seamless transactions. The types of Personal Data we collect include, but are not limited to:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Contact Information:</strong> This includes Your email address, first name, last name, phone number, and Your physical address (including Street, City, State, Province, and ZIP/Postal code). We collect this information primarily to facilitate direct communication with You, ensure accurate delivery and pickup of equipment, process billing, and send important service-related notifications. For example, your address is essential for dispatching dumpsters or temporary toilets to the correct location.</li>
                        <li><strong>Company Information:</strong> If You are using our Service on behalf of a business, we collect Your Company Name. This helps us understand the nature of Your business needs, tailor our service offerings, and facilitate B2B transactions, including corporate billing and specific contract requirements.</li>
                        <li><strong>Financial Information:</strong> To process payments for equipment rentals, junk removal services, and other transactions, we collect necessary financial details. This may include credit card numbers, bank account information for ACH transfers, and billing addresses. Please note that while we collect this data, actual payment processing is securely handled by reputable third-party payment service providers, and we do not store sensitive credit card details directly on our servers.</li>
                        <li><strong>Profile Information:</strong> Data You provide to build and enrich Your user profile within the Service. This can include optional information such as profile photos, Your job title, industry sector, and any other details You choose to share to personalize Your experience. This information helps us understand Your professional context and may be used to recommend relevant services or connect You with appropriate support.</li>
                        <li><strong>Content Data:</strong> This refers to any information, images, videos, or documents You post, upload, or otherwise submit through the Service. For instance, when requesting junk removal services, You may upload images or videos of items to be removed for quotation purposes. Similarly, project details, special instructions for deliveries, or communications with service providers and customer support representatives are also considered content data. This data is fundamental for us to understand Your service requirements and facilitate the successful completion of Your requests.</li>
                    </ul>

                    <h4 class="text-xl font-semibold text-gray-800 mt-4 mb-3">2.1.2 Usage Data</h4>
                    <p class="mb-6">Usage Data is automatically collected as You interact with and navigate our Service. This data provides valuable insights into how our platform is being used, allowing us to continuously improve its performance, user experience, and overall functionality. This data is typically non-identifiable on its own but can be aggregated for analytical purposes.</p>
                    <p class="mb-6">Examples of Usage Data include:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Device Information:</strong> This includes Your Device's Internet Protocol address (IP address), browser type and version, operating system, unique device identifiers (e.g., IMEI for mobile devices), and other diagnostic data related to Your device's hardware and software. This helps us ensure compatibility and optimize our Service for various devices.</li>
                        <li><strong>Browsing Activity:</strong> Information about the specific pages of our Service that You visit, the time and date of Your visits, the duration of Your sessions on those pages, and the navigation paths You take through our site. This data helps us understand content popularity and user flow, informing design and content improvements.</li>
                        <li><strong>Mobile Device Specifics:</strong> When accessing the Service via a mobile device, we may collect additional information such as the type of mobile device You use, Your mobile device's unique ID, the IP address of Your mobile device, Your mobile operating system, and the type of mobile Internet browser You use. This ensures our mobile applications and responsive website perform optimally on Your specific device.</li>
                        <li><strong>Location Data:</strong> If You enable location services on Your mobile device and grant us permission, we may collect precise or approximate location data. This information is primarily used to connect You with local equipment suppliers, optimize delivery routes, and provide location-specific service availability. You can typically control location data sharing through Your device's settings.</li>
                        <li><strong>Referral Data:</strong> Information about the website or application that referred You to our Service, if any. This helps us understand the effectiveness of our marketing channels.</li>
                    </ul>

                    <h4 class="text-xl font-semibold text-gray-800 mt-4 mb-3">2.1.3 Tracking Technologies and Cookies</h4>
                    <p class="mb-6">To enhance Your browsing experience, understand user behavior, and deliver personalized content, we utilize various tracking technologies, including Cookies. These technologies help us remember Your preferences, analyze site performance, and support our marketing efforts. You have control over the use of cookies through Your browser settings.</p>
                    <p class="mb-6">The tracking technologies we employ include:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Cookies or Browser Cookies:</strong> These are small text files placed on Your Device when You visit a website. They contain a small amount of data specific to a particular client and website, and can be accessed either by the web server or the client computer. They are widely used to make websites work more efficiently, as well as to provide information to the owners of the site. You have the option to configure Your browser to refuse all Cookies or to alert You when a Cookie is being sent. However, please be aware that disabling Cookies may limit Your ability to use certain features or sections of our Service. Unless You have specifically adjusted Your browser settings to refuse Cookies, our Service will use them.</li>
                        <li><strong>Flash Cookies (Local Shared Objects):</strong> Certain features of our Service may use local stored objects, also known as Flash Cookies, to collect and store information about Your preferences or Your activity on our Service. Unlike browser cookies, Flash Cookies are not managed through Your browser's standard settings. To manage or delete Flash Cookies, please refer to the instructions provided by Adobe at <a href="https://helpx.adobe.com/flash-player/kb/disable-local-shared-objects-flash.html#main_Where_can_I_change_the_settings_for_disabling_or_deleting_local_shared_objects_" target="_blank" class="text-blue-custom underline">Adobe HelpX</a>.</li>
                        <li><strong>Web Beacons (Pixel Tags, Clear Gifs, Single-Pixel Gifs):</strong> These are tiny graphics with a unique identifier, similar in function to cookies, and are used to track the online movements of web users. In contrast to cookies, which are stored on a user's computer hard drive, web beacons are embedded invisibly on web pages or in emails. They allow us, for example, to count users who have visited certain pages, opened emails, and to gather other related website statistics (e.g., verifying system and server integrity, assessing the popularity of specific content).</li>
                    </ul>
                    <p class="mb-6">Cookies can be categorized based on their duration: "Persistent" Cookies remain on Your personal computer or mobile device even after You go offline, allowing Your preferences or actions to be remembered across multiple sessions. In contrast, "Session" Cookies are temporary and are deleted automatically as soon as You close Your web browser. For a more comprehensive understanding of cookies and web beacons, You can refer to the detailed article on the <a href="https://www.privacypolicies.com/blog/privacy-policy-template/#Cookies_and_Web_Beacons" target="_blank" class="text-blue-custom underline">Privacy Policies website</a>.</p>
                    <p class="mb-6">We use both Session and Persistent Cookies for the following specific purposes:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li>
                            <p><strong>Necessary / Essential Cookies:</strong></p>
                            <p>Type: Session Cookies</p>
                            <p>Administered by: Us</p>
                            <p>Purpose: These Cookies are absolutely essential for the proper functioning of the Website and to enable You to use its core features. They facilitate Your navigation around the site, allow You to log in securely, and prevent fraudulent use of user accounts. Without these Cookies, fundamental services that You explicitly request, such as completing a booking or accessing Your customer portal, cannot be provided. We only use these Cookies to deliver these essential functionalities.</p>
                        </li>
                        <li>
                            <p><strong>Cookies Policy / Notice Acceptance Cookies:</strong></p>
                            <p>Type: Persistent Cookies</p>
                            <p>Administered by: Us</p>
                            <p>Purpose: These Cookies serve a single, vital purpose: to record whether users have consented to the use of cookies on our Website. This ensures that we comply with legal requirements regarding cookie consent and prevents the repetitive display of the cookie banner after You have made Your choice.</p>
                        </li>
                        <li>
                            <p><strong>Functionality Cookies:</strong></p>
                            <p>Type: Persistent Cookies</p>
                            <p>Administered by: Us</p>
                            <p>Purpose: These Cookies significantly enhance Your personalized experience on our Website. They enable us to remember choices You have made, such as Your login details, language preferences, regional settings, or customized display options. By remembering these preferences, Functionality Cookies eliminate the need for You to re-enter the same information every time You visit our Service, providing a smoother and more efficient user journey.</p>
                        </li>
                        <li>
                            <p><strong>Analytics / Performance Cookies:</strong></p>
                            <p>Type: Persistent Cookies</p>
                            <p>Administered by: Third-Party Service Providers</p>
                            <p>Purpose: These Cookies collect information about how Visitors use our Service, such as which pages are visited most often, how much time is spent on certain pages, and if users encounter error messages. This data is aggregated and anonymous, used solely to improve the performance and design of our Service. For example, we use Google Analytics cookies to understand website traffic patterns and user engagement.</p>
                        </li>
                        <li>
                            <p><strong>Advertising / Targeting Cookies:</strong></p>
                            <p>Type: Persistent Cookies</p>
                            <p>Administered by: Third-Party Service Providers</p>
                            <p>Purpose: These Cookies are used to deliver advertisements that are more relevant to You and Your interests. They are often placed by advertising networks with our permission. They remember that You have visited our Service and this information may be shared with other organizations, such as advertisers. This means that after You have been to our Service, You may see advertisements about CAT Dump on other websites.</p>
                        </li>
                    </ul>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-10 mb-6">2.2 Use of Your Personal Data</h3>
                    <p class="mb-6">The Personal Data we collect is utilized for a variety of purposes, all aimed at providing, maintaining, and improving the CAT Dump Service, as well as fulfilling our legal and contractual obligations. Specifically, the Company may use Personal Data for the following purposes:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>To provide and maintain our Service:</strong> This encompasses all operational aspects of CAT Dump. We use Your data to operate our website and mobile applications, facilitate seamless equipment rentals (from initial inquiry to final pickup), efficiently process junk removal requests (including AI-powered quotation based on uploaded content), and ensure the overall stability, security, and optimal performance of our platform. This also includes monitoring service usage patterns to identify areas for technical improvement and feature development.</li>
                        <li><strong>To manage Your Account:</strong> Your Personal Data is essential for managing Your registration as a user (Member) of the Service. This includes creating and maintaining Your unique profile, enabling secure login, and granting You access to personalized features such as Your customer dashboard, order history, and saved preferences.</li>
                        <li><strong>For the performance of a contract:</strong> This is a core function of our data processing. We use Your data to fulfill the terms of any contract entered into with You, whether it's a rental agreement for a dumpster, a service contract for junk removal, or any other agreement for products or services You have purchased. This includes processing Your payments securely, managing precise delivery and pickup schedules, coordinating with our network of suppliers, and providing necessary support throughout the service lifecycle.</li>
                        <li><strong>To contact You:</strong> We use Your contact information to communicate with You regarding various aspects of our Service. This includes sending essential service updates, order confirmations, real-time delivery and pickup notifications, security alerts related to Your account, and responding to Your customer support inquiries. Communication methods may include email, telephone calls, SMS messages, and in-app push notifications, ensuring You are always informed.</li>
                        <li><strong>To provide You with news, special offers and general information:</strong> With Your consent where required, we may send You marketing and promotional communications. These communications will be about other goods, services, and events offered by CAT Dump or our trusted business partners that we believe may be of interest to You, based on Your past interactions and preferences. This may involve using Your data for targeted advertising campaigns to ensure the offers You receive are relevant and valuable. You always have the option to opt out of receiving such marketing communications.</li>
                        <li><strong>To manage Your requests:</strong> We process Your Personal Data to efficiently respond to and manage any inquiries, complaints, feedback, or requests You submit to our customer service or support teams. This ensures that Your concerns are addressed promptly and effectively.</li>
                        <li><strong>For business transfers:</strong> In the event that CAT Dump undergoes a merger, acquisition, asset sale, financing, or other corporate restructuring, Your Personal Data may be transferred as part of the assets involved in such transactions. In such cases, we will ensure that Your data remains protected under a privacy policy that is consistent with or provides similar protections as this one.</li>
                        <li><strong>For other purposes:</strong> Beyond the primary uses, we may leverage Your information for various internal business purposes. This includes conducting detailed data analysis to identify usage trends and patterns, performing internal audits to ensure compliance and efficiency, determining the effectiveness of our promotional campaigns, and continuously evaluating and improving our Service, products, services, marketing strategies, and Your overall user experience. This also plays a critical role in enhancing the security and integrity of our systems and preventing fraudulent activities.</li>
                    </ul>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-10 mb-6">2.3 Sharing Your Personal Data</h3>
                    <p class="mb-6">At CAT Dump, we understand the importance of Your data's privacy. While we do not sell Your personal information, there are specific circumstances where we may share Your data with trusted third parties to facilitate our services, comply with legal obligations, or for legitimate business purposes. We ensure that any sharing of Your data is done with appropriate safeguards and in accordance with this Privacy Policy.</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>With Service Providers:</strong> We engage various third-party companies and individuals to perform services on our behalf and support our operations. These Service Providers may include, but are not limited to, cloud hosting providers (for data storage), payment gateway processors (for secure financial transactions), data analytics firms (to understand user behavior), customer relationship management (CRM) platforms, email marketing services, and IT support vendors. These Service Providers are granted access to Your Personal Data only to the extent necessary to perform their contracted tasks for Us and are strictly obligated not to disclose or use it for any other purpose. We implement contractual agreements to ensure they adhere to high standards of data protection.</li>
                        <li><strong>For business transfers:</strong> In the event of a merger, acquisition, sale of Company assets (such as intellectual property or customer databases), financing, or a similar corporate transaction involving all or a portion of Our business, Your personal information may be shared or transferred to the acquiring entity or parties involved in the negotiation. We will ensure that any such transfer is conducted with appropriate confidentiality agreements and that the receiving party commits to protecting Your data in a manner consistent with this Privacy Policy.</li>
                        <li><strong>With Affiliates:</strong> We may share Your information with our corporate affiliates, which include our parent company and any other subsidiaries, joint venture partners, or other companies that we control or that are under common control with Us. This sharing facilitates integrated service delivery, internal reporting, and shared operational efficiencies across our corporate family. All affiliates are required to adhere to the principles and protections outlined in this Privacy Policy.</li>
                        <li><strong>With business partners:</strong> To provide You with a comprehensive range of services and offers, we may share Your information with our trusted business partners. This includes, most notably, our network of local equipment suppliers (e.g., dumpster companies, portable toilet providers) who fulfill Your rental requests. Sharing data with these partners is essential for coordinating service delivery, confirming orders, and ensuring You receive the specific products or promotions that align with your needs. This may also include sharing data with third-party advertisers or ad networks to deliver relevant advertisements to You based on Your interests and interactions with our Service.</li>
                        <li><strong>With other users:</strong> Certain features of our Service may allow You to interact with other users in public areas, such as through public profiles, customer reviews, or community forums. When You choose to share personal information or otherwise interact in these public spaces, such information may become visible to all other users and could potentially be publicly distributed outside of the Service. We advise caution when sharing personal information in public forums.</li>
                        <li><strong>For legal obligations and security:</strong> We may be required to disclose Your Personal Data if mandated by law or in response to valid requests from public authorities (e.g., a court order, subpoena, or government agency request). Furthermore, we may disclose Your data in good faith when we believe such action is necessary to: comply with a legal obligation; protect and defend the rights or property of CAT Dump; prevent or investigate possible wrongdoing in connection with the Service; protect the personal safety of users of the Service or the public; or protect against legal liability. This includes sharing data to prevent fraud, enforce our terms of service, and address security incidents.</li>
                        <li><strong>With Your consent:</strong> For any other purpose not explicitly mentioned above, we will only disclose Your personal information with Your explicit consent. You will be informed about the nature of the data to be shared and the purpose of sharing, allowing You to make an informed decision.</li>
                    </ul>
                    <p class="mb-6 font-bold">As a core principle, CAT Dump does not sell personal information to third parties for monetary or other valuable consideration.</p>

                    <h2 id="retention-data" class="text-3xl font-bold text-gray-800 mt-10 mb-6">3. Retention of Your Personal Data</h2>
                    <p class="mb-6">The duration for which CAT Dump retains Your Personal Data is determined by the specific purpose for which it was collected, our legal and regulatory obligations, and our legitimate business needs. We are committed to retaining Your data only for as long as is necessary and proportionate.</p>
                    <p class="mb-6">Generally, we will retain Your Personal Data for the period necessary to:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Provide and maintain our Service:</strong> This includes retaining data for the entire duration of Your active account with us, and for a reasonable period thereafter, to facilitate re-engagement, provide customer support, and maintain service continuity. For instance, rental history and invoice data are retained to allow you to review past transactions and reorder services with ease.</li>
                        <li><strong>Manage Your Account:</strong> Data associated with Your account, such as Your profile information and preferences, is retained as long as Your account remains active to provide a personalized experience.</li>
                        <li><strong>Comply with legal obligations:</strong> We are subject to various laws and regulations that require us to retain certain types of data for specific periods. This includes financial records, transaction histories, and communication logs for tax, audit, and compliance purposes. For example, records related to payments and invoices may be retained for several years as required by financial regulations.</li>
                        <li><strong>Resolve disputes:</strong> In the event of a legal dispute or claim, we may retain relevant Personal Data for as long as necessary to resolve such disputes effectively.</li>
                        <li><strong>Enforce our legal agreements and policies:</strong> Data may be retained to enforce our Terms of Service, Privacy Policy, and other agreements, and to protect our legal rights and interests.</li>
                        <li><strong>Prevent fraud and ensure security:</strong> Certain data may be retained to detect, prevent, and investigate fraudulent activities or security incidents, even after Your account is closed, to protect the integrity of our Service and other users.</li>
                    </ul>
                    <p class="mb-6">The Company will also retain Usage Data for internal analysis purposes. Usage Data, being generally aggregated and anonymized, is typically retained for a shorter period of time compared to identifiable Personal Data. However, exceptions apply when this data is specifically used to strengthen the security of Our Service, to improve or troubleshoot the functionality of Our Service, or if We are legally obligated to retain this data for longer time periods (e.g., for cybersecurity investigations). Once data is no longer required for these purposes, we implement procedures for its secure deletion or anonymization, ensuring it can no longer be associated with an identifiable individual.</p>

                    <h2 id="transfer-data" class="text-3xl font-bold text-gray-800 mt-10 mb-6">4. Transfer of Your Personal Data</h2>
                    <p class="mb-6">CAT Dump operates in a global digital environment, which means that Your information, including Personal Data, may be processed and stored in locations outside of Your immediate geographical region. This section clarifies how Your data may be transferred and the safeguards we put in place to protect it.</p>
                    <p class="mb-6">Your information, including Personal Data, is primarily processed at the Company's operating offices in Dallas, Texas, United States. However, it may also be processed in any other places where the parties involved in the data processing are located. This means that Your information may be transferred to — and maintained on — computers located outside of Your state, province, country, or other governmental jurisdiction where the data protection laws may differ from those in Your jurisdiction. For instance, some of our Service Providers may operate data centers or support teams in different countries.</p>
                    <p class="mb-6">By using our Service and submitting Your information, You explicitly consent to this transfer of Your Personal Data. We want to assure You that despite international transfers, our commitment to protecting Your data remains unwavering.</p>
                    <p class="mb-6">The Company will take all steps reasonably necessary to ensure that Your data is treated securely and in accordance with this Privacy Policy. This means that no transfer of Your Personal Data will take place to an organization or a country unless there are adequate controls in place to ensure the security and protection of Your data and other personal information. These adequate controls may include:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Standard Contractual Clauses (SCCs):</strong> Implementing data transfer agreements based on standard contractual clauses approved by relevant regulatory bodies (e.g., the European Commission), which impose strict obligations on the data importer to protect personal data.</li>
                        <li><strong>Binding Corporate Rules (BCRs):</strong> For transfers within our corporate group (including affiliates), we may rely on Binding Corporate Rules, which are internal codes of conduct approved by data protection authorities.</li>
                        <li><strong>Explicit Consent:</strong> In specific situations, and where no other legal basis for transfer applies, we may obtain Your explicit consent for the transfer after informing You of the possible risks of such transfers.</li>
                        <li><strong>Adequacy Decisions:</strong> Relying on adequacy decisions by relevant authorities that confirm a particular country or framework provides an adequate level of data protection.</li>
                    </ul>
                    <p class="mb-6">We continuously monitor the legal and regulatory landscape concerning international data transfers to ensure our practices remain compliant and Your data remains protected, regardless of where it is processed.</p>

                    <h2 id="disclosure-data" class="text-3xl font-bold text-gray-800 mt-10 mb-6">5. Disclosure of Your Personal Data</h2>
                    <p class="mb-6">While CAT Dump is committed to safeguarding Your Personal Data, there are specific circumstances under which Your information may be disclosed. This section outlines these situations, ensuring transparency regarding how and when Your data might be shared beyond the direct provision of our services.</p>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-6 mb-4">5.1 Business Transactions</h3>
                    <p class="mb-6">In the event that the Company undergoes significant corporate changes, such as a merger, acquisition by another company, or the sale of all or a portion of its assets, Your Personal Data may be transferred as part of that transaction. This is a standard practice in such business transitions. Should such an event occur, We will provide You with prominent notice prior to Your Personal Data being transferred. This notification will inform You about the impending transfer and, importantly, advise You if Your data will become subject to a different privacy policy by the acquiring entity. Our aim is to ensure You are fully aware of any changes to the handling of Your data in such scenarios.</p>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-6 mb-4">5.2 Law enforcement</h3>
                    <p class="mb-6">Under certain circumstances, CAT Dump may be legally required to disclose Your Personal Data. This obligation arises when we receive a valid request from law enforcement authorities or other public bodies. Such requests typically come in the form of a court order, subpoena, or other legally binding governmental demand. We will only disclose the minimum amount of Personal Data necessary to comply with such legal obligations, and we will assess the legality and necessity of each request before proceeding. Our priority is to protect Your privacy while adhering to the rule of law.</p>

                    <h3 class="text-2xl font-semibold text-gray-800 mt-6 mb-4">5.3 Other legal requirements</h3>
                    <p class="mb-6">Beyond direct law enforcement requests, the Company may disclose Your Personal Data in the good faith belief that such action is necessary to fulfill other critical legal or operational requirements. These situations include, but are not limited to, instances where disclosure is necessary to:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Comply with a legal obligation:</strong> This covers a broad range of regulatory and statutory requirements, such as responding to discovery requests in litigation, adhering to industry-specific regulations, or fulfilling reporting obligations to governmental agencies.</li>
                        <li><strong>Protect and defend the rights or property of the Company:</strong> This includes situations where we need to take legal action to protect our intellectual property, enforce our terms of service, recover debts, or defend against legal claims brought against CAT Dump.</li>
                        <li><strong>Prevent or investigate possible wrongdoing in connection with the Service:</strong> If we detect or suspect fraudulent activities, security breaches, or other violations of our policies or applicable laws, we may disclose relevant data to investigate and mitigate such issues. This is crucial for maintaining the integrity and security of our platform for all users.</li>
                        <li><strong>Protect the personal safety of Users of the Service or the public:</strong> In emergency situations where there is a credible threat to the physical safety of an individual or the public, we may disclose necessary Personal Data to prevent harm.</li>
                        <li><strong>Protect against legal liability:</strong> This involves disclosing data as part of our efforts to manage legal risks, respond to legal claims, or comply with professional obligations (e.g., disclosures to our legal counsel or auditors).</li>
                    </ul>

                    <h2 id="security-data" class="text-3xl font-bold text-gray-800 mt-10 mb-6">6. Security of Your Personal Data</h2>
                    <p class="mb-6">The security of Your Personal Data is a top priority for CAT Dump. We are committed to implementing and maintaining robust security measures to protect Your information from unauthorized access, alteration, disclosure, or destruction. We employ a multi-layered approach to data security, integrating both technical and organizational safeguards.</p>
                    <p class="mb-6">Our security measures include, but are not limited to:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Encryption:</strong> We utilize industry-standard encryption protocols (e.g., SSL/TLS) to protect data during transmission over the internet. This ensures that communications between Your device and our servers are secure and private. Where appropriate, data at rest (stored on our servers) is also encrypted.</li>
                        <li><strong>Access Controls:</strong> Access to Personal Data is strictly limited to authorized personnel who require the information to perform their job functions. We implement role-based access controls and the principle of least privilege, ensuring employees only have access to the data necessary for their specific tasks.</li>
                        <li><strong>Secure Servers and Infrastructure:</strong> Our data is hosted on secure servers within controlled environments, protected by firewalls and other network security technologies. We partner with reputable cloud infrastructure providers that adhere to high security standards.</li>
                        <li><strong>Regular Security Audits and Penetration Testing:</strong> We conduct regular security audits, vulnerability assessments, and engage in penetration testing by independent third-party experts. These proactive measures help us identify and address potential security weaknesses before they can be exploited.</li>
                        <li><strong>Employee Training:</strong> All employees who handle Personal Data undergo mandatory and regular training on data protection, privacy best practices, and our internal security policies. This ensures a culture of security awareness across the organization.</li>
                        <li><strong>Incident Response Plan:</strong> We have a comprehensive incident response plan in place to detect, contain, investigate, and recover from any potential data security incidents. This plan includes procedures for timely notification to affected individuals and relevant authorities, as required by law.</li>
                        <li><strong>Data Minimization:</strong> We adhere to the principle of data minimization, collecting only the Personal Data that is necessary for the stated purposes. This reduces the amount of sensitive information we hold.</li>
                        <li><strong>Physical Security Measures:</strong> Our physical facilities and data centers are protected by appropriate physical security measures to prevent unauthorized access to hardware and data storage.</li>
                        <li><strong>Multi-Factor Authentication (MFA):</strong> We encourage and, where applicable, require the use of multi-factor authentication for accessing Your account, adding an extra layer of security beyond just a password.</li>
                    </ul>
                    <p class="mb-6">While we strive to use commercially acceptable means to protect Your Personal Data and continuously review and update our security practices, it is important to remember that no method of transmission over the Internet, or method of electronic storage, is 100% secure. Therefore, while We endeavor to protect Your Personal Data, We cannot guarantee its absolute security. We encourage You to also take steps to protect Your personal information, such as using strong, unique passwords and being cautious about sharing information online.</p>

                    <h2 id="data-protection-rights" class="text-3xl font-bold text-gray-800 mt-10 mb-6">7. Your Data Protection Rights (e.g., CCPA)</h2>
                    <p class="mb-6">At CAT Dump, we are committed to empowering You with control over Your Personal Data. In accordance with applicable data protection laws, You have certain fundamental rights regarding Your information. We are dedicated to honoring these rights and providing mechanisms for You to exercise them effectively.</p>
                    <p class="mb-6">Your data protection rights may include:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>The Right to Access:</strong> You have the right to request confirmation as to whether or not Your Personal Data is being processed, and, where that is the case, access to the Personal Data and certain information about its processing. You can request copies of Your Personal Data that We hold.</li>
                        <li><strong>The Right to Rectification:</strong> You have the right to request that We correct any information You believe is inaccurate or incomplete. If You identify any errors or omissions in Your Personal Data, please notify us, and we will take reasonable steps to rectify them promptly.</li>
                        <li><strong>The Right to Erasure ("Right to be Forgotten"):</strong> You have the right to request that We erase Your Personal Data, under certain conditions. This right applies when the data is no longer necessary for the purposes for which it was collected, or when You withdraw consent, among other specific legal grounds. Please note that certain legal obligations may require us to retain some data even after an erasure request.</li>
                        <li><strong>The Right to Restrict Processing:</strong> You have the right to request that We restrict the processing of Your Personal Data, under certain conditions. This means that we would store Your data but not use it for specific processing activities, for example, if You contest the accuracy of the data or the legality of its processing.</li>
                        <li><strong>The Right to Object to Processing:</strong> You have the right to object to Our processing of Your Personal Data, under certain conditions, particularly when the processing is based on our legitimate interests or for direct marketing purposes. We will cease processing Your data unless we can demonstrate compelling legitimate grounds for the processing which override Your interests, rights, and freedoms, or for the establishment, exercise, or defense of legal claims.</li>
                        <li><strong>The Right to Data Portability:</strong> You have the right to request that We transfer the data that we have collected to another organization, or directly to You, in a structured, commonly used, and machine-readable format, under certain conditions. This allows You to obtain and reuse Your Personal Data for Your own purposes across different services.</li>
                        <li><strong>The Right to Withdraw Consent:</strong> Where our processing of Your Personal Data is based on Your consent, You have the right to withdraw that consent at any time. Withdrawal of consent will not affect the lawfulness of processing based on consent before its withdrawal.</li>
                    </ul>
                    <p class="mb-6">For California residents, the California Consumer Privacy Act (CCPA) provides specific additional rights concerning Your personal information. Under the CCPA, "personal information" is broadly defined to include information that identifies, relates to, describes, is capable of being associated with, or could reasonably be linked, directly or indirectly, with a particular consumer or household. Your CCPA rights include:</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>Right to Know:</strong> You have the right to request that We disclose what personal information We collect, use, disclose, and sell (if applicable). This includes categories of personal information, sources from which it is collected, the business or commercial purpose for collecting or selling it, categories of third parties with whom we share it, and specific pieces of personal information collected about You.</li>
                        <li><strong>Right to Delete:</strong> You have the right to request the deletion of personal information that We have collected from You, subject to certain exceptions. For instance, we may not be able to delete information necessary to complete a transaction, detect security incidents, or comply with legal obligations.</li>
                        <li><strong>Right to Opt-Out of Sale:</strong> You have the right to opt-out of the sale of Your personal information. As a fundamental principle, CAT Dump does not sell personal information to third parties. Therefore, this right applies in principle but is not actively exercised as we do not engage in such sales.</li>
                        <li><strong>Right to Non-Discrimination:</strong> You have the right not to be discriminated against for exercising any of Your CCPA rights. This means we will not deny You goods or services, charge You different prices, or provide a different level or quality of goods or services because You exercised Your rights.</li>
                    </ul>
                    <p class="mb-6">To exercise any of these data protection rights, please contact us using the information provided in the "Contact Us" section below. We will verify Your request and respond within the timeframes required by applicable law (e.g., typically 30 days for GDPR-related requests and 45 days for CCPA requests, with possible extensions). We may require You to provide additional information to verify Your identity before processing Your request.</p>

                    <h2 id="childrens-privacy" class="text-3xl font-bold text-gray-800 mt-10 mb-6">8. Children's Privacy</h2>
                    <p class="mb-6">Protecting the privacy of young individuals is a critical aspect of our commitment to data protection. Our Service is not intended for, nor does it knowingly target, anyone under the age of 13 ("Children" or "Child").</p>
                    <p class="mb-6">We do not knowingly collect personally identifiable information from anyone under the age of 13. Our Service is designed for individuals involved in equipment rental and related services, which are typically adults or businesses.</p>
                    <p class="mb-6">If You are a parent or guardian and You become aware that Your child has provided Us with Personal Data without Your verifiable consent, please contact Us immediately using the contact information provided in this Privacy Policy. We will take prompt steps to investigate the matter and, if confirmed, remove that information from Our servers and any associated databases.</p>
                    <p class="mb-6">Furthermore, if We need to rely on consent as a legal basis for processing Your information, and Your country or jurisdiction requires the consent of a parent or guardian for individuals below a certain age (e.g., 16 in some regions), We may require Your parent's or guardian's verifiable consent before We collect and use that information. This ensures compliance with international and regional child privacy regulations.</p>

                    <h2 id="links-to-other-websites" class="text-3xl font-bold text-gray-800 mt-10 mb-6">9. Links to Other Websites</h2>
                    <p class="mb-6">Our Service may contain links to external websites that are not operated by CAT Dump. These links are provided for Your convenience and to offer additional resources or information that may be relevant to Your needs. However, please be aware that once You click on a third-party link, You will be directed away from our Service to that third party's site.</p>
                    <p class="mb-6">We strongly advise You to review the Privacy Policy and Terms of Service of every external site You visit. The privacy practices and content of these third-party websites are beyond our control. We have no control over, and assume no responsibility for, the content, privacy policies, or practices of any third-party sites or services. Our Privacy Policy applies solely to information collected by CAT Dump through our Service.</p>
                    <p class="mb-6">This includes links to social media platforms, payment gateways, or other external tools that may be integrated into our Service. While we strive to partner with reputable organizations, we cannot be held responsible for their independent data collection or privacy practices. Your interactions with these linked websites are governed by their respective policies, not ours.</p>

                    <h2 id="changes-to-policy" class="text-3xl font-bold text-gray-800 mt-10 mb-6">10. Changes to this Privacy Policy</h2>
                    <p class="mb-6">CAT Dump reserves the right to update or modify this Privacy Policy from time to time to reflect changes in our data processing practices, technological advancements, legal or regulatory requirements, or business operations. We are committed to keeping our Privacy Policy current and transparent.</p>
                    <p class="mb-6">We will notify You of any material changes by posting the revised Privacy Policy on this page. Minor changes or clarifications may be effective immediately upon posting. For significant updates that affect how we use or share Your Personal Data, we will provide more prominent notice.</p>
                    <p class="mb-6">We will let You know via email (sent to the primary email address specified in Your account) and/or a prominent notice on Our Service (e.g., a banner on our website or a notification within the app), prior to the change becoming effective. This notification will also update the "Last updated" date at the top of this Privacy Policy, allowing You to quickly identify when the policy was last revised.</p>
                    <p class="mb-6">You are advised to review this Privacy Policy periodically for any changes. Your continued use of the Service after the revised Privacy Policy has been posted signifies Your acceptance of the changes. If You do not agree with the terms of the updated Privacy Policy, You should discontinue Your use of the Service.</p>

                    <h2 id="contact-us" class="text-3xl font-bold text-gray-800 mt-10 mb-6">11. Contact Us</h2>
                    <p class="mb-6">If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please do not hesitate to contact us. We are committed to addressing your inquiries promptly and transparently.</p>
                    <ul class="list-disc pl-8 mb-6 space-y-2">
                        <li><strong>By email:</strong> You can reach our dedicated privacy team directly at <a href="mailto:support@catdump.com" class="text-blue-custom underline">support@catdump.com</a>. We aim to respond to all email inquiries within a reasonable timeframe.</li>
                        <li><strong>By visiting this page on our website:</strong> For general inquiries or to find more information about our services, please visit our contact page at [Your Contact Page URL, e.g., www.catdump.com/contact]. This page may also contain additional methods of contact or FAQs.</li>
                        <li><strong>By mail:</strong> You can send written correspondence to our physical address: 9330 LBJ Freeway Suite 900 Dallas TX 75243. Please ensure to clearly mark your correspondence as "Privacy Inquiry" to ensure it reaches the appropriate department.</li>
                    </ul>
                    <p class="mb-6">When contacting us, please provide sufficient detail to allow us to understand and respond to your inquiry effectively. This may include your name, email address associated with your account (if applicable), and a clear description of your question or request. We appreciate your proactive engagement in understanding your privacy rights.</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const closeMobileMenuButton = document.getElementById('close-mobile-menu');
            const mobileNavOverlay = document.getElementById('mobile-nav-overlay');

            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileNavOverlay.classList.add('open');
                });
            }

            if (closeMobileMenuButton) {
                closeMobileMenuButton.addEventListener('click', () => {
                    mobileNavOverlay.classList.remove('open');
                });
            }

            if (mobileNavOverlay) {
                mobileNavOverlay.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileNavOverlay.classList.remove('open');
                    });
                });
            }

            const mainHeader = document.getElementById('main-header');
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 50) {
                    mainHeader.classList.add('header-scrolled');
                } else {
                    mainHeader.classList.remove('header-scrolled');
                }
            });

            document.querySelectorAll('[data-dropdown-toggle]').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = toggle.dataset.dropdownToggle;
                    const targetContent = document.getElementById(targetId);
                    const arrowIcon = toggle.querySelector('[data-dropdown-arrow]');

                    if (targetContent) {
                        if (targetContent.classList.contains('open')) {
                            targetContent.classList.remove('open');
                            targetContent.classList.add('hidden');
                            if (arrowIcon) arrowIcon.classList.remove('rotate-180');
                        } else {
                            document.querySelectorAll('.mobile-dropdown-content.open').forEach(openContent => {
                                openContent.classList.remove('open');
                                openContent.classList.add('hidden');
                                const openArrow = document.querySelector(`[data-dropdown-arrow="${openContent.id}"]`);
                                if (openArrow) openArrow.classList.remove('rotate-180');
                            });

                            targetContent.classList.remove('hidden');
                            targetContent.classList.add('open');
                            if (arrowIcon) arrowIcon.classList.add('rotate-180');
                        }
                    }
                });
            });

            // Smooth scroll for table of contents with header offset
            document.querySelectorAll('nav.mb-12 a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();

                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    const headerOffset = mainHeader.offsetHeight; // Get the height of the fixed header
                    
                    if (targetElement) {
                        const elementPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
                        // Adjust the offset to account for the fixed header and add a little extra padding
                        const offsetPosition = elementPosition - headerOffset - 20; 

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
<?php include 'includes/public_footer.php'; ?>
</html>