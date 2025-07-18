<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php'; // For getSystemSetting and CSRF
require_once __DIR__ . '/includes/session.php'; // For is_logged_in

generate_csrf_token();
$csrf_token = $_SESSION['csrf_token'];

$companyName = getSystemSetting('company_name') ?? 'Catdump';
$user_id = $_SESSION['user_id'] ?? null;
$user_profile_data = [];

if ($user_id) {
    // Fetch user data for pre-filling common fields if logged in
    global $conn; // Access the global database connection
    $stmt_user = $conn->prepare("SELECT first_name, last_name, email, phone_number, company, address, city, state, zip_code FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_profile_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    $conn->close();
}

// Function to format pre-filled value, avoiding 'Undefined array key' warnings
function get_prefill_value($data, $key) {
    return htmlspecialchars($data[$key] ?? '');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get a Quote - <?php echo htmlspecialchars($companyName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
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
            padding: 2rem; /* Adjusted padding */
            margin-bottom: 2rem; /* Adjusted margin */
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        @media (min-width: 768px) {
            .section-box {
                padding: 4rem; /* Original padding for larger screens */
            }
        }
        .btn-primary {
            background-color: #1a73e8;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 0.75rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.4);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .btn-primary:hover {
            background-color: #155bb5;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(26, 115, 232, 0.6);
        }
        .tab-button {
            padding: 0.75rem 1.5rem;
            border-bottom: 2px solid transparent;
            font-size: 1.125rem; /* text-lg */
            font-weight: 500; /* font-medium */
            color: #6b7280; /* gray-500 */
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        .tab-button:hover {
            color: #1a73e8;
            border-color: #a3c9f7; /* lighter blue on hover */
        }
        .tab-button.active {
            border-color: #1a73e8;
            color: #1a73e8;
            font-weight: 600; /* font-semibold */
        }
        .form-input-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 0.25rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            color: #2d3748;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #4a5568;
            cursor: pointer;
        }
        .checkbox-input {
            margin-right: 0.5rem;
            width: 1rem;
            height: 1rem;
            color: #1a73e8;
            background-color: #f9fafb;
            border: 1px solid #d2d6dc;
            border-radius: 0.25rem;
            transition: all 0.2s ease;
        }
        .checkbox-input:checked {
            background-color: #1a73e8;
            border-color: #1a73e8;
        }
        .checkbox-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }

        /* Table styling for larger screens */
        .table-header-cell {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table-body-cell {
            padding: 0.75rem 1rem;
            font-size: 0.9375rem; /* ~15px */
            color: #374151; /* gray-700 */
        }
        .table-row:nth-child(even) {
            background-color: #f9fafb; /* light gray for even rows */
        }
        .table-row:hover {
            background-color: #f3f4f6; /* slightly darker on hover */
        }
        /* Inline editable inputs in tables */
        .edit-input {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.9375rem;
            color: #2d3748;
        }
        .edit-input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        /* Responsive Table/Card View */
        /* Hide cards by default on larger screens, show table */
        .card-view-container {
            display: none;
        }
        .table-view-container {
            display: block;
        }

        /* Mobile View: Hide table, show cards */
        @media (max-width: 767px) {
            .table-view-container {
                display: none;
            }
            .card-view-container {
                display: block;
            }
            .item-card {
                background-color: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                padding: 1rem;
                margin-bottom: 1rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .item-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.75rem;
                padding-bottom: 0.5rem;
                border-bottom: 1px dashed #e2e8f0;
            }
            .item-card-title {
                font-weight: 600;
                color: #1a73e8;
                font-size: 1.125rem;
            }
            .item-card-detail {
                display: flex;
                flex-direction: column;
                margin-bottom: 0.5rem;
            }
            .item-card-detail label {
                font-size: 0.75rem;
                color: #6b7280;
                margin-bottom: 0.25rem;
            }
            .item-card-detail .edit-input {
                width: 100%;
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            .item-card .remove-btn {
                padding: 0.5rem;
                font-size: 1rem;
            }
        }


        /* Toast styles */
        #toast-container {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column-reverse;
            gap: 0.5rem;
        }
        .toast {
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateY(100%);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            min-width: 250px;
            max-width: 350px;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast.bg-success { background-color: #48bb78; }
        .toast.bg-error { background-color: #ef4444; }
        .toast.bg-info { background-color: #3b82f6; }
        .toast.bg-warning { background-color: #f59e0b; }

        /* Camera Options Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .modal-button {
            display: block;
            width: 100%;
            padding: 0.75rem 1.5rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        .modal-button:last-child {
            margin-bottom: 0;
        }
        
        
        /* Keyframe animation for the thumbs-up icon */
@keyframes pop-in {
    0% { transform: scale(0.5); opacity: 0; }
    60% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(1); }
}

.success-icon {
    /* Animation will be triggered by JS */
}

.success-icon.animate {
    animation: pop-in 0.5s ease-out forwards;
}

/* Adjust modal content for the new layout */
#success-modal .modal-content {
    max-width: 500px; /* A bit wider for the message */
    text-align: center;
}

/* Ensure modal buttons can be side-by-side on small screens and up */
#success-modal .modal-button {
    display: inline-block;
    width: auto;
    margin-bottom: 0;
    text-decoration: none; /* For the <a> tag */
}
    </style>
</head>
<body class="antialiased">
    <?php include __DIR__ . '/includes/public_header.php'; ?>

    <main class="py-8 md:py-12">
        <section class="container-box">
            <div class="section-box">
                <h1 class="text-4xl md:text-5xl font-bold text-center mb-6 md:mb-8 text-gray-800">Get Your Instant Quote</h1>
                <p class="text-center text-gray-600 mb-6 md:mb-8 text-base md:text-lg">Fill out the form below to get a personalized quote for your equipment rental or junk removal needs. We'll create an account for you automatically upon submission.</p>

                <form id="quote-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="service_type" id="hidden-service-type" value="equipment_rental">
                    <input type="hidden" name="quote_id" id="hidden-quote-id" value="">

                    <h2 class="text-2xl font-semibold mb-4 text-blue-800">Your Contact Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="form-input-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-input" placeholder="John Doe" value="<?php echo get_prefill_value($user_profile_data, 'first_name') . ' ' . get_prefill_value($user_profile_data, 'last_name'); ?>" required>
                        </div>
                        <div class="form-input-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="john.doe@example.com" value="<?php echo get_prefill_value($user_profile_data, 'email'); ?>" required>
                        </div>
                        <div class="form-input-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="123-456-7890" value="<?php echo get_prefill_value($user_profile_data, 'phone_number'); ?>" required>
                        </div>
                        <div class="form-input-group">
                            <label for="company" class="form-label">Company (Optional)</label>
                            <input type="text" id="company" name="company" class="form-input" placeholder="ABC Corp" value="<?php echo get_prefill_value($user_profile_data, 'company'); ?>">
                        </div>
                        <div class="form-input-group md:col-span-2">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" id="address" name="address" class="form-input" placeholder="123 Main St" value="<?php echo get_prefill_value($user_profile_data, 'address'); ?>" required>
                        </div>
                        <div class="form-input-group">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" name="city" class="form-input" placeholder="Anytown" value="<?php echo get_prefill_value($user_profile_data, 'city'); ?>" required>
                        </div>
                        <div class="form-input-group">
                            <label for="state" class="form-label">State</label>
                            <input type="text" id="state" name="state" class="form-input" placeholder="CA" value="<?php echo get_prefill_value($user_profile_data, 'state'); ?>" required>
                        </div>
                        <div class="form-input-group">
                            <label for="zip-code" class="form-label">ZIP Code</label>
                            <input type="text" id="zip-code" name="zip_code" class="form-input" placeholder="90210" value="<?php echo get_prefill_value($user_profile_data, 'zip_code'); ?>" required>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200 mb-6 mt-8">
                        <button type="button" id="tab-equipment-rental" class="tab-button active text-blue-600 border-b-2 border-blue-600">Equipment Rental</button>
                        <button type="button" id="tab-junk-removal" class="tab-button ml-4">Junk Removal</button>
                    </div>

                    <!-- Tab Content: Equipment Rental -->
                    <div id="content-equipment-rental" class="tab-content">
                        <h2 class="text-2xl font-semibold mb-4 text-blue-800">Equipment Rental Details</h2>
                        <div class="form-input-group mb-4">
                            <label for="equipment-type-select" class="form-label">Select Equipment Type</label>
                            <select id="equipment-type-select" class="form-input">
                                <option value="">-- Select Equipment Type --</option>
                                <option value="dumpster">Dumpster</option>
                                <option value="temporary_toilet">Temporary Toilet</option>
                                <option value="storage_container">Storage Container</option>
                            </select>
                        </div>

                        <!-- Dynamic Equipment Fields Container -->
                        <div id="equipment-fields-container" class="mb-6">
                            <!-- Dumpster Fields -->
                            <div id="dumpster-fields" class="equipment-fields grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                                <div class="form-input-group">
                                    <label for="dumpster-size" class="form-label">Dumpster Size</label>
                                    <select id="dumpster-size" class="form-input">
                                        <option value="">-- Select Size --</option>
                                        <option value="10-yard">10-Yard</option>
                                        <option value="15-yard">15-Yard</option>
                                        <option value="20-yard">20-Yard</option>
                                        <option value="30-yard">30-Yard</option>
                                        <option value="40-yard">40-Yard</option>
                                    </select>
                                </div>
                                <div class="form-input-group">
                                    <label for="dumpster-weight" class="form-label">Estimated Weight (tons)</label>
                                    <input type="number" id="dumpster-weight" class="form-input" placeholder="e.g., 2" step="0.1" min="0">
                                </div>
                                <div class="form-input-group">
                                    <label for="dumpster-rental-duration" class="form-label">Rental Duration (days)</label>
                                    <input type="number" id="dumpster-rental-duration" class="form-input" placeholder="e.g., 7" min="1">
                                </div>
                                <div class="form-input-group">
                                    <label for="dumpster-delivery-date" class="form-label">Delivery Date</label>
                                    <input type="date" id="dumpster-delivery-date" class="form-input">
                                </div>
                                <div class="form-input-group">
                                    <label for="dumpster-pickup-date" class="form-label">Pickup Date</label>
                                    <input type="date" id="dumpster-pickup-date" class="form-input">
                                </div>
                                <div class="form-input-group">
                                    <label for="dumpster-waste-type" class="form-label">Type of Waste</label>
                                    <input type="text" id="dumpster-waste-type" class="form-input" placeholder="e.g., Household Junk, Construction Debris">
                                </div>
                                <div class="form-input-group md:col-span-2">
                                    <label for="dumpster-project-description" class="form-label">Project Description</label>
                                    <textarea id="dumpster-project-description" class="form-input" rows="3" placeholder="Briefly describe your project"></textarea>
                                </div>
                            </div>

                            <!-- Temporary Toilet Fields -->
                            <div id="temporary_toilet-fields" class="equipment-fields grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                                <div class="form-input-group">
                                    <label for="toilet-project-event-type" class="form-label">Project/Event Type</label>
                                    <input type="text" id="toilet-project-event-type" class="form-input" placeholder="e.g., Wedding, Construction Site">
                                </div>
                                <div class="form-input-group">
                                    <label for="toilet-num-attendees-workers" class="form-label">Number of Attendees/Workers</label>
                                    <input type="number" id="toilet-num-attendees-workers" class="form-input" min="1" placeholder="e.g., 50">
                                </div>
                                <div class="form-input-group">
                                    <label for="toilet-rental-duration" class="form-label">Rental Duration (days)</label>
                                    <input type="number" id="toilet-rental-duration" class="form-input" min="1" placeholder="e.g., 3">
                                </div>
                                <div class="form-input-group">
                                    <label for="toilet-delivery-date" class="form-label">Delivery Date</label>
                                    <input type="date" id="toilet-delivery-date" class="form-input">
                                </div>
                                <div class="form-input-group">
                                    <label for="toilet-pickup-date" class="form-label">Pickup Date</label>
                                    <input type="date" id="toilet-pickup-date" class="form-input">
                                </div>
                                <div class="form-input-group">
                                    <label for="toilet-num-units" class="form-label">Number of Units</label>
                                    <input type="number" id="toilet-num-units" class="form-input" min="1" placeholder="e.g., 2">
                                </div>
                                <div class="form-input-group">
                                    <label for="toilet-unit-type" class="form-label">Unit Type</label>
                                    <select id="toilet-unit-type" class="form-input">
                                        <option value="">-- Select Unit Type --</option>
                                        <option value="standard">Standard</option>
                                        <option value="ADA">ADA Accessible</option>
                                        <option value="deluxe">Deluxe Flushing</option>
                                        <option value="luxury_trailer">Luxury Restroom Trailer</option>
                                    </select>
                                </div>
                                <div class="form-input-group">
                                    <label class="form-label">Additional Services</label>
                                    <div class="flex flex-col space-y-2 mt-1">
                                        <label class="checkbox-label"><input type="checkbox" id="toilet-handwashing-stations" class="checkbox-input" value="Handwashing Stations"> Handwashing Stations</label>
                                        <label class="checkbox-label"><input type="checkbox" id="toilet-sanitizers" class="checkbox-input" value="Sanitizers"> Sanitizers</label>
                                    </div>
                                </div>
                                <div class="form-input-group md:col-span-2">
                                    <label for="toilet-comments" class="form-label">Comments</label>
                                    <textarea id="toilet-comments" class="form-input" rows="3" placeholder="Any special requests or details"></textarea>
                                </div>
                            </div>

                            <!-- Storage Container Fields -->
                            <div id="storage_container-fields" class="equipment-fields grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                                <div class="form-input-group">
                                    <label for="container-size" class="form-label">Container Size</label>
                                    <select id="container-size" class="form-input">
                                        <option value="">-- Select Size --</option>
                                        <option value="10ft">10ft</option>
                                        <option value="20ft">20ft</option>
                                        <option value="40ft">40ft</option>
                                    </select>
                                </div>
                                <div class="form-input-group">
                                    <label for="container-num-containers" class="form-label">Number of Containers</label>
                                    <input type="number" id="container-num-containers" class="form-input" min="1" placeholder="e.g., 1">
                                </div>
                                <div class="form-input-group">
                                    <label for="container-rental-duration" class="form-label">Rental Duration (days)</label>
                                    <input type="number" id="container-rental-duration" class="form-input" min="1" placeholder="e.g., 30">
                                </div>
                                <div class="form-input-group">
                                    <label for="container-delivery-date" class="form-label">Delivery Date</label>
                                    <input type="date" id="container-delivery-date" class="form-input">
                                </div>
                                <div class="form-input-group">
                                    <label for="container-pickup-date" class="form-label">Pickup Date</label>
                                    <input type="date" id="container-pickup-date" class="form-input">
                                </div>
                                <div class="form-input-group">
                                    <label for="container-purpose" class="form-label">Purpose</label>
                                    <input type="text" id="container-purpose" class="form-input" placeholder="e.g., Construction Storage, Home Renovation">
                                </div>
                                <div class="form-input-group">
                                    <label for="container-access-requirements" class="form-label">Access Requirements</label>
                                    <input type="text" id="container-access-requirements" class="form-input" placeholder="e.g., paved surface, 60ft clear space">
                                </div>
                                <div class="form-input-group">
                                    <label class="form-label">Additional Services</label>
                                    <div class="flex flex-col space-y-2 mt-1">
                                        <label class="checkbox-label"><input type="checkbox" id="container-lighting" class="checkbox-input" value="Lighting"> Lighting</label>
                                        <label class="checkbox-label"><input type="checkbox" id="container-shelving" class="checkbox-input" value="Shelving"> Shelving</label>
                                        <label class="checkbox-label"><input type="checkbox" id="container-lock" class="checkbox-input" value="Lock"> Lock</label>
                                    </div>
                                </div>
                                <div class="form-input-group md:col-span-2">
                                    <label for="container-comments" class="form-label">Comments</label>
                                    <textarea id="container-comments" class="form-input" rows="3" placeholder="Any special requests or details"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-equipment-item-btn" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 shadow-md hidden"><i class="fas fa-plus mr-2"></i>Add Equipment to List</button>

                        <h3 class="text-xl font-semibold mt-8 mb-4 text-blue-800">Your Equipment List</h3>
                        <!-- Table View for larger screens -->
                        <div class="table-view-container overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="table-header-cell">Type</th>
                                        <th class="table-header-cell">Quantity</th>
                                        <th class="table-header-cell">Details</th>
                                        <th class="table-header-cell">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="equipment-list-tbody" class="divide-y divide-gray-200 bg-white">
                                    <!-- Dynamic rows added here by JS -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Card View for smaller screens -->
                        <div id="equipment-list-card-view" class="card-view-container space-y-4 mt-4">
                            <!-- Dynamic cards added here by JS -->
                        </div>
                        <p id="no-equipment-message" class="text-gray-500 text-center mt-4">No equipment added yet.</p>
                    </div>

                    <!-- Tab Content: Junk Removal -->
                    <div id="content-junk-removal" class="tab-content hidden">
                        <h2 class="text-2xl font-semibold mb-4 text-blue-800">Junk Removal Details</h2>
                        <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
                            <p class="text-gray-600 text-base sm:text-lg">Describe or upload media of your junk items for AI analysis.</p>
                            <div>
                                <button type="button" id="ai-vision-trigger-btn" class="py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 shadow-md cursor-pointer text-sm">
                                    <i class="fas fa-camera-retro mr-2"></i>Upload for AI Vision
                                </button>
                                <input type="file" id="ai-vision-upload" class="hidden" multiple accept="image/*,video/*">
                                <video id="hiddenVideo" style="display:none;" controls></video>
                                <canvas id="hiddenCanvas" style="display:none;"></canvas>
                            </div>
                        </div>
                        <div id="ai-vision-loading-message" class="hidden text-center text-blue-600 mb-4 py-2 bg-blue-50 rounded-md">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Analyzing media with AI...
                        </div>
                        <div id="junk-items-manual-entry" class="mb-6">
                            <h3 class="text-xl font-semibold mb-3 text-blue-800">Junk Items (Manual Entry/Review)</h3>
                            <!-- Table View for larger screens -->
                            <div class="table-view-container overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                                <table id="junk-items-table" class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="table-header-cell">Item</th>
                                            <th class="table-header-cell">Quantity</th>
                                            <th class="table-header-cell">Est. Dimensions</th>
                                            <th class="table-header-cell">Est. Weight</th>
                                            <th class="table-header-cell">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="junk-items-tbody" class="divide-y divide-gray-200 bg-white">
                                    </tbody>
                                </table>
                            </div>
                            <!-- Card View for smaller screens -->
                            <div id="junk-items-card-view" class="card-view-container space-y-4 mt-4">
                                <!-- Dynamic cards added here by JS -->
                            </div>

                            <button type="button" id="add-junk-item-row" class="mt-4 py-2 px-4 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors duration-200 text-sm">
                                <i class="fas fa-plus mr-2"></i>Add Item Manually
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                            <div class="form-input-group">
                                <label for="junk-removal-date" class="form-label">Preferred Date for Service</label>
                                <input type="date" id="junk-removal-date" name="junk_removal_date" class="form-input">
                            </div>
                            <div class="form-input-group">
                                <label for="junk-removal-time" class="form-label">Preferred Time for Service</label>
                                <input type="time" id="junk-removal-time" name="junk_removal_time" class="form-input">
                            </div>
                            <div class="form-input-group md:col-span-2">
                                <label for="junk-project-description" class="form-label">Project Description / Additional Comments</label>
                                <textarea id="junk-project-description" name="junk_project_description" class="form-input" rows="3" placeholder="Any specific details for junk removal"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-8 text-center">
                        <button type="submit" id="submit-quote-request" class="btn-primary">Submit Request for Pricing</button>
                    </div>
                </form>
            </div>
        </section>
        
        
        <div id="success-modal" class="modal hidden">
    <div class="modal-content text-center p-8">
        <div class="mb-4">
            <i class="fas fa-thumbs-up text-green-500 text-6xl success-icon"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-800 mb-3">Request Submitted!</h2>
        <p id="success-modal-message" class="text-gray-600 mb-8 text-base">
            </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <button type="button" id="request-another-quote-btn" class="modal-button bg-blue-600 text-white hover:bg-blue-700 px-6 py-3">
                Request Another Quote
            </button>
            <a href="/customer/login.php" class="modal-button bg-gray-700 text-white hover:bg-gray-800 px-6 py-3">
                Login to Dashboard
            </a>
        </div>
    </div>
</div>
    </main>

    <?php include __DIR__ . '/includes/public_footer.php'; ?>

    <!-- Camera Options Modal -->
    <div id="camera-options-modal" class="modal hidden">
        <div class="modal-content">
            <h3 class="text-xl font-bold mb-4">Choose Media Source</h3>
            <button type="button" id="option-upload-gallery" class="modal-button bg-blue-600 text-white hover:bg-blue-700">
                <i class="fas fa-upload mr-2"></i>Upload Photo/Video from Gallery
            </button>
            <button type="button" id="option-take-photo" class="modal-button bg-green-600 text-white hover:bg-green-700">
                <i class="fas fa-camera mr-2"></i>Take a Photo
            </button>
            <button type="button" id="option-shoot-video" class="modal-button bg-purple-600 text-white hover:bg-purple-700">
                <i class="fas fa-video mr-2"></i>Shoot a Video
            </button>
            <button type="button" id="option-cancel" class="modal-button bg-gray-300 text-gray-800 hover:bg-gray-400">Cancel</button>
        </div>
    </div>


    <script>
        // Global utility functions for modals and toasts (should be in public_footer.php)
        // Re-declaring here to ensure availability if public_footer is not executed in DOMContentLoaded order
        window.showToast = window.showToast || function(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || (() => {
                const div = document.createElement('div');
                div.id = 'toast-container';
                div.className = 'fixed bottom-4 right-4 z-[10000] flex flex-col-reverse gap-2';
                document.body.appendChild(div);
                return div;
            })();
            const toast = document.createElement('div');
            let bgColorClass = 'bg-blue-500';
            if (type === 'success') bgColorClass = 'bg-green-500';
            if (type === 'error') bgColorClass = 'bg-red-500';
            if (type === 'warning') bgColorClass = 'bg-orange-500';
            toast.className = `toast px-4 py-2 rounded-lg text-white shadow-lg opacity-0 transform translate-y-full transition-all duration-300 ${bgColorClass}`;
            toast.textContent = message;
            toastContainer.appendChild(toast);
            void toast.offsetWidth;
            toast.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => {
                toast.classList.remove('opacity-100', 'translate-y-0');
                toast.classList.add('opacity-0', 'translate-y-full');
                toast.addEventListener('transitionend', () => toast.remove());
            }, 3000);
        };
        
        // --- Video Frame Extraction Function (from public_footer.php) ---
        window.extractFramesFromVideo = window.extractFramesFromVideo || function(videoFile, numFrames = 10) {
            return new Promise((resolve, reject) => {
                const video = document.getElementById('hiddenVideo');
                const canvas = document.getElementById('hiddenCanvas');
                if (!video || !canvas) {
                    // Fallback or error if elements are not present, though they are in this file.
                    return reject(new Error("Required hidden video/canvas elements are not found in the DOM."));
                }
                const context = canvas.getContext('2d');
                const frames = [];
                let framesExtracted = 0;
                video.preload = 'metadata';
                video.muted = true;
                video.src = URL.createObjectURL(videoFile);

                video.onloadeddata = () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    video.currentTime = (video.duration / (numFrames + 1));
                };
                video.onseeked = () => {
                    if (framesExtracted >= numFrames) {
                        if (video.src) URL.revokeObjectURL(video.src);
                        resolve(frames);
                        return;
                    }
                    try {
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        frames.push(canvas.toDataURL('image/jpeg'));
                        framesExtracted++;
                        if (framesExtracted < numFrames) {
                            const nextTime = (framesExtracted + 1) * (video.duration / (numFrames + 1));
                            video.currentTime = nextTime;
                        } else {
                            if (video.src) URL.revokeObjectURL(video.src);
                            resolve(frames);
                        }
                    } catch (e) {
                        reject(new Error("Error drawing video frame to canvas: " + e.message));
                    }
                };
                video.onerror = (e) => reject(new Error('Error loading video: ' + (e.target.error ? e.target.error.message : 'Unknown error')));
            });
        };

        // Helper to convert Data URL to Blob (from public_footer.php)
        window.dataURLtoBlob = window.dataURLtoBlob || function(dataurl) {
            const arr = dataurl.split(',');
            const mimeMatch = arr[0].match(/:(.*?);/);
            const mime = mimeMatch ? mimeMatch[1] : 'application/octet-stream';
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], { type: mime });
        };


        document.addEventListener('DOMContentLoaded', function() {
            const quoteForm = document.getElementById('quote-form');
             const successModal = document.getElementById('success-modal');
        const successModalMessage = document.getElementById('success-modal-message');
        const requestAnotherQuoteBtn = document.getElementById('request-another-quote-btn');
        const successIcon = successModal.querySelector('.success-icon');
            const tabEquipmentRental = document.getElementById('tab-equipment-rental');
            const tabJunkRemoval = document.getElementById('tab-junk-removal');
            const contentEquipmentRental = document.getElementById('content-equipment-rental');
            const contentJunkRemoval = document.getElementById('content-junk-removal');
            const hiddenServiceType = document.getElementById('hidden-service-type');
            const equipmentTypeSelect = document.getElementById('equipment-type-select');
            // Select all equipment field containers using a class for robustness
            const equipmentFieldsContainers = document.querySelectorAll('.equipment-fields'); 
            const addEquipmentItemBtn = document.getElementById('add-equipment-item-btn');
            const equipmentListTbody = document.getElementById('equipment-list-tbody');
            const equipmentListCardView = document.getElementById('equipment-list-card-view'); // New: Card view container
            const noEquipmentMessage = document.getElementById('no-equipment-message');
            const aiVisionTriggerBtn = document.getElementById('ai-vision-trigger-btn');
            const aiVisionUploadInput = document.getElementById('ai-vision-upload');
            const aiVisionLoadingMessage = document.getElementById('ai-vision-loading-message');
            const junkItemsTbody = document.getElementById('junk-items-tbody');
            const junkItemsCardView = document.getElementById('junk-items-card-view'); // New: Card view container
            const addJunkItemRowBtn = document.getElementById('add-junk-item-row');

            // Modals for camera options
            const cameraOptionsModal = document.getElementById('camera-options-modal');
            const optionUploadGallery = document.getElementById('option-upload-gallery');
            const optionTakePhoto = document.getElementById('option-take-photo');
            const optionShootVideo = document.getElementById('option-shoot-video');
            const optionCancel = document.getElementById('option-cancel');


            let equipmentItems = []; // Array to store equipment line items
            let junkItems = []; // Array to store junk line items

            // --- Helper for Modals ---
            function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            // Trigger animation for the success icon if it's the success modal
            if (modalId === 'success-modal') {
                successIcon.classList.add('animate');
            }
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
             // Remove animation class so it can re-trigger next time
            if (modalId === 'success-modal') {
                successIcon.classList.remove('animate');
            }
        }

            // --- Mobile Device Detection ---
            function isMobileDevice() {
                return /Mobi|Android|iPhone|iPad|iPod|Windows Phone|BlackBerry/i.test(navigator.userAgent);
            }

            // --- Tab Switching Logic ---
            function switchTab(activeTab) {
                if (activeTab === 'equipment_rental') {
                    tabEquipmentRental.classList.add('active');
                    tabJunkRemoval.classList.remove('active');
                    
                    contentEquipmentRental.classList.remove('hidden');
                    contentJunkRemoval.classList.add('hidden');
                    hiddenServiceType.value = 'equipment_rental';
                } else {
                    tabJunkRemoval.classList.add('active');
                    tabEquipmentRental.classList.remove('active');

                    contentJunkRemoval.classList.remove('hidden');
                    contentEquipmentRental.classList.add('hidden');
                    hiddenServiceType.value = 'junk_removal';
                }
            }

            tabEquipmentRental.addEventListener('click', () => switchTab('equipment_rental'));
            tabJunkRemoval.addEventListener('click', () => switchTab('junk_removal'));

            // --- Equipment Rental Tab Logic ---
            equipmentTypeSelect.addEventListener('change', function() {
                equipmentFieldsContainers.forEach(field => {
                    // Ensure field is not null/undefined before accessing classList
                    if (field) { 
                        field.classList.add('hidden');
                    }
                });
                addEquipmentItemBtn.classList.add('hidden'); // Hide add button by default

                const selectedType = this.value;
                const selectedFieldsDiv = document.getElementById(`${selectedType}-fields`);
                if (selectedFieldsDiv) { // Check if the element exists
                    selectedFieldsDiv.classList.remove('hidden');
                    addEquipmentItemBtn.classList.remove('hidden');
                }
            });

            addEquipmentItemBtn.addEventListener('click', function() {
                const selectedType = equipmentTypeSelect.value;
                let item = {};
                let isValid = true;

                // Collect data based on selected equipment type
                // Ensure to get current values from inputs
                const currentFields = document.getElementById(`${selectedType}-fields`);
                if (!currentFields) {
                    showToast('Please select an equipment type.', 'error');
                    return;
                }

                if (selectedType === 'dumpster') {
                    const size = currentFields.querySelector('#dumpster-size').value;
                    const weight = currentFields.querySelector('#dumpster-weight').value;
                    const duration = currentFields.querySelector('#dumpster-rental-duration').value;
                    const deliveryDate = currentFields.querySelector('#dumpster-delivery-date').value;
                    const pickupDate = currentFields.querySelector('#dumpster-pickup-date').value;
                    const wasteType = currentFields.querySelector('#dumpster-waste-type').value;
                    const projectDescription = currentFields.querySelector('#dumpster-project-description').value;

                    if (!size || !duration || !deliveryDate || !pickupDate) {
                        showToast('Please fill all required fields for Dumpster.', 'error');
                        isValid = false;
                    }
                    item = {
                        type: 'dumpster',
                        name: size ? `${size} Dumpster` : 'Dumpster',
                        quantity: 1, 
                        details: {
                            size, weight, duration: parseInt(duration), deliveryDate, pickupDate, wasteType, projectDescription
                        }
                    };
                } else if (selectedType === 'temporary_toilet') {
                    const projectEventType = currentFields.querySelector('#toilet-project-event-type').value;
                    const numAttendeesWorkers = currentFields.querySelector('#toilet-num-attendees-workers').value;
                    const duration = currentFields.querySelector('#toilet-rental-duration').value;
                    const deliveryDate = currentFields.querySelector('#toilet-delivery-date').value;
                    const pickupDate = currentFields.querySelector('#toilet-pickup-date').value;
                    const numUnits = currentFields.querySelector('#toilet-num-units').value;
                    const unitType = currentFields.querySelector('#toilet-unit-type').value;
                    const additionalServices = Array.from(currentFields.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
                    const comments = currentFields.querySelector('#toilet-comments').value;

                    if (!unitType || !numUnits || !duration || !deliveryDate || !pickupDate) {
                        showToast('Please fill all required fields for Temporary Toilet.', 'error');
                        isValid = false;
                    }
                    item = {
                        type: 'temporary_toilet',
                        name: unitType ? `${unitType} Portable Toilet` : 'Portable Toilet',
                        quantity: parseInt(numUnits),
                        details: {
                            projectEventType, numAttendeesWorkers: parseInt(numAttendeesWorkers), duration: parseInt(duration),
                            deliveryDate, pickupDate, unitType, additionalServices, comments
                        }
                    };
                } else if (selectedType === 'storage_container') {
                    const size = currentFields.querySelector('#container-size').value;
                    const numContainers = currentFields.querySelector('#container-num-containers').value;
                    const duration = currentFields.querySelector('#container-rental-duration').value;
                    const deliveryDate = currentFields.querySelector('#container-delivery-date').value;
                    const pickupDate = currentFields.querySelector('#container-pickup-date').value;
                    const purpose = currentFields.querySelector('#container-purpose').value;
                    const accessRequirements = currentFields.querySelector('#container-access-requirements').value;
                    const additionalServices = Array.from(currentFields.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
                    const comments = currentFields.querySelector('#container-comments').value;

                    if (!size || !numContainers || !duration || !deliveryDate || !pickupDate) {
                        showToast('Please fill all required fields for Storage Container.', 'error');
                        isValid = false;
                    }
                    item = {
                        type: 'storage_container',
                        name: size ? `${size} Storage Container` : 'Storage Container',
                        quantity: parseInt(numContainers),
                        details: {
                            size, duration: parseInt(duration), deliveryDate, pickupDate,
                            purpose, accessRequirements, additionalServices, comments
                        }
                    };
                } else {
                    isValid = false;
                    showToast('Please select an equipment type.', 'error');
                }

                if (!isValid) return;

                equipmentItems.push(item);
                renderEquipmentList();
                
                // Reset current equipment fields
                currentFields.querySelectorAll('input, select, textarea').forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                    else input.value = '';
                });
                equipmentTypeSelect.value = '';
                equipmentFieldsContainers.forEach(field => field.classList.add('hidden'));
                addEquipmentItemBtn.classList.add('hidden');
                showToast(`${item.name} added to your list!`, 'success');
            });

            function renderEquipmentList() {
                equipmentListTbody.innerHTML = '';
                equipmentListCardView.innerHTML = ''; // Clear card view as well

                if (equipmentItems.length === 0) {
                    noEquipmentMessage.classList.remove('hidden');
                } else {
                    noEquipmentMessage.classList.add('hidden');
                    equipmentItems.forEach((item, index) => {
                        // --- Table Row HTML ---
                        const tableRow = document.createElement('tr');
                        tableRow.classList.add('table-row');
                        let detailsHtmlTable = ``;
                        let quantityHtmlTable = `<input type="number" class="edit-input w-20" value="${item.quantity || ''}" data-field="quantity" data-index="${index}" style="min-width: 50px;">`;

                        if (item.type === 'dumpster') {
                             detailsHtmlTable = `Size: <input type="text" class="edit-input w-24" value="${item.details.size || ''}" data-field="size" data-index="${index}">, Wt: <input type="number" class="edit-input w-20" value="${item.details.weight || ''}" data-field="weight" data-index="${index}"> tons, Duration: <input type="number" class="edit-input w-20" value="${item.details.duration || ''}" data-field="duration" data-index="${index}"> days, Waste: <input type="text" class="edit-input w-32" value="${item.details.wasteType || ''}" data-field="wasteType" data-index="${index}">`;
                        } else if (item.type === 'temporary_toilet') {
                            detailsHtmlTable = `Type: <input type="text" class="edit-input w-28" value="${item.details.unitType || ''}" data-field="unitType" data-index="${index}">, Dur: <input type="number" class="edit-input w-16" value="${item.details.duration || ''}" data-field="duration" data-index="${index}"> days, Event: <input type="text" class="edit-input w-32" value="${item.details.projectEventType || ''}" data-field="projectEventType" data-index="${index}">`;
                            quantityHtmlTable = `<input type="number" class="edit-input w-20" value="${item.quantity || ''}" data-field="quantity" data-index="${index}" style="min-width: 50px;">`;
                        } else if (item.type === 'storage_container') {
                            detailsHtmlTable = `Size: <input type="text" class="edit-input w-24" value="${item.details.size || ''}" data-field="size" data-index="${index}">, Dur: <input type="number" class="edit-input w-16" value="${item.details.duration || ''}" data-field="duration" data-index="${index}"> days, Purpose: <input type="text" class="edit-input w-32" value="${item.details.purpose || ''}" data-field="purpose" data-index="${index}">`;
                            quantityHtmlTable = `<input type="number" class="edit-input w-20" value="${item.quantity || ''}" data-field="quantity" data-index="${index}" style="min-width: 50px;">`;
                        }
                        
                        detailsHtmlTable += `<br>Delivery: <input type="date" class="edit-input w-36" value="${item.details.deliveryDate || ''}" data-field="deliveryDate" data-index="${index}">, Pickup: <input type="date" class="edit-input w-36" value="${item.details.pickupDate || ''}" data-field="pickupDate" data-index="${index}">`;
                        
                        if (item.details.comments) {
                            detailsHtmlTable += `<br>Comments: <input type="text" class="edit-input w-full" value="${item.details.comments || ''}" data-field="comments" data-index="${index}">`;
                        }
                        if (item.details.additionalServices && item.details.additionalServices.length > 0) {
                            detailsHtmlTable += `<br>Add. Services: <input type="text" class="edit-input w-full" value="${item.details.additionalServices.join(', ') || ''}" data-field="additionalServices" data-index="${index}">`;
                        }

                        tableRow.innerHTML = `
                            <td class="table-body-cell font-semibold">${item.name}</td>
                            <td class="table-body-cell">${quantityHtmlTable}</td>
                            <td class="table-body-cell text-sm text-gray-600">${detailsHtmlTable}</td>
                            <td class="table-body-cell text-center">
                                <button type="button" class="text-red-600 hover:text-red-800 remove-equipment-item-btn" data-index="${index}"><i class="fas fa-trash"></i></button>
                            </td>
                        `;
                        equipmentListTbody.appendChild(tableRow);

                        // --- Card View HTML ---
                        const cardDiv = document.createElement('div');
                        cardDiv.classList.add('item-card');
                        let detailsHtmlCard = ``;
                        
                        if (item.type === 'dumpster') {
                            detailsHtmlCard = `
                                <div class="item-card-detail"><label>Size:</label><input type="text" class="edit-input" value="${item.details.size || ''}" data-field="size" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Weight:</label><input type="number" class="edit-input" value="${item.details.weight || ''}" data-field="weight" data-index="${index}"> tons</div>
                                <div class="item-card-detail"><label>Duration:</label><input type="number" class="edit-input" value="${item.details.duration || ''}" data-field="duration" data-index="${index}"> days</div>
                                <div class="item-card-detail"><label>Waste Type:</label><input type="text" class="edit-input" value="${item.details.wasteType || ''}" data-field="wasteType" data-index="${index}"></div>
                            `;
                        } else if (item.type === 'temporary_toilet') {
                            detailsHtmlCard = `
                                <div class="item-card-detail"><label>Unit Type:</label><input type="text" class="edit-input" value="${item.details.unitType || ''}" data-field="unitType" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Duration:</label><input type="number" class="edit-input" value="${item.details.duration || ''}" data-field="duration" data-index="${index}"> days</div>
                                <div class="item-card-detail"><label>Event Type:</label><input type="text" class="edit-input" value="${item.details.projectEventType || ''}" data-field="projectEventType" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Attendees/Workers:</label><input type="number" class="edit-input" value="${item.details.numAttendeesWorkers || ''}" data-field="numAttendeesWorkers" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Additional Services:</label><input type="text" class="edit-input" value="${(item.details.additionalServices || []).join(', ') || ''}" data-field="additionalServices" data-index="${index}"></div>
                            `;
                        } else if (item.type === 'storage_container') {
                            detailsHtmlCard = `
                                <div class="item-card-detail"><label>Size:</label><input type="text" class="edit-input" value="${item.details.size || ''}" data-field="size" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Duration:</label><input type="number" class="edit-input" value="${item.details.duration || ''}" data-field="duration" data-index="${index}"> days</div>
                                <div class="item-card-detail"><label>Purpose:</label><input type="text" class="edit-input" value="${item.details.purpose || ''}" data-field="purpose" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Access Requirements:</label><input type="text" class="edit-input" value="${item.details.accessRequirements || ''}" data-field="accessRequirements" data-index="${index}"></div>
                                <div class="item-card-detail"><label>Additional Services:</label><input type="text" class="edit-input" value="${(item.details.additionalServices || []).join(', ') || ''}" data-field="additionalServices" data-index="${index}"></div>
                            `;
                        }

                        cardDiv.innerHTML = `
                            <div class="item-card-header">
                                <span class="item-card-title">${item.name} (Qty: <input type="number" class="edit-input w-16" value="${item.quantity || ''}" data-field="quantity" data-index="${index}">)</span>
                                <button type="button" class="text-red-600 hover:text-red-800 remove-equipment-item-btn remove-btn" data-index="${index}"><i class="fas fa-trash"></i></button>
                            </div>
                            ${detailsHtmlCard}
                            <div class="item-card-detail"><label>Delivery Date:</label><input type="date" class="edit-input" value="${item.details.deliveryDate || ''}" data-field="deliveryDate" data-index="${index}"></div>
                            <div class="item-card-detail"><label>Pickup Date:</label><input type="date" class="edit-input" value="${item.details.pickupDate || ''}" data-field="pickupDate" data-index="${index}"></div>
                            ${item.details.comments ? `<div class="item-card-detail"><label>Comments:</label><input type="text" class="edit-input" value="${item.details.comments || ''}" data-field="comments" data-index="${index}"></div>` : ''}
                        `;
                        equipmentListCardView.appendChild(cardDiv);
                    });

                    // Attach input listeners for editing directly on the card view
                    equipmentListCardView.querySelectorAll('.edit-input').forEach(input => {
                        input.addEventListener('change', function() { // Use change to update on blur
                            const index = parseInt(this.dataset.index);
                            const field = this.dataset.field;
                            
                            if (field === 'quantity') {
                                equipmentItems[index].quantity = parseInt(this.value) || 1;
                            } else if (field === 'additionalServices') {
                                equipmentItems[index].details[field] = this.value.split(',').map(s => s.trim()).filter(s => s.length > 0);
                            } else {
                                equipmentItems[index].details[field] = this.value;
                            }
                        });
                    });
                }
            }

            equipmentListTbody.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-equipment-item-btn')) {
                    const index = parseInt(event.target.dataset.index);
                    equipmentItems.splice(index, 1);
                    renderEquipmentList();
                    showToast('Item removed.', 'info');
                }
            });
            equipmentListCardView.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-equipment-item-btn')) {
                    const index = parseInt(event.target.dataset.index);
                    equipmentItems.splice(index, 1);
                    renderEquipmentList();
                    showToast('Item removed.', 'info');
                }
            });


            // --- Junk Removal Tab Logic ---
            aiVisionTriggerBtn.addEventListener('click', () => {
                if (isMobileDevice()) {
                    showModal('camera-options-modal');
                } else {
                    aiVisionUploadInput.click();
                }
            });

            // Camera Options Modal Listeners
            optionUploadGallery.addEventListener('click', () => {
                aiVisionUploadInput.removeAttribute('capture');
                aiVisionUploadInput.setAttribute('accept', 'image/*,video/*');
                aiVisionUploadInput.click();
                hideModal('camera-options-modal');
            });

            optionTakePhoto.addEventListener('click', () => {
                aiVisionUploadInput.setAttribute('capture', 'camera');
                aiVisionUploadInput.setAttribute('accept', 'image/*');
                aiVisionUploadInput.click();
                hideModal('camera-options-modal');
            });

            optionShootVideo.addEventListener('click', () => {
                aiVisionUploadInput.setAttribute('capture', 'camcorder');
                aiVisionUploadInput.setAttribute('accept', 'video/*');
                aiVisionUploadInput.click();
                hideModal('camera-options-modal');
            });

            optionCancel.addEventListener('click', () => {
                hideModal('camera-options-modal');
            });


            aiVisionUploadInput.addEventListener('change', async function(event) {
                const files = event.target.files;
                if (files.length === 0) return;

                aiVisionLoadingMessage.classList.remove('hidden');
                showToast('Uploading and analyzing media with AI...', 'info');

                const formData = new FormData();
                formData.append('action', 'analyze_media');

                for (const file of files) {
                    if (file.type.startsWith('video/')) {
                        try {
                            const frames = await window.extractFramesFromVideo(file, 5); // Extract 5 frames for analysis
                            frames.forEach((frameDataUrl, index) => {
                                const blob = window.dataURLtoBlob(frameDataUrl);
                                formData.append('media_files[]', blob, `frame_${index}_${file.name}.jpeg`);
                            });
                        } catch (error) {
                            console.error('Frame extraction failed:', error);
                            showToast(`Failed to process video: ${file.name}.`, 'error');
                            aiVisionLoadingMessage.classList.add('hidden');
                            return;
                        }
                    } else {
                        formData.append('media_files[]', file);
                    }
                }

                try {
                    const response = await fetch('/api/openai_chat.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    aiVisionLoadingMessage.classList.add('hidden');

                    if (result.success && result.items) {
                        junkItems = result.items.map(item => ({
                            itemType: item.item,
                            quantity: 1, // Default quantity to 1 for AI-detected items
                            estDimensions: item.estDimensions,
                            estWeight: item.estWeight
                        }));
                        renderJunkItemsList();
                        showToast('Items detected and added to the list!', 'success');
                    } else {
                        showToast(result.message || 'AI could not detect items. Please add manually.', 'error');
                    }
                } catch (error) {
                    aiVisionLoadingMessage.classList.add('hidden');
                    console.error('AI Vision API Error:', error);
                    showToast('An error occurred during AI analysis. Please try again.', 'error');
                }
            });

            addJunkItemRowBtn.addEventListener('click', () => {
                junkItems.push({ itemType: '', quantity: 1, estDimensions: '', estWeight: '' });
                renderJunkItemsList();
            });

            function renderJunkItemsList() {
                junkItemsTbody.innerHTML = '';
                junkItemsCardView.innerHTML = ''; // Clear card view as well

                junkItems.forEach((item, index) => {
                    // --- Table Row HTML ---
                    const tableRow = document.createElement('tr');
                    tableRow.classList.add('table-row');
                    tableRow.innerHTML = `
                        <td class="table-body-cell"><input type="text" class="form-input item-type-input edit-input" value="${item.itemType}" placeholder="e.g., Sofa" data-index="${index}" required></td>
                        <td class="table-body-cell"><input type="number" class="form-input quantity-input edit-input" value="${item.quantity}" min="1" data-index="${index}" required></td>
                        <td class="table-body-cell"><input type="text" class="form-input dimensions-input edit-input" value="${item.estDimensions}" placeholder="e.g., 6x3x3 ft" data-index="${index}"></td>
                        <td class="table-body-cell"><input type="text" class="form-input weight-input edit-input" value="${item.estWeight}" placeholder="e.g., 100 lbs" data-index="${index}"></td>
                        <td class="table-body-cell text-center">
                            <button type="button" class="text-red-600 hover:text-red-800 remove-junk-item-btn" data-index="${index}"><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                    junkItemsTbody.appendChild(tableRow);

                    // --- Card View HTML ---
                    const cardDiv = document.createElement('div');
                    cardDiv.classList.add('item-card');
                    cardDiv.innerHTML = `
                        <div class="item-card-header">
                            <span class="item-card-title">Item: <input type="text" class="edit-input" value="${item.itemType}" data-field="itemType" data-index="${index}" required></span>
                            <button type="button" class="text-red-600 hover:text-red-800 remove-junk-item-btn remove-btn" data-index="${index}"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="item-card-detail"><label>Quantity:</label><input type="number" class="edit-input" value="${item.quantity}" min="1" data-field="quantity" data-index="${index}" required></div>
                        <div class="item-card-detail"><label>Est. Dimensions:</label><input type="text" class="edit-input" value="${item.estDimensions}" data-field="estDimensions" data-index="${index}"></div>
                        <div class="item-card-detail"><label>Est. Weight:</label><input type="text" class="edit-input" value="${item.estWeight}" data-field="estWeight" data-index="${index}"></div>
                    `;
                    junkItemsCardView.appendChild(cardDiv);
                });

                // Attach event listeners for dynamically created inputs for junk items (table view)
                junkItemsTbody.querySelectorAll('.edit-input').forEach(input => {
                    input.addEventListener('input', function() { // Use input event for real-time updates
                        const index = parseInt(this.dataset.index);
                        const field = this.dataset.field;
                        junkItems[index][field] = (field === 'quantity') ? parseInt(this.value) : this.value;
                    });
                });
                // Attach event listeners for dynamically created inputs for junk items (card view)
                junkItemsCardView.querySelectorAll('.edit-input').forEach(input => {
                    input.addEventListener('input', function() { // Use input event for real-time updates
                        const index = parseInt(this.dataset.index);
                        const field = this.dataset.field;
                        junkItems[index][field] = (field === 'quantity') ? parseInt(this.value) : this.value;
                    });
                });


                junkItemsTbody.addEventListener('click', function(event) {
                    if (event.target.classList.contains('remove-junk-item-btn')) {
                        const index = parseInt(event.target.dataset.index);
                        junkItems.splice(index, 1);
                        renderJunkItemsList();
                        showToast('Item removed.', 'info');
                    }
                });
                junkItemsCardView.addEventListener('click', function(event) {
                    if (event.target.classList.contains('remove-junk-item-btn')) {
                        const index = parseInt(event.target.dataset.index);
                        junkItems.splice(index, 1);
                        renderJunkItemsList();
                        showToast('Item removed.', 'info');
                    }
                });
            }

            // --- Form Submission Logic ---
          // --- Form Submission Logic ---
quoteForm.addEventListener('submit', async function(event) {
    event.preventDefault();

    const serviceType = hiddenServiceType.value;
    let isValid = true;

    // Common fields validation
    const commonFields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zip-code'];
    commonFields.forEach(id => {
        const input = document.getElementById(id);
        if (!input.value.trim()) {
            input.style.borderColor = 'red';
            isValid = false;
        } else {
            input.style.borderColor = ''; // Reset border
        }
    });
    const emailInput = document.getElementById('email');
    if (!emailInput.value.includes('@')) {
        emailInput.style.borderColor = 'red';
        isValid = false;
    } else {
        emailInput.style.borderColor = '';
    }

    // Manually create FormData to include JS arrays
    const formData = new FormData(quoteForm);
    
    // Conditional service-specific data collection
    if (serviceType === 'equipment_rental') {
        if (equipmentItems.length === 0) {
            showToast('Please add at least one equipment item.', 'error');
            isValid = false;
        }
        // Add the equipment array to the form data
        formData.append('equipment_details', JSON.stringify(equipmentItems));

    } else if (serviceType === 'junk_removal') {
        if (junkItems.length === 0) {
            showToast('Please add at least one junk item.', 'error');
            isValid = false;
        }
        const junkRemovalDate = document.getElementById('junk-removal-date').value;
        const junkRemovalTime = document.getElementById('junk-removal-time').value;
        const junkProjectDescription = document.getElementById('junk-project-description').value;

        if (!junkRemovalDate) { // Basic check for date
             showToast('Preferred date is required for Junk Removal.', 'error');
             isValid = false;
        }

        // Package junk data into a single object
        const junkData = {
            junk_items: junkItems,
            preferred_date: junkRemovalDate,
            preferred_time: junkRemovalTime,
            additional_comment: junkProjectDescription
        };
        // Add the junk data object to the form data
        formData.append('junk_details', JSON.stringify(junkData));
    }

    if (!isValid) {
        showToast('Please fix the errors before submitting.', 'warning');
        return;
    }

    showToast('Submitting your request...', 'info');
    
    try {
        const response = await fetch('/api/public/quote_submission.php', {
            method: 'POST',
            body: formData // Send the correctly built form data
        });
        const result = await response.json();

        if (result.success) {
            // Reset the form fields first
            quoteForm.reset();
            equipmentItems = [];
            junkItems = [];
            renderEquipmentList();
            renderJunkItemsList();
            equipmentTypeSelect.value = '';
            equipmentFieldsContainers.forEach(field => field.classList.add('hidden'));
            addEquipmentItemBtn.classList.add('hidden');
            document.getElementById('no-equipment-message').classList.remove('hidden');
            
            // Set the detailed success message for the modal
            successModalMessage.innerHTML = "Thank you for your submission! You will receive pricing within 60 minutes during our working hours. We've sent a confirmation to your email. If you're a new customer, an account has been created for you. You can log in to view your quote status.";

            // Show the new interactive modal
            showModal('success-modal');

        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Quote submission error:', error);
        showToast('An unexpected error occurred during submission. Please try again.', 'error');
    }
});
    });
    </script>
</body>
</html>