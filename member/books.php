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

// $member_query = "SELECT m.* FROM members m 
//                  JOIN users u ON m.user_id = u.id 
//                  WHERE u.id = :user_id";
// $member_stmt = $db->prepare($member_query);


$member_query = "SELECT m.* FROM members m 
                 JOIN users u ON m.user_id = u.id 
                 WHERE u.id = :user_id";
$member_stmt = $db->prepare($member_query);
$member_stmt->bindParam(':user_id', $_SESSION['user_id']);
$member_stmt->execute();
$member = $member_stmt->fetch(PDO::FETCH_ASSOC);

// Search functionality
$search = '';
$books = [];
if (isset($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $search_query = "SELECT * FROM books 
                    WHERE (title LIKE :search OR author LIKE :search OR genre LIKE :search OR isbn LIKE :search)
                    AND status = 'available'
                    ORDER BY title";
    $search_stmt = $db->prepare($search_query);
    $search_param = "%$search%";
    $search_stmt->bindParam(':search', $search_param);
    $search_stmt->execute();
    $books = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all available books when no search is performed
    $all_books_query = "SELECT * FROM books 
                       WHERE status = 'available'
                       ORDER BY title";
    $all_books_stmt = $db->prepare($all_books_query);
    $all_books_stmt->execute();
    $books = $all_books_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Reserve book functionality
if (isset($_POST['reserve_book'])) {
    $book_id = sanitize($_POST['book_id']);
    
    // Check if member already has active reservation for this book
    $existing_reservation_check = $db->prepare("SELECT id FROM reservations 
                                               WHERE book_id = :book_id 
                                               AND member_id = :member_id 
                                               AND status = 'active'");
    $existing_reservation_check->bindParam(':book_id', $book_id);
    $existing_reservation_check->bindParam(':member_id', $member['id']);
    $existing_reservation_check->execute();
    
    if ($existing_reservation_check->rowCount() > 0) {
        $_SESSION['error'] = "You already have an active reservation for this book.";
        redirect('books.php');
    }

    // Check if member already has this book on loan
    $existing_loan_check = $db->prepare("SELECT id FROM book_loans 
                                        WHERE book_id = :book_id 
                                        AND member_id = :member_id 
                                        AND status = 'active'");
    $existing_loan_check->bindParam(':book_id', $book_id);
    $existing_loan_check->bindParam(':member_id', $member['id']);
    $existing_loan_check->execute();
    
    if ($existing_loan_check->rowCount() > 0) {
        $_SESSION['error'] = "You already have this book on loan. You cannot reserve a book you currently have borrowed.";
        redirect('books.php');
    }

    // Check if book is physically available
    $book_check = $db->prepare("SELECT available_copies, title FROM books WHERE id = :id AND available_copies > 0");
    $book_check->bindParam(':id', $book_id);
    $book_check->execute();
    
    if ($book_check->rowCount() > 0) {
        $book_data = $book_check->fetch(PDO::FETCH_ASSOC);
        $reservation_date = date('Y-m-d');
        $expiry_date = date('Y-m-d', strtotime('+3 days'));
        
        try {
            $db->beginTransaction();
            
            // Create reservation
            $reserve_query = "INSERT INTO reservations (book_id, member_id, reservation_date, expiry_date) 
                             VALUES (:book_id, :member_id, :reservation_date, :expiry_date)";
            $reserve_stmt = $db->prepare($reserve_query);
            $reserve_stmt->bindParam(':book_id', $book_id);
            $reserve_stmt->bindParam(':member_id', $member['id']);
            $reserve_stmt->bindParam(':reservation_date', $reservation_date);
            $reserve_stmt->bindParam(':expiry_date', $expiry_date);
            
            // Reduce available copies by 1
            $update_book = $db->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = :id");
            $update_book->bindParam(':id', $book_id);
            
            if ($reserve_stmt->execute() && $update_book->execute()) {
                $db->commit();
                $_SESSION['success'] = "Book '{$book_data['title']}' reserved successfully! Please collect within 3 days (by $expiry_date).";
                redirect('books.php');
            } else {
                $db->rollBack();
                $_SESSION['error'] = "Failed to reserve book.";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Book is not available for reservation.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Library Management System</title>
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
                    <h1 class="h2">Browse Books</h1>
                </div>

                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" placeholder="Search by title, author, genre, or ISBN..." value="<?php echo $search; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                            <div class="col-md-2">
                                <a href="books.php" class="btn btn-outline-secondary w-100">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Books List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <?php if (!empty($search)): ?>
                                Search Results for "<?php echo $search; ?>"
                            <?php else: ?>
                                All Available Books
                            <?php endif; ?>
                            <span class="badge bg-primary ms-2"><?php echo count($books); ?> books</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>

                        <?php if (count($books) > 0): ?>
                            <div class="row">
                                <?php foreach ($books as $book): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo $book['title']; ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">by <?php echo $book['author']; ?></h6>
                                                <p class="card-text">
                                                    <strong>ISBN:</strong> <?php echo $book['isbn']; ?><br>
                                                    <strong>Genre:</strong> <?php echo $book['genre']; ?><br>
                                                    <strong>Publisher:</strong> <?php echo $book['publisher']; ?><br>
                                                    <strong>Available Copies:</strong> <?php echo $book['available_copies']; ?>
                                                </p>
                                                
                                                <?php 
                                                // Check if member already has active reservation for this book
                                                $has_reservation = $db->prepare("SELECT id FROM reservations 
                                                                               WHERE book_id = :book_id 
                                                                               AND member_id = :member_id 
                                                                               AND status = 'active'");
                                                $has_reservation->bindParam(':book_id', $book['id']);
                                                $has_reservation->bindParam(':member_id', $member['id']);
                                                $has_reservation->execute();
                                                $already_reserved = $has_reservation->rowCount() > 0;

                                                // Check if member already has this book on loan
                                                $has_loan = $db->prepare("SELECT id FROM book_loans 
                                                                        WHERE book_id = :book_id 
                                                                        AND member_id = :member_id 
                                                                        AND status = 'active'");
                                                $has_loan->bindParam(':book_id', $book['id']);
                                                $has_loan->bindParam(':member_id', $member['id']);
                                                $has_loan->execute();
                                                $already_borrowed = $has_loan->rowCount() > 0;
                                                ?>
                                                
                                                <!-- Simple Button Logic -->
                                                <?php if ($book['available_copies'] > 0 && !$already_reserved && !$already_borrowed): ?>
                                                    <form method="POST">
                                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                        <button type="submit" name="reserve_book" class="btn btn-success btn-sm w-100">
                                                            <i class="bi bi-bookmark-plus"></i> Reserve Book
                                                        </button>
                                                    </form>
                                                    <small class="text-muted d-block text-center mt-1">
                                                        Collect within 3 days
                                                    </small>
                                                <?php elseif ($already_reserved): ?>
                                                    <span class="badge bg-info w-100 py-2">Already Reserved</span>
                                                    <small class="text-muted d-block text-center mt-1">
                                                        You have reserved this book
                                                    </small>
                                                <?php elseif ($already_borrowed): ?>
                                                    <span class="badge bg-primary w-100 py-2">Currently Borrowed</span>
                                                    <small class="text-muted d-block text-center mt-1">
                                                        You already have this book
                                                    </small>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary w-100 py-2">Not Available</span>
                                                    <small class="text-muted d-block text-center mt-1">
                                                        No copies available
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-book" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No books found</h5>
                                <p class="text-muted">
                                    <?php if (!empty($search)): ?>
                                        No books found for "<?php echo $search; ?>". Try searching for different keywords.
                                    <?php else: ?>
                                        No books are currently available. Please check back later.
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($search)): ?>
                                    <a href="books.php" class="btn btn-primary">View All Books</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
                                <li>Reservations are held for <strong>3 days only</strong></li>
                                <li><strong>Only reserved members can check out books</strong></li>
                                <li>You must reserve online before visiting the library</li>
                                <li>If you don't collect within 3 days, the reservation expires</li>
                                <li>You cannot reserve books you already have on loan</li>
                                <li>Maximum 3 active reservations per member</li>
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