<?php
// api/admin/vendors.php - Admin API for Vendor Management

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and has_role()

header('Content-Type: application/json');

// Check if user is logged in and has admin role
if (!is_loggedin() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? ''; // Expected actions: 'add_vendor', 'update_vendor', 'delete_vendor'
$vendor_id = $_POST['vendor_id'] ?? null; // For update and delete actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_vendor':
            handleAddVendor($conn);
            break;
        case 'update_vendor':
            if (empty($vendor_id)) {
                echo json_encode(['success' => false, 'message' => 'Vendor ID is required for update.']);
                break;
            }
            handleUpdateVendor($conn, $vendor_id);
            break;
        case 'delete_vendor':
            if (empty($vendor_id)) {
                echo json_encode(['success' => false, 'message' => 'Vendor ID is required for delete.']);
                break;
            }
            handleDeleteVendor($conn, $vendor_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

function handleAddVendor($conn) {
    $name = trim($_POST['name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? null);
    $email = trim($_POST['email'] ?? null);
    $phoneNumber = trim($_POST['phone_number'] ?? null);
    $address = trim($_POST['address'] ?? null);
    $city = trim($_POST['city'] ?? null);
    $state = trim($_POST['state'] ?? null);
    $zipCode = trim($_POST['zip_code'] ?? null);
    $isActive = isset($_POST['is_active']) ? true : false; // Checkbox value 'on' or 'off'

    // Server-side validation
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Vendor Name and Email are required.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }
    if ($phoneNumber && !preg_match('/^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/', $phoneNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
        return;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM vendors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A vendor with this email already exists.']);
        $stmt->close();
        return;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO vendors (name, contact_person, email, phone_number, address, city, state, zip_code, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssi", $name, $contactPerson, $email, $phoneNumber, $address, $city, $state, $zipCode, $isActive);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vendor added successfully!']);
    } else {
        error_log("Failed to add vendor: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to add vendor. Please try again.']);
    }
    $stmt->close();
}

function handleUpdateVendor($conn, $vendor_id) {
    $name = trim($_POST['name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? null);
    $email = trim($_POST['email'] ?? null);
    $phoneNumber = trim($_POST['phone_number'] ?? null);
    $address = trim($_POST['address'] ?? null);
    $city = trim($_POST['city'] ?? null);
    $state = trim($_POST['state'] ?? null);
    $zipCode = trim($_POST['zip_code'] ?? null);
    $isActive = isset($_POST['is_active']) ? true : false;

    // Server-side validation
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Vendor Name and Email are required.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }
    if ($phoneNumber && !preg_match('/^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/', $phoneNumber)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
        return;
    }

    // Check if new email already exists for another vendor (excluding current vendor being updated)
    $stmt = $conn->prepare("SELECT id FROM vendors WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already in use by another vendor.']);
        $stmt->close();
        return;
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE vendors SET name = ?, contact_person = ?, email = ?, phone_number = ?, address = ?, city = ?, state = ?, zip_code = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssssssii", $name, $contactPerson, $email, $phoneNumber, $address, $city, $state, $zipCode, $isActive, $vendor_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Vendor updated successfully!']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made or vendor not found.']);
        }
    } else {
        error_log("Failed to update vendor ID $vendor_id: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update vendor. Please try again.']);
    }
    $stmt->close();
}

function handleDeleteVendor($conn, $vendor_id) {
    $conn->begin_transaction();
    try {
        // First, set any bookings assigned to this vendor to NULL or 'unassigned' status
        $stmt_update_bookings = $conn->prepare("UPDATE bookings SET vendor_id = NULL WHERE vendor_id = ?");
        $stmt_update_bookings->bind_param("i", $vendor_id);
        $stmt_update_bookings->execute();
        $stmt_update_bookings->close();
        // This is important to avoid foreign key constraint errors or orphaned bookings.

        $stmt = $conn->prepare("DELETE FROM vendors WHERE id = ?");
        $stmt->bind_param("i", $vendor_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Vendor deleted successfully!']);
            } else {
                throw new Exception("Vendor not found or already deleted.");
            }
        } else {
            throw new Exception("Failed to delete vendor from DB: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete vendor transaction failed for ID $vendor_id: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete vendor: ' . $e->getMessage()]);
    }
}
?>