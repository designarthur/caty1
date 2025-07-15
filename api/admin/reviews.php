<?php
// api/admin/reviews.php - Handles admin actions for customer reviews

session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

header('Content-Type: application/json');

// Security check: Ensure user is a logged-in admin
if (!is_logged_in() || !has_role('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';
$review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);

if (!$review_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required action or review ID.']);
    exit;
}

$conn->begin_transaction();

try {
    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE reviews SET is_approved = TRUE WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Review approved successfully.']);
            } else {
                throw new Exception('Failed to approve review.');
            }
            $stmt->close();
            break;

        case 'unapprove':
            $stmt = $conn->prepare("UPDATE reviews SET is_approved = FALSE WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Review has been unapproved and is now hidden.']);
            } else {
                throw new Exception('Failed to unapprove review.');
            }
            $stmt->close();
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Review deleted successfully.']);
            } else {
                throw new Exception('Failed to delete review.');
            }
            $stmt->close();
            break;

        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>