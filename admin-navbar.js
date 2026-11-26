// Toggle mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.admin-mobile-menu-btn');
    const mobileMenu = document.querySelector('.admin-mobile-menu');
    const mobileOverlay = document.querySelector('.admin-mobile-overlay');
    
    if (mobileMenuBtn && mobileMenu) {
      mobileMenuBtn.addEventListener('click', function() {
        mobileMenu.classList.toggle('show');
        mobileOverlay.style.display = mobileMenu.classList.contains('show') ? 'block' : 'none';
      });
      
      mobileOverlay.addEventListener('click', function() {
        mobileMenu.classList.remove('show');
        this.style.display = 'none';
      });
    }
  
    // Update datetime
    function updateAdminDateTime() {
      const now = new Date();
      const timeOptions = { hour: '2-digit', minute: '2-digit' };
      const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
      
      // Update desktop time
      const timeElement = document.getElementById('adminCurrentTime');
      const dateElement = document.getElementById('adminCurrentDate');
      
      if (timeElement) timeElement.textContent = now.toLocaleTimeString('en-US', timeOptions);
      if (dateElement) dateElement.textContent = now.toLocaleDateString('en-US', dateOptions);
      
      // Update mobile date
      const mobileDateElements = document.querySelectorAll('.admin-mobile-date span');
      const mobileDateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      
      mobileDateElements.forEach(el => {
        el.textContent = now.toLocaleDateString('en-US', mobileDateOptions);
      });
    }
  
    // Theme toggle functionality
    const themeToggle = document.querySelector('.admin-theme-toggle');
    if (themeToggle) {
      themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-theme');
        
        // Toggle moon/sun icon
        const icon = this.querySelector('i');
        if (icon) {
          icon.classList.toggle('fa-sun');
          icon.classList.toggle('fa-moon');
        }
        
        // Update button text if it exists
        const textSpan = this.querySelector('span');
        if (textSpan) {
          textSpan.textContent = document.body.classList.contains('dark-theme') ? 'Light Mode' : 'Dark Mode';
        }
      });
    }
  
    // Initialize datetime and update every minute
    updateAdminDateTime();
    setInterval(updateAdminDateTime, 60000);
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.matches('.admin-nav-dropbtn, .admin-nav-dropbtn *, .admin-user-btn, .admin-user-btn *')) {
        const dropdowns = document.querySelectorAll('.admin-nav-dropdown-content, .admin-user-dropdown-content');
        dropdowns.forEach(dropdown => {
          dropdown.style.display = 'none';
        });
      }
    });
  });