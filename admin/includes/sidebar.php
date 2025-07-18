<?php
// admin/includes/sidebar.php
// Assumes companyName is available globally (e.g., from admin/index.php)
require_once __DIR__ . '/../../includes/functions.php'; // Ensure functions are loaded

$companyName = $companyName ?? 'CAT Dump'; // Fallback for includes
$notification_counts = get_admin_notification_counts();
$quotes_count = $notification_counts['quotes'] ?? 0;
$invoices_count = $notification_counts['invoices'] ?? 0;
?>

<aside class="w-full md:w-64 bg-blue-900 text-white flex-shrink-0 p-4 shadow-lg md:rounded-r-lg hidden md:flex flex-col">
    <div class="flex items-center justify-center md:justify-start mb-8 flex-col">
        <img src="../assets/images/logocatdump.png" alt="<?php echo htmlspecialchars($companyName); ?> Logo" class="h-12 mb-2" onerror="this.onerror=null;this.src='https://placehold.co/100x48/000/FFF?text=<?php echo urlencode($companyName); ?>';">
        <span class="text-white text-2xl font-bold">Admin Panel</span>
    </div>
    <nav class="flex-1 space-y-2">
        <a href="#dashboard" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="dashboard">
            <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
        </a>
        <a href="#users" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="users">
            <i class="fas fa-users mr-3"></i>Users
        </a>
        <a href="#quotes" class="nav-link-desktop flex items-center justify-between p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="quotes">
            <span class="flex items-center"><i class="fas fa-file-invoice mr-3"></i>Quotes</span>
            <?php if ($quotes_count > 0): ?>
                <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $quotes_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="#bookings" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="bookings">
            <i class="fas fa-book-open mr-3"></i>Bookings
        </a>
        <a href="#invoices" class="nav-link-desktop flex items-center justify-between p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="invoices">
            <span class="flex items-center"><i class="fas fa-file-invoice-dollar mr-3"></i>Invoices</span>
            <?php if ($invoices_count > 0): ?>
                <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $invoices_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="#reviews" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="reviews">
            <i class="fas fa-star mr-3"></i>Reviews
        </a>
        <a href="#equipment" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="equipment">
            <i class="fas fa-dumpster mr-3"></i>Equipment
        </a>
        <a href="#junk_removal" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="junk_removal">
            <i class="fas fa-fire mr-3"></i>Junk Removal
        </a>
        <a href="#vendors" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="vendors">
            <i class="fas fa-industry mr-3"></i>Vendors
        </a>
        <a href="#settings" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="settings">
            <i class="fas fa-cogs mr-3"></i>Settings
        </a>
        <a href="#notifications" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="notifications">
            <i class="fas fa-bell mr-3"></i>Notifications
        </a>
    </nav>
    <div class="mt-8 pt-4 border-t border-blue-700 space-y-2">
        <a href="#" class="nav-link-desktop flex items-center p-3 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="logout" onclick="showModal('admin-logout-modal'); return false;">
            <i class="fas fa-sign-out-alt mr-3"></i>Logout
        </a>
    </div>
</aside>

<div id="mobile-nav" class="fixed bottom-0 left-0 right-0 bg-blue-900 text-white flex justify-around p-2 shadow-lg md:hidden z-50">
    <a href="#dashboard" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="dashboard">
        <i class="fas fa-tachometer-alt text-xl mb-1"></i>
        <span class="text-xs">Dash</span>
    </a>
    <a href="#users" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="users">
        <i class="fas fa-users text-xl mb-1"></i>
        <span class="text-xs">Users</span>
    </a>
    <a href="#quotes" class="nav-link-mobile relative flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="quotes">
        <i class="fas fa-file-invoice text-xl mb-1"></i>
        <span class="text-xs">Quotes</span>
        <?php if ($quotes_count > 0): ?>
            <span class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full" style="transform: translate(50%, -50%);"><?php echo $quotes_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="#bookings" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="bookings">
        <i class="fas fa-book-open text-xl mb-1"></i>
        <span class="text-xs">Bookings</span>
    </a>
     <a href="#invoices" class="nav-link-mobile relative flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="invoices">
        <i class="fas fa-file-invoice-dollar text-xl mb-1"></i>
        <span class="text-xs">Invoices</span>
        <?php if ($invoices_count > 0): ?>
             <span class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full" style="transform: translate(50%, -50%);"><?php echo $invoices_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="#settings" class="nav-link-mobile flex flex-col items-center p-2 rounded-lg text-blue-200 hover:bg-blue-700 hover:text-white transition-colors duration-200" data-section="settings">
        <i class="fas fa-cogs text-xl mb-1"></i>
        <span class="text-xs">Settings</span>
    </a>
</div>