<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/database.php';

if (!isLoggedIn() || (!isAdmin() && !isLibrarian())) {
    redirect('../login.php');
}

$database = new Database();
$db = $database->getConnection();

// Update member status
if (isset($_POST['update_status'])) {
    $member_id = sanitize($_POST['member_id']);
    $status = sanitize($_POST['status']);

    $query = "UPDATE members SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $member_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Member status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update member status.";
    }
}

// Get all members with user details
$members_query = "SELECT m.*, u.username, u.email, u.created_at as join_date 
                 FROM members m 
                 JOIN users u ON m.user_id = u.id 
                 ORDER BY m.first_name, m.last_name";
$members_stmt = $db->query($members_query);
$members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Management - Library Management System</title>
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
                    <h1 class="h2">Members Management</h1>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search members by name, email, or membership ID...">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" onclick="filterMembers()">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">All Members</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="membersTable">
                                <thead>
                                    <tr>
                                        <th>Membership ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Join Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td><?php echo $member['membership_id']; ?></td>
                                            <td><?php echo $member['first_name'] . ' ' . $member['last_name']; ?></td>
                                            <td><?php echo $member['email']; ?></td>
                                            <td><?php echo $member['phone'] ?: 'N/A'; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($member['join_date'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $member['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ucfirst($member['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary view-member-btn" 
                                                        data-member-data='<?php echo json_encode($member); ?>'>
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning status-member-btn" 
                                                        data-member-id="<?php echo $member['id']; ?>"
                                                        data-member-name="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>"
                                                        data-current-status="<?php echo $member['status']; ?>">
                                                    <i class="bi bi-pencil"></i> Status
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

    <!-- View Member Modal -->
    <div class="modal fade" id="viewMemberModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Member Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Membership ID</strong></label>
                                <p class="form-control-plaintext" id="viewMembershipId"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Status</strong></label>
                                <p id="viewStatus"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Name</strong></label>
                                <p class="form-control-plaintext" id="viewName"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Date of Birth</strong></label>
                                <p class="form-control-plaintext" id="viewDob"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Email</strong></label>
                                <p class="form-control-plaintext" id="viewEmail"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Phone</strong></label>
                                <p class="form-control-plaintext" id="viewPhone"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Address</strong></label>
                        <p class="form-control-plaintext" id="viewAddress"></p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Username</strong></label>
                                <p class="form-control-plaintext" id="viewUsername"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Join Date</strong></label>
                                <p class="form-control-plaintext" id="viewJoinDate"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Modal -->
    <div class="modal fade" id="statusMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Member Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="member_id" id="statusMemberId">
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <p class="form-control-plaintext fw-bold" id="statusMemberName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Status</label>
                            <p id="currentStatus"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" name="status" id="newStatus" required>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View Member Modal
        document.querySelectorAll('.view-member-btn').forEach(button => {
            button.addEventListener('click', function() {
                const memberData = JSON.parse(this.getAttribute('data-member-data'));
                
                // Fill the view modal
                document.getElementById('viewMembershipId').textContent = memberData.membership_id;
                document.getElementById('viewName').textContent = memberData.first_name + ' ' + memberData.last_name;
                document.getElementById('viewEmail').textContent = memberData.email;
                document.getElementById('viewPhone').textContent = memberData.phone || 'N/A';
                document.getElementById('viewDob').textContent = memberData.date_of_birth ? 
                    new Date(memberData.date_of_birth).toLocaleDateString() : 'N/A';
                document.getElementById('viewAddress').textContent = memberData.address || 'N/A';
                document.getElementById('viewUsername').textContent = memberData.username;
                document.getElementById('viewJoinDate').textContent = new Date(memberData.join_date).toLocaleDateString();
                
                // Status badge
                const statusBadge = memberData.status === 'active' ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-danger">Suspended</span>';
                document.getElementById('viewStatus').innerHTML = statusBadge;
                
                // Show the modal
                const viewModal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
                viewModal.show();
            });
        });

        // Status Member Modal
        document.querySelectorAll('.status-member-btn').forEach(button => {
            button.addEventListener('click', function() {
                const memberId = this.getAttribute('data-member-id');
                const memberName = this.getAttribute('data-member-name');
                const currentStatus = this.getAttribute('data-current-status');
                
                // Fill the status modal
                document.getElementById('statusMemberId').value = memberId;
                document.getElementById('statusMemberName').textContent = memberName;
                
                // Current status badge
                const currentStatusBadge = currentStatus === 'active' ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-danger">Suspended</span>';
                document.getElementById('currentStatus').innerHTML = currentStatusBadge;
                
                // Set the new status dropdown
                document.getElementById('newStatus').value = currentStatus;
                
                // Show the modal
                const statusModal = new bootstrap.Modal(document.getElementById('statusMemberModal'));
                statusModal.show();
            });
        });

        // Search functionality
        function filterMembers() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#membersTable tbody tr');
            
            rows.forEach(row => {
                const membershipId = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                
                const matchesSearch = membershipId.includes(searchText) || 
                                    name.includes(searchText) || 
                                    email.includes(searchText);
                
                row.style.display = matchesSearch ? '' : 'none';
            });
        }

        // Initialize search on input
        document.getElementById('searchInput').addEventListener('input', filterMembers);
    </script>
</body>
</html>