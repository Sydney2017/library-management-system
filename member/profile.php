<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member')) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get member details - FIXED QUERY
$member_query = "SELECT m.*, u.username, u.email, u.created_at as join_date FROM members m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE u.id = :user_id";
$member_stmt = $db->prepare($member_query);
$member_stmt->bindParam(':user_id', $_SESSION['user_id']);
$member_stmt->execute();
$member = $member_stmt->fetch(PDO::FETCH_ASSOC);

// Update profile
if (isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $dob = sanitize($_POST['dob']);
    
    $update_query = "UPDATE members SET first_name = :first_name, last_name = :last_name, 
                    phone = :phone, address = :address, date_of_birth = :dob 
                    WHERE user_id = :user_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':first_name', $first_name);
    $update_stmt->bindParam(':last_name', $last_name);
    $update_stmt->bindParam(':phone', $phone);
    $update_stmt->bindParam(':address', $address);
    $update_stmt->bindParam(':dob', $dob);
    $update_stmt->bindParam(':user_id', $_SESSION['user_id']);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
        redirect('profile.php');
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/member_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Profile</h1>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo $member['first_name']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo $member['last_name']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo $member['email']; ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo $member['username']; ?>" disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo $member['phone']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="dob" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="dob" name="dob" 
                                               value="<?php echo $member['date_of_birth']; ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo $member['address']; ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Membership Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Membership ID</label>
                                    <input type="text" class="form-control" value="<?php echo $member['membership_id']; ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $member['join_date'] ? date('F j, Y', strtotime($member['join_date'])) : 'N/A'; ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Status</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ucfirst($member['status']); ?>" disabled>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i> Membership Benefits</h6>
                                    <ul class="mb-0">
                                        <li>Borrow up to 5 books at a time</li>
                                        <li>7-day loan period per book</li>
                                        <li>Online book reservations</li>
                                        <li>Access to digital resources</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title">Account Security</h5>
                            </div>
                            <div class="card-body">
                                <p>For security reasons, please contact library administration to change your username or email address.</p>
                                <a href="../logout.php" class="btn btn-outline-danger">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>