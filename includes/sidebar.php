<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse mt-5">
    <div class="position-sticky pt-3">
        <!-- Library Brand -->
        <div class="text-center mb-4">
            <h5 class="text-white mb-1">
                <i class="bi bi-book"></i> Library Admin
            </h5>
            <small class="text-muted">Management System</small>
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
                    <i class="bi bi-book"></i>
                    Books Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>" href="members.php">
                    <i class="bi bi-people"></i>
                    Members Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : ''; ?>" href="loans.php">
                    <i class="bi bi-arrow-left-right"></i>
                    Book Loans
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>" href="reservations.php">
                    <i class="bi bi-bookmark"></i>
                    Reservations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'fines.php' ? 'active' : ''; ?>" href="fines.php">
                    <i class="bi bi-cash-coin"></i>
                    Fines Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="bi bi-bar-chart"></i>
                    Reports & Analytics
                </a>
            </li>
        </ul>

        <hr class="border-secondary my-3">

        <!-- Quick Actions -->
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-white">
            <span>Quick Actions</span>
            <i class="bi bi-lightning"></i>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="books.php?action=add">
                    <i class="bi bi-plus-circle"></i>
                    Add New Book
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="loans.php?action=checkout">
                    <i class="bi bi-arrow-up-circle"></i>
                    Check Out Book
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="loans.php?action=checkin">
                    <i class="bi bi-arrow-down-circle"></i>
                    Check In Book
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php?type=quick">
                    <i class="bi bi-graph-up"></i>
                    Quick Stats
                </a>
            </li>
        </ul>

        <hr class="border-secondary my-3">

        <!-- User Section -->
        <div class="px-3 py-2">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <i class="bi bi-person-circle text-white fs-4"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <span class="text-white d-block"><?php echo $_SESSION['username']; ?></span>
                    <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <div class="px-3 mt-3">
            <a href="../logout.php" class="btn btn-outline-light w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- Toggle Button for Mobile -->
<button class="navbar-toggler position-fixed d-md-none" type="button" style="top: 0.5rem; left: 0.5rem; z-index: 101;" 
        data-bs-toggle="collapse" data-bs-target="#sidebar">
    <span class="navbar-toggler-icon"></span>
</button>