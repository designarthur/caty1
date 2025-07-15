<?php
// api/admin/settings.php - Admin API for System Settings

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

$action = $_POST['action'] ?? ''; // Expected action: 'update_settings'

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_settings') {
    $conn->begin_transaction();
    try {
        $settings_to_update = [
            'company_name' => trim($_POST['company_name'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'global_tax_rate' => filter_input(INPUT_POST, 'global_tax_rate', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]),
            'global_service_fee' => filter_input(INPUT_POST, 'global_service_fee', FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]])
        ];

        foreach ($settings_to_update as $key => $value) {
            // Basic validation per setting key
            if ($key === 'company_name' && empty($value)) {
                throw new Exception('Company Name cannot be empty.');
            }
            if ($key === 'admin_email') {
                if (empty($value)) {
                    throw new Exception('Admin Email Recipient cannot be empty.');
                }
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid Admin Email Recipient format.');
                }
            }
             if (($key === 'global_tax_rate' || $key === 'global_service_fee') && $value === false) {
                throw new Exception(ucwords(str_replace('_', ' ', $key)) . ' must be a valid number.');
            }
            
            // Add more specific validations for other settings here

            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update setting '{$key}': " . $stmt->error);
            }
            $stmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Settings update transaction failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update settings: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method or action.']);
}

$conn->close();
?>