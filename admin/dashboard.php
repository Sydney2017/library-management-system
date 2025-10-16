<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$books_query = "SELECT COUNT(*) as total_books, SUM(available_copies) as available_books FROM books";
$books_stmt = $db->query($books_query);
$books_stats = $books_stmt->fetch(PDO::FETCH_ASSOC);

$members_query = "SELECT COUNT(*) as total_members FROM members WHERE status = 'active'";
$members_stmt = $db->query($members_query);
$members_stats = $members_stmt->fetch(PDO::FETCH_ASSOC);

$loans_query = "SELECT COUNT(*) as active_loans FROM book_loans WHERE status = 'active'";
$loans_stmt = $db->query($loans_query);
$loans_stats = $loans_stmt->fetch(PDO::FETCH_ASSOC);

$fines_query = "SELECT SUM(amount) as total_fines FROM fines WHERE status = 'pending'";
$fines_stmt = $db->query($fines_query);
$fines_stats = $fines_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Books</h5>
                                <h2><?php echo $books_stats['total_books']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Available Books</h5>
                                <h2><?php echo $books_stats['available_books']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Active Members</h5>
                                <h2><?php echo $members_stats['total_members']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Active Loans</h5>
                                <h2><?php echo $loans_stats['active_loans']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Recent Book Loans</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Member</th>
                                                <th>Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $recent_loans = $db->query("
                                                SELECT b.title, m.first_name, m.last_name, bl.due_date 
                                                FROM book_loans bl 
                                                JOIN books b ON bl.book_id = b.id 
                                                JOIN members m ON bl.member_id = m.id 
                                                WHERE bl.status = 'active' 
                                                ORDER BY bl.created_at DESC 
                                                LIMIT 5
                                            ");
                                            while ($loan = $recent_loans->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<tr>
                                                    <td>{$loan['title']}</td>
                                                    <td>{$loan['first_name']} {$loan['last_name']}</td>
                                                    <td>{$loan['due_date']}</td>
                                                </tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6>Pending Fines</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Member</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $pending_fines = $db->query("
                                                SELECT m.first_name, m.last_name, f.amount, f.status 
                                                FROM fines f 
                                                JOIN members m ON f.member_id = m.id 
                                                WHERE f.status = 'pending' 
                                                ORDER BY f.created_at DESC 
                                                LIMIT 5
                                            ");
                                            while ($fine = $pending_fines->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<tr>
                                                    <td>{$fine['first_name']} {$fine['last_name']}</td>
                                                    <td>R{$fine['amount']}</td>
                                                    <td><span class='badge bg-warning'>{$fine['status']}</span></td>
                                                </tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
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