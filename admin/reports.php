<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Get report data
$books_stats = $db->query("SELECT COUNT(*) as total_books, SUM(available_copies) as available_books FROM books")->fetch();
$members_stats = $db->query("SELECT COUNT(*) as total_members FROM members WHERE status = 'active'")->fetch();
$active_loans = $db->query("SELECT COUNT(*) as active_loans FROM book_loans WHERE status = 'active'")->fetch();
$pending_fines = $db->query("SELECT SUM(amount) as total_fines FROM fines WHERE status = 'pending'")->fetch();

// Popular books
$popular_books = $db->query("
    SELECT b.title, b.author, COUNT(bl.id) as loan_count 
    FROM books b 
    LEFT JOIN book_loans bl ON b.id = bl.book_id 
    GROUP BY b.id 
    ORDER BY loan_count DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Popular genres
$popular_genres = $db->query("
    SELECT genre, COUNT(*) as book_count, 
           SUM(total_copies) as total_copies,
           SUM(available_copies) as available_copies
    FROM books 
    WHERE genre IS NOT NULL AND genre != ''
    GROUP BY genre 
    ORDER BY book_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly statistics
$monthly_stats = $db->query("
    SELECT 
        DATE_FORMAT(bl.checkout_date, '%Y-%m') as month,
        COUNT(bl.id) as loans_count,
        COUNT(DISTINCT bl.member_id) as unique_members,
        SUM(CASE WHEN f.id IS NOT NULL THEN f.amount ELSE 0 END) as fines_collected
    FROM book_loans bl
    LEFT JOIN fines f ON bl.id = f.loan_id AND f.status = 'paid'
    WHERE bl.checkout_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(bl.checkout_date, '%Y-%m')
    ORDER BY month DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">

    <style>
    /* Simple scroll fix for web view */
    main.col-md-9 {
        height: calc(100vh - 56px);
        overflow-y: auto;
    }
    
    .status-badge {
        font-size: 0.8em;
        padding: 0.4em 0.6em;
    }
    
    /* Print styles for PDF */
    @media print {
        /* Hide navigation and buttons */
        .navbar, .sidebar, .btn, .d-print-none {
            display: none !important;
        }
        
        /* Reset layout for printing */
        body, .container-fluid, .row, main.col-md-9 {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
            overflow: visible !important;
            display: block !important;
        }
        
        /* Ensure cards don't break across pages */
        .card {
            break-inside: avoid;
            margin-bottom: 20px;
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        /* Table styles for print */
        .table {
            width: 100%;
            font-size: 12px;
        }
        
        .table th, .table td {
            padding: 6px;
        }
        
        /* Ensure charts are visible */
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        
        /* Header styles */
        .border-bottom {
            border-bottom: 2px solid #333 !important;
        }
        
        /* Improve text contrast */
        .text-muted {
            color: #666 !important;
        }
        
        /* Card header styles */
        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #ddd !important;
            color: #333 !important;
        }
        
        /* Remove backgrounds for better printing */
        .bg-primary, .bg-success, .bg-info, .bg-warning {
            background-color: transparent !important;
            color: #000 !important;
            border: 1px solid #000 !important;
        }
    }
    </style>
    
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Library Reports</h1>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5>Total Books</h5>
                                <h3><?php echo $books_stats['total_books']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5>Available Books</h5>
                                <h3><?php echo $books_stats['available_books']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5>Active Members</h5>
                                <h3><?php echo $members_stats['total_members']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5>Pending Fines</h5>
                                <h3>R<?php echo number_format($pending_fines['total_fines'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Loans Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Monthly Book Loans (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyLoansChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Popular Books -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Most Popular Books</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Times Borrowed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_books as $index => $book): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo $book['title']; ?></td>
                                            <td><?php echo $book['author']; ?></td>
                                            <td><?php echo $book['loan_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Genre Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Books by Genre</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Genre</th>
                                        <th>Number of Books</th>
                                        <th>Total Copies</th>
                                        <th>Available Copies</th>
                                        <th>Utilization Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_genres as $genre): ?>
                                        <tr>
                                            <td><?php echo $genre['genre']; ?></td>
                                            <td><?php echo $genre['book_count']; ?></td>
                                            <td><?php echo $genre['total_copies']; ?></td>
                                            <td><?php echo $genre['available_copies']; ?></td>
                                            <td>
                                                <?php 
                                                $utilization = $genre['total_copies'] > 0 ? 
                                                    (($genre['total_copies'] - $genre['available_copies']) / $genre['total_copies']) * 100 : 0;
                                                echo number_format($utilization, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Monthly Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Monthly Statistics (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Total Loans</th>
                                        <th>Unique Members</th>
                                        <th>Fines Collected</th>
                                        <th>Average Loans per Member</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></td>
                                            <td><?php echo $stat['loans_count']; ?></td>
                                            <td><?php echo $stat['unique_members']; ?></td>
                                            <td>R<?php echo number_format($stat['fines_collected'], 2); ?></td>
                                            <td>
                                                <?php 
                                                $avg = $stat['unique_members'] > 0 ? 
                                                    $stat['loans_count'] / $stat['unique_members'] : 0;
                                                echo number_format($avg, 1);
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Report Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Key Insights:</h6>
                                <ul>
                                    <li>Library collection: <?php echo $books_stats['total_books']; ?> books</li>
                                    <li>Current availability: <?php echo $books_stats['available_books']; ?> books</li>
                                    <li>Active membership: <?php echo $members_stats['total_members']; ?> members</li>
                                    <li>Outstanding fines: R<?php echo number_format($pending_fines['total_fines'] ?? 0, 2); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Recommendations:</h6>
                                <ul>
                                    <li>Consider acquiring more copies of popular books</li>
                                    <li>Review genres with high utilization rates</li>
                                    <li>Follow up on outstanding fines</li>
                                    <li>Promote underutilized book categories</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Monthly Loans Chart
        const monthlyLoansChart = new Chart(
            document.getElementById('monthlyLoansChart'),
            {
                type: 'bar',
                data: {
                    labels: [<?php 
                        $reversed_stats = array_reverse($monthly_stats);
                        echo implode(',', array_map(function($stat) { 
                            return "'" . date('M Y', strtotime($stat['month'] . '-01')) . "'"; 
                        }, $reversed_stats)); 
                    ?>],
                    datasets: [{
                        label: 'Book Loans',
                        data: [<?php echo implode(',', array_column($reversed_stats, 'loans_count')); ?>],
                        backgroundColor: '#2c3e50'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );
    </script>
</body>
</html>