<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$response = ["success" => false, "message" => "Initial error"];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response["message"] = "Only POST method allowed";
        echo json_encode($response);
        exit();
    }

    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        $response["message"] = "Username and password required";
        echo json_encode($response);
        exit();
    }

    $username = sanitize($input['username']);
    $password = sanitize($input['password']);

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT u.id, u.username, u.password, u.email, u.role, 
                     m.first_name, m.last_name, m.membership_id
              FROM users u 
              LEFT JOIN members m ON u.id = m.user_id 
              WHERE u.username = :username OR u.email = :username";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            $response["success"] = true;
            $response["message"] = "Login successful";
            $response["token"] = base64_encode($user['id'] . ':' . $user['username']);
            $response["role"] = $user['role'];
            $response["user"] = [
                "id" => (int)$user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "role" => $user['role'],
                "first_name" => $user['first_name'],
                "last_name" => $user['last_name'],
                "membership_id" => $user['membership_id']
            ];
        } else {
            $response["message"] = "Invalid password";
        }
    } else {
        $response["message"] = "User not found";
    }

} catch (Exception $e) {
    $response["message"] = "Error: " . $e->getMessage();
}

echo json_encode($response);
?>