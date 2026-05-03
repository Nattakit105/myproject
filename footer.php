<footer class="app-footer text-center mt-5 mb-4">
        <p>MyProject Dormitory Billing System</p>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const toggle = document.getElementById('themeToggle');
            const icon = document.getElementById('themeToggleIcon');

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('appTheme', theme);
                if (!icon) return;
                icon.className = theme === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
            }

            applyTheme(localStorage.getItem('appTheme') || 'dark');

            if (toggle) {
                toggle.addEventListener('click', function() {
                    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                    applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
                });
            }
        })();
    </script>
</body>
</html>
