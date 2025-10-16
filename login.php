<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>

<?php include 'header-auth.php'; ?>

    <div class="container" style="padding-top: 80px; padding-bottom: 50px;">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header text-center mb-4">
                        <div class="auth-logo">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <h2 class="auth-title">Welcome Back</h2>
                        <p class="auth-subtitle">Sign in to your library account</p>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required 
                                   placeholder="Enter your username">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" id="password" name="password" required 
                                       placeholder="Enter your password">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y border-0 text-secondary" id="togglePassword" style="background: none;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-primary w-100 py-3" style="border-radius: 50px; font-weight: 500;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">Don't have an account? <a href="register.php" class="text-decoration-none" style="color: var(--secondary); font-weight: 500;">Create one here</a></p>
                        <p class="mt-2"><a href="index.php" class="text-decoration-none" style="color: var(--dark);"><i class="bi bi-arrow-left me-1"></i>Back to Home</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                this.classList.add('text-primary');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                this.classList.remove('text-primary');
            }
        });
    </script>

<?php include 'footer.php'; ?>