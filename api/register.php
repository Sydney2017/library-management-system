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
        $first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
        $last_name = isset($input['last_name']) ? trim($input['last_name']) : '';
        $username = isset($input['username']) ? trim($input['username']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';

        // Validate input
        if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
            $response["message"] = "All fields are required";
            echo json_encode($response);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response["message"] = "Invalid email format";
            echo json_encode($response);
            exit;
        }

        if (strlen($password) < 6) {
            $response["message"] = "Password must be at least 6 characters long";
            echo json_encode($response);
            exit;
        }

        // Check if username already exists
        $checkUsernameQuery = "SELECT id FROM users WHERE username = ?";
        $checkUsernameStmt = $db->prepare($checkUsernameQuery);
        $checkUsernameStmt->execute([$username]);
        
        if ($checkUsernameStmt->fetch()) {
            $response["message"] = "Username already exists";
            echo json_encode($response);
            exit;
        }

        // Check if email already exists
        $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
        $checkEmailStmt = $db->prepare($checkEmailQuery);
        $checkEmailStmt->execute([$email]);
        
        if ($checkEmailStmt->fetch()) {
            $response["message"] = "Email already exists";
            echo json_encode($response);
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start transaction
        $db->beginTransaction();

        try {
            // Insert into users table
            $insertUserQuery = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'member')";
            $insertUserStmt = $db->prepare($insertUserQuery);
            
            if ($insertUserStmt->execute([$username, $email, $hashed_password])) {
                $user_id = $db->lastInsertId();
                
                // Generate membership ID
                $membership_id = "MEM" . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                
                // Insert into members table
                $insertMemberQuery = "INSERT INTO members (user_id, membership_id, first_name, last_name, status) VALUES (?, ?, ?, ?, 'active')";
                $insertMemberStmt = $db->prepare($insertMemberQuery);
                
                if ($insertMemberStmt->execute([$user_id, $membership_id, $first_name, $last_name])) {
                    $db->commit();
                    $response["success"] = true;
                    $response["message"] = "Registration successful! Your membership ID is: " . $membership_id;
                    $response["user_id"] = $user_id;
                } else {
                    $db->rollBack();
                    $response["message"] = "Failed to create member profile";
                }
            } else {
                $db->rollBack();
                $response["message"] = "Failed to create user account";
            }

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

    } else {
        $response["message"] = "Invalid request method";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>