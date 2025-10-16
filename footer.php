    <!-- Footer -->
    <footer class="footer" style="background: var(--dark); color: white; padding: 40px 0;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5>Library Management System</h5>
                    <p>Modern, efficient, and user-friendly library management solution for the digital age.</p>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php#features" class="text-light text-decoration-none">Features</a></li>
                        <li><a href="index.php#about" class="text-light text-decoration-none">About</a></li>
                        <li><a href="index.php#testimonials" class="text-light text-decoration-none">Testimonials</a></li>
                        <li><a href="login.php" class="text-light text-decoration-none">Login</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5>Contact Us</h5>
                    <p><i class="bi bi-envelope me-2"></i> DEVELOPED BY SYDNEY MOAGI</p>
                    <p><i class="bi bi-envelope me-2"></i> info@librarysystem.com</p>
                    <p><i class="bi bi-telephone me-2"></i> +27789591500</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <hr class="border-secondary">
            
            <div class="text-center">
                <p>&copy; 2025 Library Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background change on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 100) {
                navbar.style.background = 'rgba(255,255,255,0.98) !important';
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.background = 'rgba(255,255,255,0.95) !important';
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            }
        });
    </script>
</body>
</html>