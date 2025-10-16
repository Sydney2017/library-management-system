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

// Get all reservations
$reservations_query = "SELECT r.*, b.title, b.author, b.isbn 
                       FROM reservations r 
                       JOIN books b ON r.book_id = b.id 
                       WHERE r.member_id = :member_id 
                       ORDER BY r.created_at DESC";
$reservations_stmt = $db->prepare($reservations_query);
$reservations_stmt->bindParam(':member_id', $member['id']);
$reservations_stmt->execute();
$all_reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Cancel reservation
if (isset($_POST['cancel_reservation'])) {
    $reservation_id = sanitize($_POST['reservation_id']);
    
    try {
        $db->beginTransaction();
        
        // Get book_id from reservation
        $get_reservation = $db->prepare("SELECT book_id FROM reservations WHERE id = :id AND member_id = :member_id");
        $get_reservation->bindParam(':id', $reservation_id);
        $get_reservation->bindParam(':member_id', $member['id']);
        $get_reservation->execute();
        $reservation = $get_reservation->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            throw new Exception("Reservation not found or you don't have permission to cancel it.");
        }
        
        $book_id = $reservation['book_id'];
        
        // Update reservation status to cancelled
        $cancel_query = "UPDATE reservations SET status = 'cancelled' WHERE id = :id AND member_id = :member_id";
        $cancel_stmt = $db->prepare($cancel_query);
        $cancel_stmt->bindParam(':id', $reservation_id);
        $cancel_stmt->bindParam(':member_id', $member['id']);
        
        // Increase available copies by 1
        $update_book = $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = :book_id");
        $update_book->bindParam(':book_id', $book_id);
        
        if ($cancel_stmt->execute() && $update_book->execute()) {
            $db->commit();
            $_SESSION['success'] = "Reservation cancelled successfully! The book is now available for others.";
            redirect('reservations.php');
        } else {
            throw new Exception("Failed to cancel reservation.");
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - Library Management System</title>
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
                    <h1 class="h2">My Reservations</h1>
                    <a href="books.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Make New Reservation
                    </a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Reservations List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">All Reservations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($all_reservations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>ISBN</th>
                                            <th>Reservation Date</th>
                                            <th>Expiry Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_reservations as $reservation): ?>
                                            <tr>
                                                <td><?php echo $reservation['title']; ?></td>
                                                <td><?php echo $reservation['author']; ?></td>
                                                <td><?php echo $reservation['isbn']; ?></td>
                                                <td><?php echo $reservation['reservation_date']; ?></td>
                                                <td>
                                                    <?php 
                                                    echo $reservation['expiry_date']; 
                                                    if ($reservation['status'] === 'active' && strtotime($reservation['expiry_date']) < time()) {
                                                        echo ' <span class="badge bg-danger">Expired</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $badge_class = '';
                                                    switch ($reservation['status']) {
                                                        case 'active':
                                                            $badge_class = 'bg-success';
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
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($reservation['status']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($reservation['status'] === 'active'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger cancel-btn" 
                                                                data-reservation-id="<?php echo $reservation['id']; ?>"
                                                                data-book-title="<?php echo htmlspecialchars($reservation['title']); ?>">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">No actions</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-bookmark-plus" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No reservations found</h5>
                                <p class="text-muted">You haven't made any book reservations yet.</p>
                                <a href="books.php" class="btn btn-primary">Browse Books</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reservation Statistics -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5>Total Reservations</h5>
                                <h3><?php echo count($all_reservations); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5>Active</h5>
                                <h3><?php echo count(array_filter($all_reservations, fn($r) => $r['status'] === 'active')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5>Fulfilled</h5>
                                <h3><?php echo count(array_filter($all_reservations, fn($r) => $r['status'] === 'fulfilled')); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5>Expired/Cancelled</h5>
                                <h3><?php echo count(array_filter($all_reservations, fn($r) => in_array($r['status'], ['expired', 'cancelled']))); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reservation Policy -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Reservation Policy</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Important Information</h6>
                            <ul class="mb-0">
                                <li>Reservations are held for 3 days</li>
                                <li>You can have up to 3 active reservations at a time</li>
                                <li>Reservations can be cancelled at any time</li>
                                <li>If you cancel a reservation, the book becomes available for others immediately</li>
                                <li>If a reservation expires, you'll need to make a new one</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Cancel Reservation Modal -->
    <div class="modal fade" id="cancelReservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="cancelReservationForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Reservation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="reservation_id" id="cancelReservationId">
                        <p>Are you sure you want to cancel your reservation for <strong id="cancelBookTitle"></strong>?</p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Note:</strong> Cancelling will make this book immediately available for other members.
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This action cannot be undone.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Reservation</button>
                        <button type="submit" name="cancel_reservation" class="btn btn-danger">Yes, Cancel Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Cancel Reservation Modal
        document.querySelectorAll('.cancel-btn').forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-reservation-id');
                const bookTitle = this.getAttribute('data-book-title');
                
                // Set the reservation ID and book title in the modal
                document.getElementById('cancelReservationId').value = reservationId;
                document.getElementById('cancelBookTitle').textContent = bookTitle;
                
                // Show the modal
                const cancelModal = new bootstrap.Modal(document.getElementById('cancelReservationModal'));
                cancelModal.show();
            });
        });

        // Clear modal when hidden
        document.getElementById('cancelReservationModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('cancelBookTitle').textContent = '';
        });
    </script>
</body>
</html>