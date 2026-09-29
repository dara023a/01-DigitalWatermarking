// Common utilities for Spectra Watermarking

document.addEventListener('DOMContentLoaded', function() {
    // Mobile navigation toggle
    const burgerBtn = document.getElementById('burgerBtn');
    const navlinks = document.getElementById('navlinks');
    
    if (burgerBtn && navlinks) {
        burgerBtn.addEventListener('click', function() {
            navlinks.classList.toggle('open');
        });
    }

    // Accordion functionality
    const accBtns = document.querySelectorAll('.acc-btn');
    accBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const panel = this.nextElementSibling;
            const plus = this.querySelector('.plus');
            
            if (panel.style.maxHeight) {
                panel.style.maxHeight = null;
                plus.textContent = '+';
            } else {
                panel.style.maxHeight = panel.scrollHeight + 'px';
                plus.textContent = '−';
            }
        });
    });

    // Tab functionality
    const tabBtns = document.querySelectorAll('.tabbtn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const target = this.dataset.target;
            
            // Remove active class from all tabs and panels
            document.querySelectorAll('.tabbtn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tabpanel').forEach(p => p.style.display = 'none');
            
            // Add active class to clicked tab and show corresponding panel
            this.classList.add('active');
            const panel = document.getElementById(target);
            if (panel) panel.style.display = 'block';
        });
    });
});

// Toggle password visibility
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.style.color = isPassword ? '#2F6FEF' : '';
}
