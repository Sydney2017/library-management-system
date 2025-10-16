<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>

<?php include 'header-auth.php'; ?>

    <div class="container" style="padding-top: 60px; padding-bottom: 50px;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="auth-card">
                    <div class="auth-header text-center mb-4">
                        <div class="auth-logo">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h2 class="auth-title">Create Account</h2>
                        <p class="auth-subtitle">Join our library community</p>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required 
                                           placeholder="Enter first name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required 
                                           placeholder="Enter last name">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required 
                                   placeholder="Choose a username">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="Enter your email">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" id="password" name="password" required 
                                       placeholder="Create a password">
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y border-0 text-secondary" id="togglePassword" style="background: none;">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Must be at least 8 characters long</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Enter phone number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="dob">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="Enter your address"></textarea>
                        </div>

                        <button type="submit" name="register" class="btn btn-primary w-100 py-3" style="border-radius: 50px; font-weight: 500;">
                            <i class="bi bi-person-plus me-2"></i>Create Account
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? <a href="login.php" class="text-decoration-none" style="color: var(--secondary); font-weight: 500;">Sign in here</a></p>
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