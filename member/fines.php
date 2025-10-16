<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member')) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get member details
$member_query = "SELECT m.* FROM members m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE u.id = :user_id";
$member_stmt = $db->prepare($member_query);
$member_stmt->bindParam(':user_id', $_SESSION['user_id']);
$member_stmt->execute();
$member = $member_stmt->fetch(PDO::FETCH_ASSOC);

// Get all fines
$fines_query = "SELECT f.*, b.title, bl.due_date 
                FROM fines f 
                JOIN book_loans bl ON f.loan_id = bl.id 
                JOIN books b ON bl.book_id = b.id 
                WHERE f.member_id = :member_id 
                ORDER BY f.created_at DESC";
$fines_stmt = $db->prepare($fines_query);
$fines_stmt->bindParam(':member_id', $member['id']);
$fines_stmt->execute();
$all_fines = $fines_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_fines = array_sum(array_column($all_fines, 'amount'));
$paid_fines = array_sum(array_column(
    array_filter($all_fines, fn($fine) => $fine['status'] === 'paid'), 
    'amount'
));
$pending_fines = array_sum(array_column(
    array_filter($all_fines, fn($fine) => $fine['status'] === 'pending'), 
    'amount'
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Fines - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
    /* Fix scrolling for reservations page only */
    main.col-md-9 {
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
    
    .sidebar {
        height: calc(100vh - 56px);
        overflow-y: auto;
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
                    <h1 class="h2">My Fines</h1>
                </div>

                <!-- Fines Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5>Total Fines</h5>
                                <h3>R<?php echo number_format($total_fines, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5>Paid Fines</h5>
                                <h3>R<?php echo number_format($paid_fines, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5>Pending Fines</h5>
                                <h3>R<?php echo number_format($pending_fines, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fines Details -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Fines History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($all_fines) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Amount</th>
                                            <th>Reason</th>
                                            <th>Due Date</th>
                                            <th>Issued Date</th>
                                            <th>Paid Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_fines as $fine): ?>
                                            <tr>
                                                <td><?php echo $fine['title']; ?></td>
                                                <td>R<?php echo number_format($fine['amount'], 2); ?></td>
                                                <td><?php echo $fine['reason'] ?: 'Late return'; ?></td>
                                                <td><?php echo $fine['due_date']; ?></td>
                                                <td><?php echo $fine['created_at']; ?></td>
                                                <td><?php echo $fine['paid_date'] ?: 'Not paid'; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $fine['status'] === 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($fine['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-emoji-smile" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No fines found</h5>
                                <p class="text-muted">You don't have any fines at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Payment Instructions</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> How to Pay Your Fines</h6>
                            <p>To pay your fines, please visit the library administration desk during working hours.</p>
                            <ul>
                                <li>Bring your membership card</li>
                                <li>Payments can be made in cash or by card</li>
                                <li>Receipts will be provided for all payments</li>
                                <li>Fines must be cleared before borrowing more books</li>
                            </ul>
                            <p class="mb-0"><strong>Library Hours:</strong> Monday-Friday, 9:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>