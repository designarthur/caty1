<?php
// admin/pages/reviews.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';

if (!is_logged_in() || !has_role('admin')) {
    echo '<div class="text-red-500 text-center p-8">Unauthorized access.</div>';
    exit;
}

$reviews = [];

// Fetch all reviews with user and booking information
$stmt = $conn->prepare("
    SELECT 
        r.id, r.rating, r.review_text, r.is_approved, r.created_at,
        u.first_name, u.last_name,
        b.booking_number
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN bookings b ON r.booking_id = b.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt->close();
$conn->close();

function renderStars($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        $output .= '<i class="fas fa-star ' . ($i <= $rating ? 'text-yellow-400' : 'text-gray-300') . '"></i>';
    }
    return $output;
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Review Management</h1>

<div class="bg-white p-6 rounded-lg shadow-md border border-blue-200">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center"><i class="fas fa-star-half-alt mr-2 text-blue-600"></i>All Customer Reviews</h2>

    <?php if (empty($reviews)): ?>
        <p class="text-gray-600 text-center p-4">No reviews have been submitted yet.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Booking #</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Rating</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Review</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['booking_number']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo renderStars($review['rating']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-sm truncate"><?php echo htmlspecialchars($review['review_text'] ?: 'No comment'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (new DateTime($review['created_at']))->format('Y-m-d'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($review['is_approved']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if (!$review['is_approved']): ?>
                                    <button class="text-green-600 hover:text-green-900 mr-3 approve-review-btn" data-id="<?php echo $review['id']; ?>">Approve</button>
                                <?php else: ?>
                                     <button class="text-yellow-600 hover:text-yellow-900 mr-3 unapprove-review-btn" data-id="<?php echo $review['id']; ?>">Unapprove</button>
                                <?php endif; ?>
                                <button class="text-red-600 hover:text-red-900 delete-review-btn" data-id="<?php echo $review['id']; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('click', function(event) {
        // Handle Approve/Unapprove
        const toggleBtn = event.target.closest('.approve-review-btn, .unapprove-review-btn');
        if (toggleBtn) {
            const reviewId = toggleBtn.dataset.id;
            const action = toggleBtn.classList.contains('approve-review-btn') ? 'approve' : 'unapprove';
            
            showConfirmationModal(
                `Confirm ${action}`,
                `Are you sure you want to ${action} this review?`,
                async (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('review_id', reviewId);
                        
                        try {
                            const response = await fetch('/api/admin/reviews.php', { method: 'POST', body: formData });
                            const result = await response.json();
                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('reviews');
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            showToast('An error occurred.', 'error');
                        }
                    }
                },
                `Confirm`,
                action === 'approve' ? 'bg-green-600' : 'bg-yellow-600'
            );
        }

        // Handle Delete
        const deleteBtn = event.target.closest('.delete-review-btn');
        if (deleteBtn) {
            const reviewId = deleteBtn.dataset.id;
            showConfirmationModal(
                'Delete Review',
                'Are you sure you want to permanently delete this review?',
                async (confirmed) => {
                    if (confirmed) {
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('review_id', reviewId);

                        try {
                            const response = await fetch('/api/admin/reviews.php', { method: 'POST', body: formData });
                            const result = await response.json();
                            if (result.success) {
                                showToast(result.message, 'success');
                                window.loadAdminSection('reviews');
                            } else {
                                showToast(result.message, 'error');
                            }
                        } catch (error) {
                            showToast('An error occurred.', 'error');
                        }
                    }
                },
                'Delete',
                'bg-red-600'
            );
        }
    });
</script>