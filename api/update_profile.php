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

    // Get JSON input
    $input = json_decode(file_get_contents("php://input"), true);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Validate required fields
        if (isset($input['user_id']) && isset($input['first_name']) && isset($input['last_name']) && isset($input['phone'])) {
            
            $user_id = (int)$input['user_id'];
            $first_name = trim($input['first_name']);
            $last_name = trim($input['last_name']);
            $phone = trim($input['phone']);
            $address = isset($input['address']) ? trim($input['address']) : null;
            $date_of_birth = isset($input['date_of_birth']) ? trim($input['date_of_birth']) : null;

            // Basic validation
            if (empty($first_name) || empty($last_name) || empty($phone)) {
                $response["message"] = "First name, last name, and phone are required";
                echo json_encode($response);
                exit;
            }

            if ($user_id <= 0) {
                $response["message"] = "Invalid user ID";
                echo json_encode($response);
                exit;
            }

            // Check if member exists for this user
            $checkQuery = "SELECT id FROM members WHERE user_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$user_id]);
            $memberExists = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($memberExists) {
                // Update existing member - REMOVED updated_at since it doesn't exist in your table
                $updateQuery = "UPDATE members 
                               SET first_name = ?, last_name = ?, phone = ?, address = ?, date_of_birth = ?
                               WHERE user_id = ?";
                
                $updateStmt = $db->prepare($updateQuery);
                $success = $updateStmt->execute([
                    $first_name, 
                    $last_name, 
                    $phone, 
                    $address, 
                    $date_of_birth, 
                    $user_id
                ]);

                if ($success) {
                    $response["success"] = true;
                    $response["message"] = "Profile updated successfully";
                    
                    // Return updated profile data
                    $profileQuery = "SELECT m.membership_id, m.first_name, m.last_name, m.phone, m.address, m.date_of_birth, m.status,
                                            u.username, u.email, u.role
                                     FROM members m 
                                     JOIN users u ON m.user_id = u.id 
                                     WHERE u.id = ?";
                    
                    $profileStmt = $db->prepare($profileQuery);
                    $profileStmt->execute([$user_id]);
                    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

                    if ($profile) {
                        $response["profile"] = [
                            "membership_id" => $profile['membership_id'],
                            "first_name" => $profile['first_name'],
                            "last_name" => $profile['last_name'],
                            "full_name" => $profile['first_name'] . ' ' . $profile['last_name'],
                            "phone" => $profile['phone'],
                            "address" => $profile['address'],
                            "date_of_birth" => $profile['date_of_birth'],
                            "status" => $profile['status'],
                            "username" => $profile['username'],
                            "email" => $profile['email'],
                            "role" => $profile['role']
                        ];
                    }
                } else {
                    $response["message"] = "Failed to update profile";
                }
            } else {
                $response["message"] = "Member profile not found for this user";
            }

        } else {
            $response["message"] = "Missing required fields";
        }
    } else {
        $response["message"] = "Invalid request method";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>