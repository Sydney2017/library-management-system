<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Update fine status (mark as paid)
if (isset($_POST['update_fine'])) {
    $fine_id = sanitize($_POST['fine_id']);
    $status = sanitize($_POST['status']);
    $paid_date = $status === 'paid' ? date('Y-m-d') : null;

    $query = "UPDATE fines SET status = :status, paid_date = :paid_date WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':paid_date', $paid_date);
    $stmt->bindParam(':id', $fine_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Fine status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update fine status.";
    }
}

// Get all fines
$fines_query = "SELECT f.*, m.first_name, m.last_name, m.membership_id, b.title, b.isbn, bl.due_date 
               FROM fines f 
               JOIN members m ON f.member_id = m.id 
               JOIN book_loans bl ON f.loan_id = bl.id 
               JOIN books b ON bl.book_id = b.id 
               ORDER BY f.created_at DESC";
$fines_stmt = $db->query($fines_query);
$fines = $fines_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_fines = array_sum(array_column($fines, 'amount'));
$paid_fines = array_sum(array_column(
    array_filter($fines, fn($fine) => $fine['status'] === 'paid'), 
    'amount'
));
$pending_fines = array_sum(array_column(
    array_filter($fines, fn($fine) => $fine['status'] === 'pending'), 
    'amount'
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines Management - Library Management System</title>
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
                    <h1 class="h2">Fines Management</h1>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

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

                <!-- Fines Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">All Fines</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Book</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Issued Date</th>
                                        <th>Paid Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fines as $fine): ?>
                                        <tr>
                                            <td><?php echo $fine['first_name'] . ' ' . $fine['last_name']; ?> (<?php echo $fine['membership_id']; ?>)</td>
                                            <td><?php echo $fine['title']; ?> (<?php echo $fine['isbn']; ?>)</td>
                                            <td><?php echo $fine['due_date']; ?></td>
                                            <td>R<?php echo number_format($fine['amount'], 2); ?></td>
                                            <td><?php echo $fine['reason']; ?></td>
                                            <td><?php echo $fine['created_at']; ?></td>
                                            <td><?php echo $fine['paid_date'] ?: 'Not paid'; ?></td>
                                            <td>
                                                <span class="badge <?php echo $fine['status'] === 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo ucfirst($fine['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($fine['status'] === 'pending'): ?>
                                                    <button class="btn btn-sm btn-outline-success pay-fine-btn" 
                                                            data-fine-id="<?php echo $fine['id']; ?>"
                                                            data-fine-amount="<?php echo number_format($fine['amount'], 2); ?>"
                                                            data-member-name="<?php echo htmlspecialchars($fine['first_name'] . ' ' . $fine['last_name']); ?>"
                                                            data-book-title="<?php echo htmlspecialchars($fine['title']); ?>"
                                                            data-fine-reason="<?php echo htmlspecialchars($fine['reason']); ?>">
                                                        <i class="bi bi-cash"></i> Mark Paid
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">No action</span>
                                                <?php endif; ?>
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

    <!-- Pay Fine Modal (Single Modal) -->
    <div class="modal fade" id="payFineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Mark Fine as Paid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="fine_id" id="payFineId">
                        <input type="hidden" name="status" value="paid">
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <p class="form-control-plaintext" id="payMemberName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book</label>
                            <p class="form-control-plaintext" id="payBookTitle"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <p class="form-control-plaintext" id="payFineAmount"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <p class="form-control-plaintext" id="payFineReason"></p>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This will mark the fine as paid. Ensure payment has been received at the desk.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_fine" class="btn btn-success">Mark as Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pay Fine Modal
        document.querySelectorAll('.pay-fine-btn').forEach(button => {
            button.addEventListener('click', function() {
                const fineId = this.getAttribute('data-fine-id');
                const fineAmount = this.getAttribute('data-fine-amount');
                const memberName = this.getAttribute('data-member-name');
                const bookTitle = this.getAttribute('data-book-title');
                const fineReason = this.getAttribute('data-fine-reason');
                
                // Fill the pay fine modal
                document.getElementById('payFineId').value = fineId;
                document.getElementById('payMemberName').textContent = memberName;
                document.getElementById('payBookTitle').textContent = bookTitle;
                document.getElementById('payFineAmount').textContent = 'R' + fineAmount;
                document.getElementById('payFineReason').textContent = fineReason;
                
                // Show the modal
                const payModal = new bootstrap.Modal(document.getElementById('payFineModal'));
                payModal.show();
            });
        });

        // Clear modal when hidden
        document.getElementById('payFineModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>