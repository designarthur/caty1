<?php
// customer/includes/sidebar.php
// Assumes companyName is available globally (e.g., from dashboard.php)

$companyName = $companyName ?? 'CAT Dump'; // Fallback for includes
?>

<aside class="w-full md:w-64 bg-blue-900 text-white flex-shrink-0 p-4 shadow-lg md:rounded-r-lg hidden md:flex flex-col">
    <div class="flex items-center justify-center md:justify-start mb-8 flex-col">
        <img src="/assets/images/logo.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-12 mb-2" onerror="this.onerror=null;this.src='https://catproject.prelaunchdesigns.com/assets/images/logocatdump.png';">
        <span class="text-white text-2xl font-bold">Customer Dashboard</span>
    </div>
    <nav class="flex-1 space-y-2">
        <a href="#dashboard" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="dashboard">
            <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
        </a>
        <a href="#quotes" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="quotes">
            <i class="fas fa-file-invoice mr-3"></i>My Quotes
        </a>
        <a href="#bookings" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="bookings">
            <i class="fas fa-book-open mr-3"></i>Equipment Bookings
        </a>
        <a href="#invoices" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="invoices">
            <i class="fas fa-file-invoice-dollar mr-3"></i>Invoices
        </a>
        <a href="#edit-profile" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="edit-profile">
            <i class="fas fa-user-edit mr-3"></i>Edit Profile
        </a>
        <a href="#change-password" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="change-password">
            <i class="fas fa-key mr-3"></i>Change Password
        </a>
        <a href="#payment-methods" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="payment-methods">
            <i class="fas fa-credit-card mr-3"></i>Payment Methods
        </a>
        <a href="#junk-removal" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="junk-removal">
            <i class="fas fa-dumpster-fire mr-3"></i>Junk Removal
        </a>
        <a href="#notifications" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="notifications">
            <i class="fas fa-bell mr-3"></i>Notifications
        </a>
       <a href="#tutorial" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="tutorial">
    <i class="fas fa-question-circle mr-3"></i>Start Tutorial
</a>
    </nav>
    <div class="mt-8 pt-4 border-t border-blue-700 space-y-2">
        <a href="/customer/logout.php" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="logout">
            <i class="fas fa-sign-out-alt mr-3"></i>Logout
        </a>
        <a href="#" class="nav-link-desktop flex items-center p-3 rounded-lg text-red-300 hover:bg-red-700 hover:text-white transition-colors duration-200" data-section="delete-account">
            <i class="fas fa-trash-alt mr-3"></i>Delete Account
        </a>
    </div>
</aside>

<div id="mobile-nav" class="fixed bottom-0 left-0 right-0 bg-blue-900 text-white flex justify-around p-2 shadow-lg md:hidden z-50">
    <a href="#dashboard" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="dashboard">
        <i class="fas fa-tachometer-alt text-xl mb-1"></i>
        <span class="text-xs">Dashboard</span>
    </a>
    <a href="#quotes" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="quotes">
        <i class="fas fa-file-invoice text-xl mb-1"></i>
        <span class="text-xs">Quotes</span>
    </a>
    <a href="#bookings" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="bookings">
        <i class="fas fa-book-open text-xl mb-1"></i>
        <span class="text-xs">Bookings</span>
    </a>
    <a href="#invoices" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="invoices">
        <i class="fas fa-file-invoice-dollar text-xl mb-1"></i>
        <span class="text-xs">Invoices</span>
    </a>
    <a href="#edit-profile" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="edit-profile">
        <i class="fas fa-user-edit text-xl mb-1"></i>
        <span class="text-xs">Profile</span>
    </a>
    <a href="#junk-removal" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="junk-removal">
        <i class="fas fa-dumpster-fire text-xl mb-1"></i>
        <span class="text-xs">Junk</span>
    </a>
</div>