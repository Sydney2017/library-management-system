<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error"];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get input data
    $reservation_id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($reservation_id <= 0 || $user_id <= 0) {
        $response["message"] = "Invalid reservation ID or user ID";
        echo json_encode($response);
        exit;
    }

    // Get member_id from user_id
    $memberQuery = "SELECT id FROM members WHERE user_id = ?";
    $memberStmt = $db->prepare($memberQuery);
    $memberStmt->execute([$user_id]);
    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        $response["message"] = "Member profile not found";
        echo json_encode($response);
        exit;
    }

    $member_id = $member['id'];

    // Check if reservation exists and belongs to this user
    $reservationQuery = "SELECT r.id, r.book_id, r.status, b.available_copies, b.total_copies 
                         FROM reservations r 
                         JOIN books b ON r.book_id = b.id 
                         WHERE r.id = ? AND r.member_id = ?";
    $reservationStmt = $db->prepare($reservationQuery);
    $reservationStmt->execute([$reservation_id, $member_id]);
    $reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        $response["message"] = "Reservation not found or you don't have permission to cancel it";
        echo json_encode($response);
        exit;
    }

    // Check if reservation is already cancelled
    if ($reservation['status'] == 'cancelled') {
        $response["message"] = "Reservation is already cancelled";
        echo json_encode($response);
        exit;
    }

    // Check if reservation is already fulfilled or expired
    if ($reservation['status'] == 'fulfilled') {
        $response["message"] = "Cannot cancel a fulfilled reservation";
        echo json_encode($response);
        exit;
    }

    if ($reservation['status'] == 'expired') {
        $response["message"] = "Cannot cancel an expired reservation";
        echo json_encode($response);
        exit;
    }

    // Start transaction
    $db->beginTransaction();

    try {
        // Update reservation status to cancelled
        $updateQuery = "UPDATE reservations SET status = 'cancelled' WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$reservation_id])) {
            // Increase available copies of the book, but don't exceed total copies
            $bookUpdateQuery = "UPDATE books 
                               SET available_copies = LEAST(available_copies + 1, total_copies) 
                               WHERE id = ?";
            $bookUpdateStmt = $db->prepare($bookUpdateQuery);
            
            if ($bookUpdateStmt->execute([$reservation['book_id']])) {
                $db->commit();
                $response["success"] = true;
                $response["message"] = "Reservation cancelled successfully";
            } else {
                $db->rollBack();
                $response["message"] = "Failed to update book availability";
            }
        } else {
            $db->rollBack();
            $response["message"] = "Failed to cancel reservation";
        }
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>