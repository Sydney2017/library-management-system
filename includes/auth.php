<?php
require_once 'database.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = sanitize($_POST['username']);
        $password = sanitize($_POST['password']);
        
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                if ($user['role'] === 'admin' || $user['role'] === 'librarian') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('member/dashboard.php');
                }
            }
        }
        $_SESSION['error'] = "Invalid username or password";
    }
    
    if (isset($_POST['register'])) {
        $username = sanitize($_POST['username']);
        $password = sanitize($_POST['password']);
        $email = sanitize($_POST['email']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $dob = sanitize($_POST['dob']);
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username exists
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $_SESSION['error'] = "Username or email already exists";
        } else {
            try {
                $db->beginTransaction();
                
                // Insert user
                $user_query = "INSERT INTO users (username, password, email, role) VALUES (:username, :password, :email, 'member')";
                $user_stmt = $db->prepare($user_query);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_stmt->bindParam(':username', $username);
                $user_stmt->bindParam(':password', $hashed_password);
                $user_stmt->bindParam(':email', $email);
                $user_stmt->execute();
                
                $user_id = $db->lastInsertId();
                $membership_id = 'MEM' . str_pad($user_id, 6, '0', STR_PAD_LEFT);
                
                // Insert member
                $member_query = "INSERT INTO members (user_id, membership_id, first_name, last_name, phone, address, date_of_birth) 
                                VALUES (:user_id, :membership_id, :first_name, :last_name, :phone, :address, :dob)";
                $member_stmt = $db->prepare($member_query);
                $member_stmt->bindParam(':user_id', $user_id);
                $member_stmt->bindParam(':membership_id', $membership_id);
                $member_stmt->bindParam(':first_name', $first_name);
                $member_stmt->bindParam(':last_name', $last_name);
                $member_stmt->bindParam(':phone', $phone);
                $member_stmt->bindParam(':address', $address);
                $member_stmt->bindParam(':dob', $dob);
                $member_stmt->execute();
                
                $db->commit();
                $_SESSION['success'] = "Registration successful! Please login.";
                redirect('login.php');
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['error'] = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>