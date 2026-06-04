    <footer class="main-footer" style="background: #fff; border-top: 1px solid #eee; padding: 2rem 0; text-align: center; color: #777; font-size: 0.9rem; margin-top: 2rem; width: 100%;">
        <p>&copy; <?php echo date('Y'); ?> Murang'a County Dairy Management System. All rights reserved.</p>
    </footer>

        </div> <!-- Closing main-content -->
    </div> <!-- Closing attendant-layout -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                    if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
                });

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        if (sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
                            sidebar.classList.remove('active');
                            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                        }
                    }
                });

                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        sidebarOverlay.classList.remove('active');
                    });
                }
            }
        });
    </script>
</body>
</html>