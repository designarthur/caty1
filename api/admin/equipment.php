<?php
// api/admin/equipment.php - Admin API for Equipment Management

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and has_role()

header('Content-Type: application/json');

// Check if user is logged in and has admin role
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_POST['action'] ?? ''; // Expected actions: 'add_equipment', 'update_equipment', 'delete_equipment'
$equipment_id = $_POST['equipment_id'] ?? null; // For update and delete actions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_equipment':
            handleAddEquipment($conn);
            break;
        case 'update_equipment':
            if (empty($equipment_id)) {
                echo json_encode(['success' => false, 'message' => 'Equipment ID is required for update.']);
                break;
            }
            handleUpdateEquipment($conn, $equipment_id);
            break;
        case 'delete_equipment':
            if (empty($equipment_id)) {
                echo json_encode(['success' => false, 'message' => 'Equipment ID is required for delete.']);
                break;
            }
            handleDeleteEquipment($conn, $equipment_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();

function handleAddEquipment($conn) {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $sizeCapacity = trim($_POST['size_capacity'] ?? null);
    $description = trim($_POST['description'] ?? null);
    $dailyRate = filter_var($_POST['daily_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
    $imageUrl = trim($_POST['image_url'] ?? null);
    $isActive = isset($_POST['is_active']) ? true : false; // Checkbox value 'on' or 'off'

    // Server-side validation
    if (empty($name) || empty($type) || $dailyRate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Name, Type, and a positive Daily Rate are required.']);
        return;
    }
    $allowedTypes = ['Dumpster', 'Temporary Toilet', 'Storage Container', 'Handwash Station'];
    if (!in_array($type, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid equipment type.']);
        return;
    }
    if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image URL format.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO equipment (name, type, size_capacity, description, daily_rate, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdsi", $name, $type, $sizeCapacity, $description, $dailyRate, $imageUrl, $isActive);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Equipment added successfully!']);
    } else {
        error_log("Failed to add equipment: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to add equipment. Please try again.']);
    }
    $stmt->close();
}

function handleUpdateEquipment($conn, $equipment_id) {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $sizeCapacity = trim($_POST['size_capacity'] ?? null);
    $description = trim($_POST['description'] ?? null);
    $dailyRate = filter_var($_POST['daily_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
    $imageUrl = trim($_POST['image_url'] ?? null);
    $isActive = isset($_POST['is_active']) ? true : false;

    // Server-side validation
    if (empty($name) || empty($type) || $dailyRate <= 0) {
        echo json_encode(['success' => false, 'message' => 'Name, Type, and a positive Daily Rate are required.']);
        return;
    }
    $allowedTypes = ['Dumpster', 'Temporary Toilet', 'Storage Container', 'Handwash Station'];
    if (!in_array($type, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid equipment type.']);
        return;
    }
    if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image URL format.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE equipment SET name = ?, type = ?, size_capacity = ?, description = ?, daily_rate = ?, image_url = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssdsii", $name, $type, $sizeCapacity, $description, $dailyRate, $imageUrl, $isActive, $equipment_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Equipment updated successfully!']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made or equipment not found.']);
        }
    } else {
        error_log("Failed to update equipment ID $equipment_id: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update equipment. Please try again.']);
    }
    $stmt->close();
}

function handleDeleteEquipment($conn, $equipment_id) {
    $conn->begin_transaction();
    try {
        // Optional: Check for existing bookings/quotes tied to this equipment before deleting.
        // If there are, you might want to prevent deletion or offer to deactivate instead.
        // For simplicity, we proceed with deletion. Ensure your DB has ON DELETE CASCADE if needed for related tables.

        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->bind_param("i", $equipment_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully!']);
            } else {
                throw new Exception("Equipment not found or already deleted.");
            }
        } else {
            throw new Exception("Failed to delete equipment from DB: " . $stmt->error);
        }
        $stmt->close();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete equipment transaction failed for ID $equipment_id: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete equipment: ' . $e->getMessage()]);
    }
}
?>