<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// MARK RESERVATIONS AS VIEWED - ADD THIS LINE
markReservationsAsViewed();


// Automatically update expired reservations
$auto_expire_query = "UPDATE reservations 
                     SET status = 'expired' 
                     WHERE status = 'active' 
                     AND expiry_date < CURDATE()";
$auto_expire_stmt = $db->prepare($auto_expire_query);
$auto_expire_stmt->execute();
$expired_count = $auto_expire_stmt->rowCount();

if ($expired_count > 0) {
    $_SESSION['info'] = "Automatically updated $expired_count reservations to expired status.";
}

// Get all reservations
$reservations_query = "SELECT r.*, b.title, b.isbn, m.first_name, m.last_name, m.membership_id 
                      FROM reservations r 
                      JOIN books b ON r.book_id = b.id 
                      JOIN members m ON r.member_id = m.id 
                      ORDER BY r.created_at DESC";
$reservations_stmt = $db->query($reservations_query);
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_reservations = count($reservations);
$active_reservations = count(array_filter($reservations, fn($r) => $r['status'] === 'active'));
$fulfilled_reservations = count(array_filter($reservations, fn($r) => $r['status'] === 'fulfilled'));
$expired_reservations = count(array_filter($reservations, fn($r) => $r['status'] === 'expired'));
$cancelled_reservations = count(array_filter($reservations, fn($r) => $r['status'] === 'cancelled'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - Library Management System</title>
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
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Book Reservations Overview</h1>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['info'])): ?>
                    <div class="alert alert-info"><?php echo $_SESSION['info']; unset($_SESSION['info']); ?></div>
                <?php endif; ?>

                <!-- Reservation Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5>Total</h5>
                                <h3><?php echo $total_reservations; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5>Active</h5>
                                <h3><?php echo $active_reservations; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5>Fulfilled</h5>
                                <h3><?php echo $fulfilled_reservations; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5>Expired/Cancelled</h5>
                                <h3><?php echo $expired_reservations + $cancelled_reservations; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reservations Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">All Reservations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Book</th>
                                        <th>ISBN</th>
                                        <th>Member</th>
                                        <th>Reservation Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo $reservation['title']; ?></td>
                                            <td><?php echo $reservation['isbn']; ?></td>
                                            <td><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?> (<?php echo $reservation['membership_id']; ?>)</td>
                                            <td><?php echo $reservation['reservation_date']; ?></td>
                                            <td>
                                                <?php 
                                                echo $reservation['expiry_date']; 
                                                if ($reservation['status'] === 'active' && strtotime($reservation['expiry_date']) < time()) {
                                                    echo ' <span class="badge bg-danger status-badge">Expired</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $badge_class = '';
                                                $status_text = ucfirst($reservation['status']);
                                                
                                                switch ($reservation['status']) {
                                                    case 'active':
                                                        $badge_class = 'bg-success';
                                                        if (strtotime($reservation['expiry_date']) < time()) {
                                                            $badge_class = 'bg-warning';
                                                            $status_text = 'Expiring Soon';
                                                        }
                                                        break;
                                                    case 'fulfilled':
                                                        $badge_class = 'bg-info';
                                                        break;
                                                    case 'expired':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                    case 'cancelled':
                                                        $badge_class = 'bg-secondary';
                                                        break;
                                                    default:
                                                        $badge_class = 'bg-warning';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?> status-badge"><?php echo $status_text; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Reservation System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Automatic Reservation Management</h6>
                            <ul class="mb-0">
                                <li><strong>Automatic Expiry:</strong> Reservations automatically expire after 3 days</li>
                                <li><strong>System Managed:</strong> Status changes are handled automatically by the system</li>
                                <li><strong>Copy Management:</strong> Available copies automatically adjust when reservations expire</li>
                                <li><strong>Real-time Updates:</strong> Page loads automatically check and update expired reservations</li>
                                <li><strong>Member Priority:</strong> Only reserved members can check out books</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>