            </div><!-- .content-wrapper -->
            
            <footer class="admin-footer">
                <p>&copy; <?php echo date('Y'); ?> SDO CTS - San Pedro Division Office Complaint Tracking System</p>
            </footer>
        </main>
    </div>

    <script src="/SDO-cts/admin/assets/js/admin.js"></script>
    <script>
    // Sidebar toggle - inline backup to ensure it works
    (function() {
        const sidebar = document.getElementById('sidebar');
        const adminLayout = document.querySelector('.admin-layout');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const desktopToggle = document.getElementById('desktopSidebarToggle');
        const mobileToggle = document.getElementById('mobileMenuToggle');
        
        if (!sidebar) {
            console.error('Sidebar element not found');
            return;
        }
        
        // Restore sidebar state from localStorage
        const savedState = localStorage.getItem('sidebarCollapsed') === 'true';
        if (savedState && window.innerWidth >= 992) {
            sidebar.classList.add('collapsed');
            if (adminLayout) adminLayout.classList.add('sidebar-collapsed');
        }
        
        // Function to toggle sidebar
        function toggleSidebar(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            const isCollapsed = sidebar.classList.toggle('collapsed');
            if (adminLayout) adminLayout.classList.toggle('sidebar-collapsed', isCollapsed);
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            console.log('Sidebar toggled:', isCollapsed ? 'collapsed' : 'expanded');
        }
        
        // Sidebar header toggle button
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        // Desktop top bar toggle button
        if (desktopToggle) {
            desktopToggle.addEventListener('click', toggleSidebar);
        }
        
        // Mobile menu toggle
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('open');
            });
        }
        
        console.log('Sidebar toggle initialized');
    })();
    </script>
</body>
</html>

