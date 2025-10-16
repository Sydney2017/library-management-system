<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error"];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user_id from query parameters
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($user_id > 0) {
        // Get member data by user_id
        $query = "SELECT m.id, m.membership_id, m.first_name, m.last_name, m.phone, m.address, m.date_of_birth, m.status,
                         u.username, u.email, u.role
                  FROM members m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE u.id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            $response["success"] = true;
            $response["message"] = "Profile loaded successfully";
            $response["profile"] = [
                "id" => $member['id'], // ADD THIS - member id
                "membership_id" => $member['membership_id'],
                "first_name" => $member['first_name'],
                "last_name" => $member['last_name'],
                "full_name" => $member['first_name'] . ' ' . $member['last_name'],
                "phone" => $member['phone'],
                "address" => $member['address'],
                "date_of_birth" => $member['date_of_birth'],
                "status" => $member['status'],
                "username" => $member['username'],
                "email" => $member['email'],
                "role" => $member['role']
            ];
        } else {
            $response["message"] = "Member profile not found";
        }
    } else {
        $response["message"] = "Invalid user ID";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>