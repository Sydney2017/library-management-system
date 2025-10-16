<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error", "reservations" => []];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user_id from query parameters
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($user_id > 0) {
        // First, get the member_id for this user
        $memberQuery = "SELECT id FROM members WHERE user_id = ?";
        $memberStmt = $db->prepare($memberQuery);
        $memberStmt->execute([$user_id]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            $member_id = $member['id'];
            
            // Query to get reservations with book details for this specific member
            $query = "SELECT 
                        r.id,
                        r.book_id,
                        b.title as book_title,
                        b.author,
                        b.isbn,
                        r.reservation_date,
                        r.expiry_date,
                        r.status
                      FROM reservations r
                      JOIN books b ON r.book_id = b.id
                      WHERE r.member_id = ?
                      ORDER BY r.reservation_date DESC, r.status";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$member_id]);
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($reservations) {
                $response["success"] = true;
                $response["message"] = "Reservations loaded successfully";
                $response["reservations"] = $reservations;
            } else {
                $response["success"] = true;
                $response["message"] = "No reservations found for this user";
                $response["reservations"] = [];
            }
        } else {
            $response["message"] = "Member profile not found for this user";
        }
    } else {
        $response["message"] = "Invalid user ID";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>