<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse mt-5">
    <div class="position-sticky pt-3">
        <!-- Member Header -->
        <div class="text-center mb-4">
            <h5 class="text-white mb-1">
                <i class="bi bi-person"></i> Member Portal
            </h5>
            <small class="text-muted">Library Services</small>
        </div>

        <hr class="border-secondary my-3">

        <!-- Main Navigation -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'books.php' ? 'active' : ''; ?>" href="books.php">
                    <i class="bi bi-search"></i>
                    Browse Books
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : ''; ?>" href="loans.php">
                    <i class="bi bi-arrow-left-right"></i>
                    My Loans
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>" href="reservations.php">
                    <i class="bi bi-bookmark"></i>
                    My Reservations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fines.php' ? 'active' : ''; ?>" href="fines.php">
                    <i class="bi bi-cash-coin"></i>
                    My Fines
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="bi bi-person"></i>
                    My Profile
                </a>
            </li>
        </ul>

        <hr class="border-secondary my-3">

        <!-- Quick Actions -->
        <!-- <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white">
            <span>Quick Access</span>
            <i class="bi bi-stars"></i>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="books.php?search=available">
                    <i class="bi bi-check-circle"></i>
                    Available Books
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reservations.php?action=new">
                    <i class="bi bi-plus-circle"></i>
                    New Reservation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="fines.php">
                    <i class="bi bi-credit-card"></i>
                    Pay Fines
                </a>
            </li>
        </ul>

        <hr class="border-secondary my-3"> -->

        <!-- User Info -->
        <div class="px-3 py-2">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-person-badge text-white fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <span class="text-white d-block">Member</span>
                    <small class="text-muted">Library Account</small>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <div class="px-3 mt-3">
            <a href="../logout.php" class="btn btn-outline-light w-100">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </div>
    </div>
</nav>

<!-- Toggle Button for Mobile -->
<button class="navbar-toggler position-fixed d-md-none" type="button" style="top: 0.5rem; left: 0.5rem; z-index: 101;" 
        data-bs-toggle="collapse" data-bs-target="#sidebar">
    <span class="navbar-toggler-icon"></span>
</button>