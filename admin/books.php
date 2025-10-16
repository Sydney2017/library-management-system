<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// // Add new book
// if (isset($_POST['add_book'])) {
//     // ... existing add book code ...
// }

// // Update book
// if (isset($_POST['update_book'])) {
//     // ... existing update book code ...
// }

// // Delete book
// if (isset($_POST['delete_book'])) {
//     // ... existing delete book code ...
// }


// Update book
if (isset($_POST['update_book'])) {
    $id = sanitize($_POST['book_id']);
    $title = sanitize($_POST['title']);
    $author = sanitize($_POST['author']);
    $genre = sanitize($_POST['genre']);
    $publisher = sanitize($_POST['publisher']);
    $publication_date = sanitize($_POST['publication_date']);
    $edition = sanitize($_POST['edition']);
    $description = sanitize($_POST['description']);
    $total_copies = sanitize($_POST['total_copies']);

    // Calculate available copies
    $current_book = $db->prepare("SELECT total_copies, available_copies FROM books WHERE id = :id");
    $current_book->bindParam(':id', $id);
    $current_book->execute();
    $book_data = $current_book->fetch(PDO::FETCH_ASSOC);

    $copies_diff = $total_copies - $book_data['total_copies'];
    $new_available = $book_data['available_copies'] + $copies_diff;

    $query = "UPDATE books SET title = :title, author = :author, genre = :genre, publisher = :publisher, 
              publication_date = :publication_date, edition = :edition, description = :description, 
              total_copies = :total_copies, available_copies = :available_copies WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':genre', $genre);
    $stmt->bindParam(':publisher', $publisher);
    $stmt->bindParam(':publication_date', $publication_date);
    $stmt->bindParam(':edition', $edition);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':total_copies', $total_copies);
    $stmt->bindParam(':available_copies', $new_available);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update book.";
    }
}

// Delete book
if (isset($_POST['delete_book'])) {
    $id = sanitize($_POST['book_id']);

    // Check if book has active loans
    $check_loans = $db->prepare("SELECT COUNT(*) as active_loans FROM book_loans WHERE book_id = :id AND status = 'active'");
    $check_loans->bindParam(':id', $id);
    $check_loans->execute();
    $loans = $check_loans->fetch(PDO::FETCH_ASSOC);

    if ($loans['active_loans'] > 0) {
        $_SESSION['error'] = "Cannot delete book with active loans.";
    } else {
        $query = "DELETE FROM books WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Book deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete book.";
        }
    }
}// Add new book
if (isset($_POST['add_book'])) {
    $isbn = sanitize($_POST['isbn']);
    $title = sanitize($_POST['title']);
    $author = sanitize($_POST['author']);
    $genre = sanitize($_POST['genre']);
    $publisher = sanitize($_POST['publisher']);
    $publication_date = sanitize($_POST['publication_date']);
    $edition = sanitize($_POST['edition']);
    $description = sanitize($_POST['description']);
    $total_copies = sanitize($_POST['total_copies']);

    $query = "INSERT INTO books (isbn, title, author, genre, publisher, publication_date, edition, description, total_copies, available_copies) 
              VALUES (:isbn, :title, :author, :genre, :publisher, :publication_date, :edition, :description, :total_copies, :total_copies)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':isbn', $isbn);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':genre', $genre);
    $stmt->bindParam(':publisher', $publisher);
    $stmt->bindParam(':publication_date', $publication_date);
    $stmt->bindParam(':edition', $edition);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':total_copies', $total_copies);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add book. ISBN might already exist.";
    }
}

// Update book
if (isset($_POST['update_book'])) {
    $id = sanitize($_POST['book_id']);
    $title = sanitize($_POST['title']);
    $author = sanitize($_POST['author']);
    $genre = sanitize($_POST['genre']);
    $publisher = sanitize($_POST['publisher']);
    $publication_date = sanitize($_POST['publication_date']);
    $edition = sanitize($_POST['edition']);
    $description = sanitize($_POST['description']);
    $total_copies = sanitize($_POST['total_copies']);

    // Calculate available copies
    $current_book = $db->prepare("SELECT total_copies, available_copies FROM books WHERE id = :id");
    $current_book->bindParam(':id', $id);
    $current_book->execute();
    $book_data = $current_book->fetch(PDO::FETCH_ASSOC);

    $copies_diff = $total_copies - $book_data['total_copies'];
    $new_available = $book_data['available_copies'] + $copies_diff;

    $query = "UPDATE books SET title = :title, author = :author, genre = :genre, publisher = :publisher, 
              publication_date = :publication_date, edition = :edition, description = :description, 
              total_copies = :total_copies, available_copies = :available_copies WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':genre', $genre);
    $stmt->bindParam(':publisher', $publisher);
    $stmt->bindParam(':publication_date', $publication_date);
    $stmt->bindParam(':edition', $edition);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':total_copies', $total_copies);
    $stmt->bindParam(':available_copies', $new_available);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Book updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update book.";
    }
}

// Delete book
if (isset($_POST['delete_book'])) {
    $id = sanitize($_POST['book_id']);

    // Check if book has active loans
    $check_loans = $db->prepare("SELECT COUNT(*) as active_loans FROM book_loans WHERE book_id = :id AND status = 'active'");
    $check_loans->bindParam(':id', $id);
    $check_loans->execute();
    $loans = $check_loans->fetch(PDO::FETCH_ASSOC);

    if ($loans['active_loans'] > 0) {
        $_SESSION['error'] = "Cannot delete book with active loans.";
    } else {
        $query = "DELETE FROM books WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Book deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete book.";
        }
    }
}

// Get all books
$books_query = "SELECT * FROM books ORDER BY title";
$books_stmt = $db->query($books_query);
$books = $books_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Management - Library Management System</title>
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
                    <h1 class="h2">Books Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="bi bi-plus-circle"></i> Add New Book
                    </button>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Books Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">All Books</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ISBN</th>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Genre</th>
                                        <th>Total Copies</th>
                                        <th>Available</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td><?php echo $book['isbn']; ?></td>
                                            <td><?php echo $book['title']; ?></td>
                                            <td><?php echo $book['author']; ?></td>
                                            <td><?php echo $book['genre']; ?></td>
                                            <td><?php echo $book['total_copies']; ?></td>
                                            <td><?php echo $book['available_copies']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $book['status'] == 'available' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($book['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-book-btn" 
                                                        data-book-id="<?php echo $book['id']; ?>"
                                                        data-book-data='<?php echo json_encode($book); ?>'>
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-book-btn" 
                                                        data-book-id="<?php echo $book['id']; ?>"
                                                        data-book-title="<?php echo htmlspecialchars($book['title']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN *</label>
                                    <input type="text" class="form-control" name="isbn" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Author *</label>
                                    <input type="text" class="form-control" name="author" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Genre</label>
                                    <input type="text" class="form-control" name="genre">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Publisher</label>
                                    <input type="text" class="form-control" name="publisher">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Publication Date</label>
                                    <input type="date" class="form-control" name="publication_date">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Edition</label>
                                    <input type="text" class="form-control" name="edition">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Copies *</label>
                                    <input type="number" class="form-control" name="total_copies" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal (Single Modal) -->
    <div class="modal fade" id="editBookModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Book</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="book_id" id="editBookId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="editBookIsbn" disabled>
                                    <small class="text-muted">ISBN cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" class="form-control" name="title" id="editBookTitle" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Author *</label>
                                    <input type="text" class="form-control" name="author" id="editBookAuthor" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Genre</label>
                                    <input type="text" class="form-control" name="genre" id="editBookGenre">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Publisher</label>
                                    <input type="text" class="form-control" name="publisher" id="editBookPublisher">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Publication Date</label>
                                    <input type="date" class="form-control" name="publication_date" id="editBookPubDate">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Edition</label>
                                    <input type="text" class="form-control" name="edition" id="editBookEdition">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Copies *</label>
                                    <input type="number" class="form-control" name="total_copies" id="editBookTotalCopies" min="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editBookDescription" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Book Modal (Single Modal) -->
    <div class="modal fade" id="deleteBookModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="book_id" id="deleteBookId">
                        <p>Are you sure you want to delete the book "<strong id="deleteBookTitle"></strong>"?</p>
                        <p class="text-danger">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_book" class="btn btn-danger">Delete Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Book Modal
        document.querySelectorAll('.edit-book-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookData = JSON.parse(this.getAttribute('data-book-data'));
                
                // Fill the edit form
                document.getElementById('editBookId').value = bookData.id;
                document.getElementById('editBookIsbn').value = bookData.isbn;
                document.getElementById('editBookTitle').value = bookData.title;
                document.getElementById('editBookAuthor').value = bookData.author;
                document.getElementById('editBookGenre').value = bookData.genre || '';
                document.getElementById('editBookPublisher').value = bookData.publisher || '';
                document.getElementById('editBookPubDate').value = bookData.publication_date || '';
                document.getElementById('editBookEdition').value = bookData.edition || '';
                document.getElementById('editBookTotalCopies').value = bookData.total_copies;
                document.getElementById('editBookDescription').value = bookData.description || '';
                
                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editBookModal'));
                editModal.show();
            });
        });

        // Delete Book Modal
        document.querySelectorAll('.delete-book-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-book-id');
                const bookTitle = this.getAttribute('data-book-title');
                
                // Fill the delete form
                document.getElementById('deleteBookId').value = bookId;
                document.getElementById('deleteBookTitle').textContent = bookTitle;
                
                // Show the modal
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteBookModal'));
                deleteModal.show();
            });
        });

        // Clear modal forms when hidden
        document.getElementById('addBookModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });

        document.getElementById('editBookModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>
