<?php
// Get new reservations count
$new_reservations_count = getNewReservationsCount($db);
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <!-- Notifications Header -->
        <div class="sidebar-header p-3 border-bottom text-center bg-white">
            <h6 class="mb-0">
                <i class="bi bi-bell"></i> 
                Notifications
                <?php if ($new_reservations_count > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-1"><?php echo $new_reservations_count; ?></span>
                <?php endif; ?>
            </h6>
        </div>
        
        <!-- Navigation Menu -->
        <ul class="nav flex-column mt-2">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="books.php">
                    <i class="bi bi-book me-2"></i>
                    Manage Books
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="members.php">
                    <i class="bi bi-people me-2"></i>
                    Manage Members
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="loans.php">
                    <i class="bi bi-arrow-left-right me-2"></i>
                    Book Loans
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reservations.php">
                    <i class="bi bi-bookmark-check me-2"></i>
                    Reservations
                    <?php if ($new_reservations_count > 0): ?>
                        <span class="badge bg-warning rounded-pill float-end">
                            <?php echo $new_reservations_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="fines.php">
                    <i class="bi bi-cash-coin me-2"></i>
                    Fines Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="bi bi-graph-up me-2"></i>
                    Reports
                </a>
            </li>
        </ul>

        <!-- New Reservations Alert -->
        <!-- //<?php if ($new_reservations_count > 0): ?>
        <div class="sidebar-footer p-3 border-top bg-warning bg-opacity-10 mt-3">
            <small class="text-muted d-block">Attention Needed:</small>
            <div class="mt-1">
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    //<?php echo $new_reservations_count; ?> new reservation(s)
                </span>
            </div>
        </div>
        <?php endif; ?> -->
    </div>
</nav>

<style>
#sidebar {
    height: calc(100vh - 56px);
    overflow-y: auto;
    position: fixed;
    left: 0;
    top: 56px;
    z-index: 100;
}

.sidebar-header {
    background-color: #f8f9fa !important;
}

.nav-link {
    border-radius: 0.375rem;
    margin: 0.1rem 0.5rem;
    padding: 0.75rem 1rem !important;
}

.nav-link:hover {
    background-color: #e9ecef;
}

.nav-link.active {
    background-color: #0d6efd;
    color: white !important;
}

.sidebar-footer {
    border-color: #ffeaa7 !important;
}

/* Ensure main content doesn't overlap */
main.col-md-9 {
    margin-left: 25%;
    padding-top: 1rem;
}

/* Responsive fix */
@media (max-width: 768px) {
    #sidebar {
        position: static;
        height: auto;
    }
    main.col-md-9 {
        margin-left: 0;
    }
}
</style>