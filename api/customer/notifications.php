<?php
// api/customer/notifications.php - Handles customer notification actions

// Start session and include necessary files
session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php'; 

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? ''; // Handle GET for count, POST for others

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = $_POST['id'] ?? null; // Can be a single ID or 'all'

    switch ($action) {
        case 'mark_read':
            handleMarkRead($conn, $user_id, $notification_id);
            break;
        case 'delete':
            handleDeleteNotification($conn, $user_id, $notification_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action.']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_unread_count') {
    handleGetUnreadCount($conn, $user_id);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();

// --- Handler Functions ---

function handleGetUnreadCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    echo json_encode(['success' => true, 'unread_count' => $result['unread_count'] ?? 0]);
}

function handleMarkRead($conn, $user_id, $notification_id) {
    if ($notification_id === null) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required.']);
        return;
    }

    if ($notification_id === 'all') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification(s) marked as read.']);
    } else {
        error_log("Failed to mark notification(s) as read for user ID $user_id. Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to mark as read.']);
    }
    $stmt->close();
}

function handleDeleteNotification($conn, $user_id, $notification_id) {
     if ($notification_id === null) {
        echo json_encode(['success' => false, 'message' => 'Notification ID is required.']);
        return;
    }

    if ($notification_id === 'all') {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Notification(s) deleted.']);
    } else {
         error_log("Failed to delete notification(s) for user ID $user_id. Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification(s).']);
    }
    $stmt->close();
}
