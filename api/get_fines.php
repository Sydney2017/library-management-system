<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error", "fines" => []];

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
            
            // Query to get fines with book details for this specific member
            $query = "SELECT 
                        f.id,
                        f.loan_id,
                        b.title as book_title,
                        f.amount,
                        f.reason,
                        bl.due_date,
                        f.created_at as issued_date,
                        f.paid_date,
                        f.status
                      FROM fines f
                      JOIN book_loans bl ON f.loan_id = bl.id
                      JOIN books b ON bl.book_id = b.id
                      WHERE f.member_id = ?
                      ORDER BY f.created_at DESC, f.status";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$member_id]);
            $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($fines) {
                $response["success"] = true;
                $response["message"] = "Fines loaded successfully";
                $response["fines"] = $fines;
            } else {
                $response["success"] = true;
                $response["message"] = "No fines found for this user";
                $response["fines"] = [];
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