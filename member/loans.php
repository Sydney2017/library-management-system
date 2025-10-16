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

// Get all loans history
$loans_query = "SELECT b.title, b.author, bl.checkout_date, bl.due_date, bl.return_date, bl.status 
                FROM book_loans bl 
                JOIN books b ON bl.book_id = b.id 
                WHERE bl.member_id = :member_id 
                ORDER BY bl.checkout_date DESC";
$loans_stmt = $db->prepare($loans_query);
$loans_stmt->bindParam(':member_id', $member['id']);
$loans_stmt->execute();
$all_loans = $loans_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Loans - Library Management System</title>
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
                    <h1 class="h2">My Loans History</h1>
                </div>

                <!-- Loans History -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">All Book Loans</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($all_loans) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Checkout Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_loans as $loan): ?>
                                            <tr>
                                                <td><?php echo $loan['title']; ?></td>
                                                <td><?php echo $loan['author']; ?></td>
                                                <td><?php echo $loan['checkout_date']; ?></td>
                                                <td>
                                                    <?php 
                                                    echo $loan['due_date']; 
                                                    if ($loan['status'] === 'active' && strtotime($loan['due_date']) < time()) {
                                                        echo ' <span class="badge bg-danger">Overdue</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $loan['return_date'] ? $loan['return_date'] : 'Not returned'; ?></td>
                                                <td>
                                                    <?php 
                                                    $badge_class = '';
                                                    switch ($loan['status']) {
                                                        case 'active':
                                                            $badge_class = 'bg-success';
                                                            break;
                                                        case 'returned':
                                                            $badge_class = 'bg-info';
                                                            break;
                                                        case 'overdue':
                                                            $badge_class = 'bg-danger';
                                                            break;
                                                        default:
                                                            $badge_class = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($loan['status']); ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-book" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No loan history found</h5>
                                <p class="text-muted">You haven't borrowed any books yet.</p>
                                <a href="books.php" class="btn btn-primary">Browse Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Loan Statistics -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5>Total Loans</h5>
                                <h3><?php echo count($all_loans); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5>Books Returned</h5>
                                <h3><?php echo count(array_filter($all_loans, fn($loan) => $loan['status'] === 'returned')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5>Active Loans</h5>
                                <h3><?php echo count(array_filter($all_loans, fn($loan) => $loan['status'] === 'active')); ?></h3>
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