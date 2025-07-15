<?php
// api/customer/notifications.php - Handles customer notification actions

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; // For is_logged_in() and $_SESSION['user_id']

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['id'] ?? null; // Can be a single ID or 'all'
$action = $_POST['action'] ?? ''; // Expected actions: 'mark_read', 'delete'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'mark_read':
            handleMarkRead($conn, $user_id, $notification_id);
            break;
        case 'delete':
            handleDeleteNotification($conn, $user_id, $notification_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close(); // Close DB connection at the end of script execution

function handleMarkRead($conn, $user_id, $notification_id) {
    if ($notification_id === null) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required.']);
        return;
    }

    if ($notification_id === 'all') {
        // Mark all unread notifications for the user as read
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $user_id);
    } else {
        // Mark a specific notification as read
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification(s) marked as read.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No new notifications to mark as read or notification already read.']);
        }
    } else {
        error_log("Failed to mark notification(s) as read for user ID $user_id, ID: $notification_id. Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification(s) as read.']);
    }
    $stmt->close();
}

function handleDeleteNotification($conn, $user_id, $notification_id) {
    if ($notification_id === null) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required.']);
        return;
    }

    if ($notification_id === 'all') {
        // Delete all notifications for the user
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        // Delete a specific notification
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification(s) deleted.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Notification not found or already deleted.']);
        }
    } else {
        error_log("Failed to delete notification(s) for user ID $user_id, ID: $notification_id. Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification(s).']);
    }
    $stmt->close();
}
?>