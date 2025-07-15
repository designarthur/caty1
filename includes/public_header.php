<?php
// includes/public_header.php
// Ensure functions.php is loaded for getSystemSetting() and other utilities
require_once __DIR__ . '/functions.php'; // Corrected to ensure functions.php is loaded

// Determine the current page to set active navigation link
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Adjust currentPage for files within subdirectories if needed
if ($currentDir == 'Company' || $currentDir == 'Resources' || $currentDir == 'Services') {
    $currentPage = $currentDir . '/' . $currentPage;
}

// Function to check if a link is active
function isActive($pageName, $currentPage) {
    if (strpos($currentPage, $pageName) !== false) {
        return 'font-bold text-blue-custom'; // Tailwind class for active state
    }
    return 'text-gray-700 hover:text-blue-custom'; // Tailwind class for inactive state
}

// Fetch company name from system settings (assuming this is done globally or in index.php before including this file)
global $companyName; // Declare global to access if set in a parent scope
if (!$companyName) {
    $companyName = getSystemSetting('company_name') ?? 'Catdump'; // Fallback if not set
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : htmlspecialchars($companyName) . ' - Your Seamless Rental Journey'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* General styles, adjust paths as needed */
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
        .text-blue-custom {
            color: #1a73e8; /* Ensure this color is defined or use default Tailwind blue-600 */
        }
        /* Style for the fixed header on scroll */
        .header-scrolled {
            background-color: rgba(255, 255, 255, 0.98);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Styles for mobile menu drawer */
        .mobile-menu-drawer {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .mobile-menu-drawer.hidden {
            opacity: 0;
            visibility: hidden;
        }
        /* Desktop dropdown initial hidden state and transitions */
        .desktop-flyout-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateY(0.25rem); /* Equivalent to translate-y-1 */
            transition: opacity 0.2s ease-out, transform 0.2s ease-out; /* ease-out duration-200 */
            pointer-events: none; /* Disable interaction when hidden */
        }
        .desktop-flyout-menu.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto; /* Enable interaction when visible */
            transition: opacity 0.2s ease-in, transform 0.2s ease-in; /* ease-in duration-150 */
        }
        /* Custom button styles for header login/signup */
        .btn-header-primary {
            background-color: #1a73e8;
            color: white;
            padding: 0.5rem 1.25rem; /* Smaller padding for slimmer header */
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(26, 115, 232, 0.3);
        }
        .btn-header-primary:hover {
            background-color: #155bb5;
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.4);
        }
        .btn-header-secondary {
            background-color: transparent;
            color: #1a73e8;
            padding: 0.5rem 1.25rem; /* Smaller padding for slimmer header */
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid transparent; /* To prevent jump on hover */
        }
        .btn-header-secondary:hover {
            color: #155bb5;
            border-color: #e2e8f0; /* Subtle border on hover */
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="antialiased">

    <header class="bg-white py-3 shadow-md sticky top-0 z-50 transition-all duration-300 ease-in-out" id="main-header">
        <nav class="container-box mx-auto flex items-center justify-between p-3" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="/index.php" class="-m-1.5 p-1.5">
                    <span class="sr-only"><?php echo htmlspecialchars($companyName); ?></span>
                    <img class="h-12 w-auto mr-4 rounded-full shadow-lg" src="/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" />
                </a>
            </div>
            <div class="flex lg:hidden">
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700" id="mobile-menu-button">
                    <span class="sr-only">Open main menu</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <a href="/How-it-works.php" class="<?php echo isActive('How-it-works.php', $currentPage); ?> text-base/7 font-semibold transition duration-300">How It Works</a>
                
                <div class="relative" id="services-menu-desktop">
                    <button type="button" class="flex items-center gap-x-1 text-base/7 font-semibold text-gray-900" aria-expanded="false" id="services-dropdown-button">
                        Services
                        <svg class="size-5 flex-none text-gray-400 transition-transform duration-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div id="services-flyout-menu" class="desktop-flyout-menu absolute left-1/2 z-10 top-full pt-3 w-screen max-w-md -translate-x-1/2 overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-gray-900/5">
                        <div class="p-4">
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-dumpster size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Services/Dumpster-Rentals.php" class="block text-gray-900">Dumpster Rentals<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Efficient waste management solutions</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-restroom size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Services/Temporary-Toilets.php" class="block text-gray-900">Temporary Toilets<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Clean & reliable portable sanitation</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-warehouse size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Services/Storage-Containers.php" class="block text-gray-900">Storage Containers<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Secure on-site storage solutions</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-fire size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Services/Junk-Removal.php" class="block text-gray-900">Junk Removal<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Effortless disposal of unwanted items</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-truck-moving size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Services/Relocation-&-Swap.php" class="block text-gray-900">Relocation & Swap<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Flexible solutions for changing project needs</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative" id="company-menu-desktop">
                    <button type="button" class="flex items-center gap-x-1 text-base/7 font-semibold text-gray-900" aria-expanded="false" id="company-dropdown-button">
                        Company
                        <svg class="size-5 flex-none text-gray-400 transition-transform duration-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div id="company-flyout-menu" class="desktop-flyout-menu absolute left-1/2 z-10 top-full pt-3 w-screen max-w-md -translate-x-1/2 overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-gray-900/5">
                        <div class="p-4">
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-info-circle size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Company/About-Us.php" class="block text-gray-900">About Us<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Learn about our mission and values</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-leaf size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Company/Sustainability.php" class="block text-gray-900">Sustainability<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Our commitment to a greener future</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-star size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Company/Testimonials.php" class="block text-gray-900">Testimonials<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Hear from our happy customers</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative" id="resources-menu-desktop">
                    <button type="button" class="flex items-center gap-x-1 text-base/7 font-semibold text-gray-900 <?php echo isActive('Resources', $currentDir); ?>" aria-expanded="false" id="resources-dropdown-button">
                        Resources
                        <svg class="size-5 flex-none text-gray-400 transition-transform duration-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div id="resources-flyout-menu" class="desktop-flyout-menu absolute left-1/2 z-10 top-full pt-3 w-screen max-w-md -translate-x-1/2 overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-gray-900/5">
                        <div class="p-4">
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-dollar-sign size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Resources/Pricing-Finance.php" class="block text-gray-900">Pricing & Finance<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Transparent costs and flexible options</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-book-open size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Resources/Customer-Resources.php" class="block text-gray-900">Customer Resources<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Guides and tools for your success</p></div>
                            </div>
                             <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-newspaper size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Resources/Blog.php" class="block text-gray-900">Blog/News<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Industry insights and updates</p></div>
                            </div>
                            <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-question-circle size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Resources/FAQs.php" class="block text-gray-900">FAQs<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Quick answers to common questions</p></div>
                            </div>
                             <div class="group relative flex items-center gap-x-6 rounded-lg p-4 text-base font-semibold hover:bg-gray-50">
                                <div class="flex size-11 flex-none items-center justify-center rounded-lg bg-gray-50 group-hover:bg-white"><i class="fas fa-headset size-6 text-gray-600 group-hover:text-indigo-600"></i></div>
                                <div class="flex-auto"><a href="/Resources/Support-Center.php" class="block text-gray-900">Support Center<span class="absolute inset-0"></span></a><p class="mt-1 text-sm text-gray-600">Get help from our expert team</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="/Resources/Contact.php" class="<?php echo isActive('Resources/Contact.php', $currentPage); ?> text-base/7 font-semibold transition duration-300">Contact</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:gap-x-4">
                <a href="/customer/login.php" class="btn-header-secondary">Log in</a>
                <a href="#" onclick="showAIChat('general'); return false;" class="btn-header-primary">Sign up</a>
            </div>
        </nav>
        <div class="mobile-menu-drawer fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-md hidden" role="dialog" aria-modal="true" id="mobile-menu-drawer">
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                <div class="flex items-center justify-between">
                    <a href="/index.php" class="-m-1.5 p-1.5">
                        <span class="sr-only"><?php echo htmlspecialchars($companyName); ?></span>
                        <img class="h-8 w-auto" src="/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" />
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700" id="close-mobile-menu">
                        <span class="sr-only">Close menu</span>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-6 divide-y divide-gray-500/10">
                        <div class="space-y-2 py-6">
                            <a href="/How-it-works.php" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 <?php echo isActive('How-it-works.php', $currentPage); ?>">How It Works</a>
                            <div class="-mx-3">
                                <button type="button" class="flex w-full items-center justify-between rounded-lg py-2 pr-3.5 pl-3 text-base/7 font-semibold text-gray-900 hover:bg-gray-50" aria-controls="mobile-services-panel" aria-expanded="false" id="mobile-services-dropdown-button">
                                    Services
                                    <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="mt-2 space-y-2 hidden" id="mobile-services-panel">
                                    <a href="/Services/Dumpster-Rentals.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Dumpster Rentals</a>
                                    <a href="/Services/Temporary-Toilets.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Temporary Toilets</a>
                                    <a href="/Services/Storage-Containers.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Storage Containers</a>
                                    <a href="/Services/Junk-Removal.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Junk Removal</a>
                                    <a href="/Services/Relocation-&-Swap.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Relocation & Swap</a>
                                </div>
                            </div>
                            <div class="-mx-3">
                                <button type="button" class="flex w-full items-center justify-between rounded-lg py-2 pr-3.5 pl-3 text-base/7 font-semibold text-gray-900 hover:bg-gray-50" aria-controls="mobile-company-panel" aria-expanded="false" id="mobile-company-dropdown-button">
                                    Company
                                    <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="mt-2 space-y-2 hidden" id="mobile-company-panel">
                                    <a href="/Company/About-Us.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">About Us</a>
                                    <a href="/Company/Sustainability.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Sustainability</a>
                                    <a href="/Company/Testimonials.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Testimonials</a>
                                </div>
                            </div>
                             <div class="-mx-3">
                                <button type="button" class="flex w-full items-center justify-between rounded-lg py-2 pr-3.5 pl-3 text-base/7 font-semibold text-gray-900 hover:bg-gray-50" aria-controls="mobile-resources-panel" aria-expanded="false" id="mobile-resources-dropdown-button">
                                    Resources
                                    <svg class="size-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div class="mt-2 space-y-2 hidden" id="mobile-resources-panel">
                                    <a href="/Resources/Pricing-Finance.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Pricing & Finance</a>
                                    <a href="/Resources/Customer-Resources.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Customer Resources</a>
                                    <a href="/Resources/Blog.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Blog/News</a>
                                    <a href="/Resources/FAQs.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">FAQs</a>
                                    <a href="/Resources/Support-Center.php" class="block rounded-lg py-2 pr-3 pl-6 text-sm/7 font-semibold text-gray-900 hover:bg-gray-50">Support Center</a>
                                </div>
                            </div>
                            <a href="/Resources/Contact.php" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 <?php echo isActive('Resources/Contact.php', $currentPage); ?>">Contact</a>
                        </div>
                        <div class="py-6">
                            <a href="/customer/login.php" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Log in</a>
                            <a href="#" onclick="showAIChat('general'); return false;" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50">Sign up</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const closeMobileMenuButton = document.getElementById('close-mobile-menu');
            const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
            
            const mobileServicesDropdownButton = document.getElementById('mobile-services-dropdown-button');
            const mobileServicesPanel = document.getElementById('mobile-services-panel');
            const mobileCompanyDropdownButton = document.getElementById('mobile-company-dropdown-button');
            const mobileCompanyPanel = document.getElementById('mobile-company-panel');
            const mobileResourcesDropdownButton = document.getElementById('mobile-resources-dropdown-button');
            const mobileResourcesPanel = document.getElementById('mobile-resources-panel');

            const servicesDropdownButton = document.getElementById('services-dropdown-button');
            const servicesFlyoutMenu = document.getElementById('services-flyout-menu');
            const companyDropdownButton = document.getElementById('company-dropdown-button');
            const companyFlyoutMenu = document.getElementById('company-flyout-menu');
            const resourcesDropdownButton = document.getElementById('resources-dropdown-button');
            const resourcesFlyoutMenu = document.getElementById('resources-flyout-menu');
            const mainHeader = document.getElementById('main-header');

            let servicesTimeout, companyTimeout, resourcesTimeout;
            const hoverDelay = 100;

            function showDesktopFlyout(button, menu) {
                clearTimeout(servicesTimeout);
                clearTimeout(companyTimeout);
                clearTimeout(resourcesTimeout);
                
                // Hide all other menus
                [servicesFlyoutMenu, companyFlyoutMenu, resourcesFlyoutMenu].forEach(m => {
                    if (m !== menu) m.classList.remove('visible');
                });
                [servicesDropdownButton, companyDropdownButton, resourcesDropdownButton].forEach(b => {
                    if (b !== button) {
                        b.setAttribute('aria-expanded', 'false');
                        b.querySelector('svg').classList.remove('rotate-180');
                    }
                });

                menu.classList.add('visible');
                button.setAttribute('aria-expanded', 'true');
                button.querySelector('svg').classList.add('rotate-180');
            }

            function hideDesktopFlyout(button, menu) {
                return setTimeout(() => {
                    menu.classList.remove('visible');
                    button.setAttribute('aria-expanded', 'false');
                    button.querySelector('svg').classList.remove('rotate-180');
                }, hoverDelay);
            }

            // Mobile Menu Drawer Logic
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', () => {
                    mobileMenuDrawer.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
            }

            if (closeMobileMenuButton) {
                closeMobileMenuButton.addEventListener('click', () => {
                    mobileMenuDrawer.classList.add('hidden');
                    document.body.style.overflow = '';
                });
            }

            if (mobileMenuDrawer) {
                mobileMenuDrawer.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileMenuDrawer.classList.add('hidden');
                        document.body.style.overflow = '';
                    });
                });
            }

            // Mobile Accordion Logic
            function setupMobileAccordion(button, panel) {
                if(button && panel) {
                    button.addEventListener('click', () => {
                        const isExpanded = button.getAttribute('aria-expanded') === 'true';
                        button.setAttribute('aria-expanded', !isExpanded);
                        panel.classList.toggle('hidden');
                        button.querySelector('svg').classList.toggle('rotate-180', !isExpanded);
                        // Close other panels
                        [mobileServicesPanel, mobileCompanyPanel, mobileResourcesPanel].forEach(p => {
                           if(p !== panel) {
                               p.classList.add('hidden');
                               const otherButton = document.getElementById(p.id.replace('panel','dropdown-button'));
                               if(otherButton) {
                                   otherButton.setAttribute('aria-expanded','false');
                                   otherButton.querySelector('svg').classList.remove('rotate-180');
                               }
                           }
                        });
                    });
                }
            }
            setupMobileAccordion(mobileServicesDropdownButton, mobileServicesPanel);
            setupMobileAccordion(mobileCompanyDropdownButton, mobileCompanyPanel);
            setupMobileAccordion(mobileResourcesDropdownButton, mobileResourcesPanel);

            // Desktop Flyout Logic
            function setupDesktopFlyout(button, menu) {
                let timeoutVar;
                if(button && menu) {
                    button.addEventListener('mouseenter', () => showDesktopFlyout(button, menu));
                    menu.addEventListener('mouseenter', () => clearTimeout(timeoutVar));
                    button.addEventListener('mouseleave', () => { timeoutVar = hideDesktopFlyout(button, menu); });
                    menu.addEventListener('mouseleave', () => { timeoutVar = hideDesktopFlyout(button, menu); });
                }
            }
            setupDesktopFlyout(servicesDropdownButton, servicesFlyoutMenu);
            setupDesktopFlyout(companyDropdownButton, companyFlyoutMenu);
            setupDesktopFlyout(resourcesDropdownButton, resourcesFlyoutMenu);

            // Header Scroll Effect
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 50) {
                    mainHeader.classList.add('header-scrolled');
                } else {
                    mainHeader.classList.remove('header-scrolled');
                }
            });
        });
    </script>
</body>
</html>