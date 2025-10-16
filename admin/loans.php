<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Check out book
if (isset($_POST['checkout_book'])) {
    $book_id = sanitize($_POST['book_id']);
    $member_id = sanitize($_POST['member_id']);
    $due_date = sanitize($_POST['due_date']);

    // Check if member has pending fines
    $fines_check = $db->prepare("SELECT SUM(amount) as total_fines FROM fines WHERE member_id = :member_id AND status = 'pending'");
    $fines_check->bindParam(':member_id', $member_id);
    $fines_check->execute();
    $fines_result = $fines_check->fetch(PDO::FETCH_ASSOC);
    
    if ($fines_result['total_fines'] > 0) {
        $_SESSION['error'] = "Member has pending fines of R" . number_format($fines_result['total_fines'], 2) . ". Cannot borrow books until fines are settled.";
        redirect('loans.php');
    }

    // Check if member already has this book
    $existing_loan_check = $db->prepare("SELECT id FROM book_loans WHERE book_id = :book_id AND member_id = :member_id AND status = 'active'");
    $existing_loan_check->bindParam(':book_id', $book_id);
    $existing_loan_check->bindParam(':member_id', $member_id);
    $existing_loan_check->execute();
    
    if ($existing_loan_check->rowCount() > 0) {
        $_SESSION['error'] = "Member already has this book on loan. Cannot borrow the same book twice.";
        redirect('loans.php');
    }

    // Check if member has active reservation for this book
    $reservation_check = $db->prepare("
        SELECT id FROM reservations 
        WHERE book_id = :book_id 
        AND member_id = :member_id 
        AND status = 'active'
        AND expiry_date >= CURDATE()
    ");
    $reservation_check->bindParam(':book_id', $book_id);
    $reservation_check->bindParam(':member_id', $member_id);
    $reservation_check->execute();
    
    if ($reservation_check->rowCount() == 0) {
        $_SESSION['error'] = "This member must reserve this book online before checking it out. Only reserved members can borrow books.";
        redirect('loans.php');
    }

    $reservation_id = $reservation_check->fetch(PDO::FETCH_ASSOC)['id'];
    $checkout_date = date('Y-m-d');
    
    try {
        $db->beginTransaction();
        
        // Create loan record
        $loan_query = "INSERT INTO book_loans (book_id, member_id, librarian_id, checkout_date, due_date) 
                      VALUES (:book_id, :member_id, :librarian_id, :checkout_date, :due_date)";
        $loan_stmt = $db->prepare($loan_query);
        $loan_stmt->bindParam(':book_id', $book_id);
        $loan_stmt->bindParam(':member_id', $member_id);
        $loan_stmt->bindParam(':librarian_id', $_SESSION['user_id']);
        $loan_stmt->bindParam(':checkout_date', $checkout_date);
        $loan_stmt->bindParam(':due_date', $due_date);

        if ($loan_stmt->execute()) {
            // Mark reservation as fulfilled
            $fulfill_reservation = $db->prepare("UPDATE reservations SET status = 'fulfilled' WHERE id = :id");
            $fulfill_reservation->bindParam(':id', $reservation_id);
            $fulfill_reservation->execute();
            
            // NOTE: available_copies stays SAME - already reduced during reservation
            $db->commit();
            $_SESSION['success'] = "Book checked out successfully!";
        } else {
            $db->rollBack();
            $_SESSION['error'] = "Failed to check out book.";
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Check in book
if (isset($_POST['checkin_book'])) {
    $loan_id = sanitize($_POST['loan_id']);

    $return_date = date('Y-m-d');
    
    // Get loan details for fine calculation
    $loan_details = $db->prepare("SELECT due_date, member_id, book_id FROM book_loans WHERE id = :id");
    $loan_details->bindParam(':id', $loan_id);
    $loan_details->execute();
    $loan = $loan_details->fetch(PDO::FETCH_ASSOC);

    $fine_amount = calculateFine($loan['due_date']);

    try {
        $db->beginTransaction();

        // Update loan record
        $update_loan = $db->prepare("UPDATE book_loans SET return_date = :return_date, status = 'returned' WHERE id = :id");
        $update_loan->bindParam(':return_date', $return_date);
        $update_loan->bindParam(':id', $loan_id);

        // Update book availability - increase by 1
        $update_book = $db->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = :id");
        $update_book->bindParam(':id', $loan['book_id']);

        if ($update_loan->execute() && $update_book->execute()) {
            // Add fine if applicable
            if ($fine_amount > 0) {
                $fine_query = "INSERT INTO fines (loan_id, member_id, amount, reason) 
                              VALUES (:loan_id, :member_id, :amount, :reason)";
                $fine_stmt = $db->prepare($fine_query);
                $fine_stmt->bindParam(':loan_id', $loan_id);
                $fine_stmt->bindParam(':member_id', $loan['member_id']);
                $fine_stmt->bindParam(':amount', $fine_amount);
                $reason = "Late return - " . floor($fine_amount / 2) . " days overdue";
                $fine_stmt->bindParam(':reason', $reason);
                $fine_stmt->execute();
            }

            $db->commit();
            $_SESSION['success'] = "Book checked in successfully!" . ($fine_amount > 0 ? " Fine of R$fine_amount applied." : "");
        } else {
            $db->rollBack();
            $_SESSION['error'] = "Failed to check in book.";
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get active loans
$active_loans_query = "SELECT bl.*, b.title, b.isbn, m.first_name, m.last_name, m.membership_id,
                      u.username as librarian_name 
                      FROM book_loans bl 
                      JOIN books b ON bl.book_id = b.id 
                      JOIN members m ON bl.member_id = m.id 
                      JOIN users u ON bl.librarian_id = u.id 
                      WHERE bl.status = 'active' 
                      ORDER BY bl.due_date";
$active_loans_stmt = $db->query($active_loans_query);
$active_loans = $active_loans_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all books for checkout (only show books with available copies)
$books_query = "SELECT * FROM books WHERE available_copies > 0 AND status = 'available' ORDER BY title";
$books_stmt = $db->query($books_query);
$available_books = $books_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all active members
$members_query = "SELECT * FROM members WHERE status = 'active' ORDER BY first_name, last_name";
$members_stmt = $db->query($members_query);
$active_members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Loans - Library Management System</title>
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
                    <h1 class="h2">Book Loans Management</h1>
                    <div>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                            <i class="bi bi-arrow-up-circle"></i> Check Out
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkinModal">
                            <i class="bi bi-arrow-down-circle"></i> Check In
                        </button>
                    </div>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Active Loans -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Active Book Loans</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($active_loans) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book</th>
                                            <th>ISBN</th>
                                            <th>Member</th>
                                            <th>Checkout Date</th>
                                            <th>Due Date</th>
                                            <th>Librarian</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($active_loans as $loan): ?>
                                            <tr>
                                                <td><?php echo $loan['title']; ?></td>
                                                <td><?php echo $loan['isbn']; ?></td>
                                                <td><?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?> (<?php echo $loan['membership_id']; ?>)</td>
                                                <td><?php echo $loan['checkout_date']; ?></td>
                                                <td>
                                                    <?php 
                                                    echo $loan['due_date']; 
                                                    if (strtotime($loan['due_date']) < time()) {
                                                        echo ' <span class="badge bg-danger">Overdue</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $loan['librarian_name']; ?></td>
                                                <td><span class="badge bg-success">Active</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-book" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No active loans</h5>
                                <p class="text-muted">There are no active book loans at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">Loan System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> How Our Loan System Works</h6>
                            <ul class="mb-0">
                                <li><strong>Reservation Required:</strong> Members must reserve books online before checkout</li>
                                <li><strong>3-Day Collection:</strong> Reserved books must be collected within 3 days</li>
                                <li><strong>No Walk-in Checkouts:</strong> Only reserved members can borrow books</li>
                                <li><strong>14-Day Loan Period:</strong> Standard borrowing period is 14 days</li>
                                <li><strong>Fine System:</strong> R2 per day for overdue books</li>
                                <li><strong>Blocked Access:</strong> Members with pending fines cannot borrow books</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Check Out Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Check Out Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>Note:</strong> Members must reserve books online before checkout. Only reserved members can borrow books.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Book *</label>
                            <select class="form-select" name="book_id" required>
                                <option value="">Choose a book...</option>
                                <?php foreach ($available_books as $book): ?>
                                    <option value="<?php echo $book['id']; ?>">
                                        <?php echo $book['title']; ?> by <?php echo $book['author']; ?> (ISBN: <?php echo $book['isbn']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Only books with available copies are shown</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Member *</label>
                            <select class="form-select" name="member_id" required>
                                <option value="">Choose a member...</option>
                                <?php foreach ($active_members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo $member['first_name'] . ' ' . $member['last_name']; ?> (<?php echo $member['membership_id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date *</label>
                            <input type="date" class="form-control" name="due_date" 
                                   value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                            <small class="text-muted">Standard loan period is 14 days</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="checkout_book" class="btn btn-success">Check Out</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Check In Modal -->
    <div class="modal fade" id="checkinModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Check In Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Book</th>
                                        <th>Member</th>
                                        <th>Checkout Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_loans as $loan): ?>
                                        <tr>
                                            <td>
                                                <input type="radio" name="loan_id" value="<?php echo $loan['id']; ?>" required>
                                            </td>
                                            <td><?php echo $loan['title']; ?> (<?php echo $loan['isbn']; ?>)</td>
                                            <td><?php echo $loan['first_name'] . ' ' . $loan['last_name']; ?></td>
                                            <td><?php echo $loan['checkout_date']; ?></td>
                                            <td>
                                                <?php 
                                                echo $loan['due_date']; 
                                                if (strtotime($loan['due_date']) < time()) {
                                                    $days_late = floor((time() - strtotime($loan['due_date'])) / (60 * 60 * 24));
                                                    echo ' <span class="badge bg-danger">' . $days_late . ' days overdue</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><span class="badge bg-success">Active</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($active_loans) === 0): ?>
                            <p class="text-center text-muted py-3">No active loans to check in</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if (count($active_loans) > 0): ?>
                            <button type="submit" name="checkin_book" class="btn btn-primary">Check In Selected</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>