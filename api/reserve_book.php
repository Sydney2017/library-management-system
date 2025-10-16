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
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($book_id <= 0 || $user_id <= 0) {
        $response["message"] = "Invalid book ID or user ID";
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

    // Check if book exists and is available
    $bookQuery = "SELECT id, available_copies, total_copies FROM books WHERE id = ?";
    $bookStmt = $db->prepare($bookQuery);
    $bookStmt->execute([$book_id]);
    $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $response["message"] = "Book not found";
        echo json_encode($response);
        exit;
    }

    if ($book['available_copies'] <= 0) {
        $response["message"] = "No copies available for reservation";
        echo json_encode($response);
        exit;
    }

    // Check if user already has an active reservation for this book
    $existingReservationQuery = "SELECT id FROM reservations WHERE book_id = ? AND member_id = ? AND status = 'active'";
    $existingStmt = $db->prepare($existingReservationQuery);
    $existingStmt->execute([$book_id, $member_id]);
    $existingReservation = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingReservation) {
        $response["message"] = "You already have an active reservation for this book";
        echo json_encode($response);
        exit;
    }

    // Calculate reservation dates (reservation lasts for 7 days)
    $reservation_date = date('Y-m-d');
    $expiry_date = date('Y-m-d', strtotime('+7 days'));

    // Start transaction to ensure both operations succeed or fail together
    $db->beginTransaction();

    try {
        // Create reservation
        $insertQuery = "INSERT INTO reservations (book_id, member_id, reservation_date, expiry_date, status) 
                        VALUES (?, ?, ?, ?, 'active')";
        $insertStmt = $db->prepare($insertQuery);
        
        if ($insertStmt->execute([$book_id, $member_id, $reservation_date, $expiry_date])) {
            $reservation_id = $db->lastInsertId();
            
            // DEDUCT 1 from available copies - THIS WAS MISSING
            $updateCopiesQuery = "UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0";
            $updateStmt = $db->prepare($updateCopiesQuery);
            
            if ($updateStmt->execute([$book_id])) {
                if ($updateStmt->rowCount() > 0) {
                    $db->commit();
                    $response["success"] = true;
                    $response["message"] = "Book reserved successfully";
                    $response["reservation_id"] = $reservation_id;
                } else {
                    $db->rollBack();
                    $response["message"] = "Failed to update book availability";
                }
            } else {
                $db->rollBack();
                $response["message"] = "Failed to update book copies";
            }
        } else {
            $db->rollBack();
            $response["message"] = "Failed to create reservation";
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