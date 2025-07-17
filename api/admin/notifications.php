<?php
// api/admin/notifications.php - Handles admin notification actions

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and has_role()
require_once __DIR__ . '/../../includes/functions.php'; // For validate_csrf_token()

header('Content-Type: application/json');

// Check if user is logged in and has admin role
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$admin_user_id = $_SESSION['user_id'];
$notification_id = $_POST['id'] ?? null; // Can be a single ID or 'all'
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle GET or POST actions

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF token validation
        validate_csrf_token();

        switch ($action) {
            case 'mark_read':
                handleMarkRead($conn, $notification_id);
                break;
            case 'delete':
                handleDeleteNotification($conn, $notification_id);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
                break;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_unread_count') {
        handleGetUnreadCount($conn, $admin_user_id);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    }
} catch (Exception $e) {
    http_response_code(400); // Bad Request for most client-side errors
    error_log("Admin Notification API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

function handleGetUnreadCount($conn, $admin_user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE is_read = FALSE");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode(['success' => true, 'unread_count' => $result['unread_count'] ?? 0]);
}

function handleMarkRead($conn, $notification_id) {
    if ($notification_id === null) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required.']);
        return;
    }

    if ($notification_id === 'all') {
        // Mark all notifications as read (for admin view)
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE is_read = FALSE");
    } else {
        // Mark a specific notification as read
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
        $stmt->bind_param("i", $notification_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification(s) marked as read.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No new notifications to mark as read or notification already read.']);
        }
    } else {
        error_log("Failed to mark notification(s) as read for admin, ID: $notification_id. Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification(s) as read.']);
    }
    $stmt->close();
}

function handleDeleteNotification($conn, $notification_id) {
    if ($notification_id === null) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required.']);
        return;
    }

    if ($notification_id === 'all') {
        // Delete all notifications
        $stmt = $conn->prepare("DELETE FROM notifications");
    } else {
        // Delete a specific notification
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $notification_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification(s) deleted.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Notification not found or already deleted.']);
        }
    } else {
        error_log("Failed to delete notification(s) for admin, ID: $notification_id. Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification(s).']);
    }
    $stmt->close();
}
?>