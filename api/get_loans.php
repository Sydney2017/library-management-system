<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error", "loans" => []];

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
            
            // Query to get loans with book details for this specific member
            $query = "SELECT 
                        bl.id,
                        bl.book_id,
                        b.title as book_title,
                        b.author,
                        bl.checkout_date,
                        bl.due_date,
                        bl.return_date,
                        bl.status
                      FROM book_loans bl
                      JOIN books b ON bl.book_id = b.id
                      WHERE bl.member_id = ?
                      ORDER BY bl.checkout_date DESC, bl.status";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$member_id]);
            $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($loans) {
                $response["success"] = true;
                $response["message"] = "Loans loaded successfully";
                $response["loans"] = $loans;
            } else {
                $response["success"] = true;
                $response["message"] = "No loans found for this user";
                $response["loans"] = [];
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