<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error"];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get book_id from GET parameters (simple and works with Retrofit)
    $book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

    if ($book_id > 0) {
        // Check if book exists and is available
        $checkQuery = "SELECT id, title, status FROM books WHERE id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$book_id]);
        $book = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($book) {
            if ($book['status'] === 'available') {
                // Create reservation record (using member_id = 1 for testing)
                $reservationQuery = "INSERT INTO reservations (book_id, member_id, reservation_date, expiry_date, status) 
                                   VALUES (?, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'active')";
                $reserveStmt = $db->prepare($reservationQuery);
                
                if ($reserveStmt->execute([$book_id])) {
                    $reservation_id = $db->lastInsertId();
                    
                    // Update book status
                    $updateQuery = "UPDATE books SET status = 'reserved' WHERE id = ?";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([$book_id]);
                    
                    $response["success"] = true;
                    $response["message"] = "Book '" . $book['title'] . "' reserved successfully";
                    $response["reservationId"] = (int)$reservation_id;
                    $response["expiryDate"] = date('Y-m-d', strtotime('+3 days'));
                } else {
                    $response["message"] = "Failed to create reservation";
                }
            } else {
                $response["message"] = "Book is not available for reservation";
            }
        } else {
            $response["message"] = "Book not found with ID: " . $book_id;
        }
    } else {
        $response["message"] = "Invalid book ID. Please provide a valid book_id";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>