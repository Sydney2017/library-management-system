<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member')) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get member details - FIXED: Include created_at field
$member_query = "SELECT m.*, u.created_at as join_date FROM members m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE u.id = :user_id";
$member_stmt = $db->prepare($member_query);
$member_stmt->bindParam(':user_id', $_SESSION['user_id']);
$member_stmt->execute();
$member = $member_stmt->fetch(PDO::FETCH_ASSOC);

// Get current loans
$loans_query = "SELECT b.title, b.author, bl.checkout_date, bl.due_date, bl.status 
                FROM book_loans bl 
                JOIN books b ON bl.book_id = b.id 
                WHERE bl.member_id = :member_id AND bl.status = 'active'";
$loans_stmt = $db->prepare($loans_query);
$loans_stmt->bindParam(':member_id', $member['id']);
$loans_stmt->execute();
$current_loans = $loans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get reservations
$reservations_query = "SELECT b.title, b.author, r.reservation_date, r.expiry_date, r.status 
                       FROM reservations r 
                       JOIN books b ON r.book_id = b.id 
                       WHERE r.member_id = :member_id AND r.status = 'active'";
$reservations_stmt = $db->prepare($reservations_query);
$reservations_stmt->bindParam(':member_id', $member['id']);
$reservations_stmt->execute();
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending fines
$fines_query = "SELECT amount, reason, created_at 
                FROM fines 
                WHERE member_id = :member_id AND status = 'pending'";
$fines_stmt = $db->prepare($fines_query);
$fines_stmt->bindParam(':member_id', $member['id']);
$fines_stmt->execute();
$pending_fines = $fines_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
    main.col-md-9 {
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
    
    .sidebar {
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
    
    .status-badge {
        font-size: 0.8em;
        padding: 0.4em 0.6em;
    }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/member_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Member Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="badge bg-primary">Membership ID: <?php echo $member['membership_id']; ?></span>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info">
                    <h4>Welcome, <?php echo $member['first_name'] . ' ' . $member['last_name']; ?>!</h4>
                    <p class="mb-0">Here you can manage your book loans, reservations, and account details.</p>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Current Loans</h5>
                                <h2><?php echo count($current_loans); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Active Reservations</h5>
                                <h2><?php echo count($reservations); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Pending Fines</h5>
                                <h2>R<?php echo array_sum(array_column($pending_fines, 'amount')); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Member Since</h5>
                                <h6 class="mt-4"><?php echo $member['join_date'] ? date('d F Y', strtotime($member['join_date'])) : 'N/A'; ?></h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Loans -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Current Book Loans</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($current_loans) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Checkout Date</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($current_loans as $loan): ?>
                                            <tr>
                                                <td><?php echo $loan['title']; ?></td>
                                                <td><?php echo $loan['author']; ?></td>
                                                <td><?php echo $loan['checkout_date']; ?></td>
                                                <td>
                                                    <?php 
                                                    echo $loan['due_date']; 
                                                    if (strtotime($loan['due_date']) < time()) {
                                                        echo ' <span class="badge bg-danger">Overdue</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><span class="badge bg-success"><?php echo $loan['status']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">You don't have any active book loans.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Active Reservations -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Active Reservations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($reservations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Reservation Date</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservations as $reservation): ?>
                                            <tr>
                                                <td><?php echo $reservation['title']; ?></td>
                                                <td><?php echo $reservation['author']; ?></td>
                                                <td><?php echo $reservation['reservation_date']; ?></td>
                                                <td><?php echo $reservation['expiry_date']; ?></td>
                                                <td><span class="badge bg-info"><?php echo $reservation['status']; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">You don't have any active reservations.</p>
                        <?php endif; ?>
                    </div>
                </div>

                
                <!-- Pending Fines -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Pending Fines</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($pending_fines) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Amount</th>
                                            <th>Reason</th>
                                            <th>Date Issued</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_fines as $fine): ?>
                                            <tr>
                                                <td>R<?php echo $fine['amount']; ?></td>
                                                <td><?php echo $fine['reason']; ?></td>
                                                <td><?php echo $fine['created_at']; ?></td>
                                                <td><span class="badge bg-warning">Pending</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">You don't have any pending fines.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>