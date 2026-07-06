/**
 * Admin Login - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const adminLoginForm = document.getElementById('adminLoginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const loginButton = document.getElementById('loginButton');
    const rememberMe = document.getElementById('rememberMe');

    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            // Update icon
            const icon = this.querySelector('svg');
            if (isPassword) {
                // Hide icon
                icon.innerHTML = '<path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5.34M6 6c0-.55.45-1 1-1h8c.55 0 1 .45 1 1m-4 4v3m0 0v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
            } else {
                // Show icon
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>';
            }
        });
    }

    // Handle form submission
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', function(e) {
            // Validate
            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            if (!email) {
                showAlert('Silakan masukkan email', 'error');
                e.preventDefault();
                return;
            }

            if (!isValidEmail(email)) {
                showAlert('Format email tidak valid', 'error');
                e.preventDefault();
                return;
            }

            if (!password) {
                showAlert('Silakan masukkan password', 'error');
                e.preventDefault();
                return;
            }

            if (password.length < 6) {
                showAlert('Password minimal 6 karakter', 'error');
                e.preventDefault();
                return;
            }

            // Save remember me preference
            if (rememberMe && rememberMe.checked) {
                localStorage.setItem('adminEmail', email);
            } else {
                localStorage.removeItem('adminEmail');
            }

            // Show loading state
            if (loginButton) {
                loginButton.classList.add('loading');
                loginButton.disabled = true;
            }
        });
    }

    // Load saved email if exists
    window.addEventListener('load', function() {
        const savedEmail = localStorage.getItem('adminEmail');
        if (savedEmail && emailInput) {
            emailInput.value = savedEmail;
            if (rememberMe) {
                rememberMe.checked = true;
            }
        }
    });

    // Email input validation on blur
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });

        emailInput.addEventListener('focus', function() {
            this.classList.remove('error');
        });
    }

    // Password strength indicator
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrength(strength);
        });
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Calculate password strength
     */
    function calculatePasswordStrength(password) {
        let strength = 0;

        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        if (strength < 2) return 'weak';
        if (strength < 4) return 'medium';
        return 'strong';
    }

    /**
     * Update password strength indicator
     */
    function updatePasswordStrength(strength) {
        // Remove previous strength indicator
        const existingIndicator = document.querySelector('.password-strength');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        // Create new indicator (optional)
        // Bisa ditambahkan jika diperlukan
    }

    /**
     * Show alert message
     */
    function showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        
        const iconMap = {
            success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 20h20L12 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        };

        alert.innerHTML = `
            <span class="alert-icon">${iconMap[type] || iconMap.info}</span>
            <span>${message}</span>
        `;

        // Insert at top of form
        const adminForm = document.querySelector('.admin-form-section');
        if (adminForm) {
            adminForm.insertBefore(alert, adminForm.firstChild);
        }

        // Auto remove after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }

    // Enter key to submit
    if (emailInput) {
        emailInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                passwordInput.focus();
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && adminLoginForm) {
                adminLoginForm.submit();
            }
        });
    }

    // Auto-focus email field
    setTimeout(() => {
        if (emailInput) emailInput.focus();
    }, 100);
});

/**
 * Utility functions
 */

/**
 * Check if user session is valid
 */
function isSessionValid() {
    // Check session via AJAX
    fetch('check-session.php', { method: 'HEAD' })
        .then(response => {
            if (!response.ok) {
                // Session invalid, redirect to login
                window.location.href = 'login.php';
            }
        })
        .catch(err => console.log('Session check failed:', err));
}

// Check session periodically
setInterval(isSessionValid, 5 * 60 * 1000); // Every 5 minutes
