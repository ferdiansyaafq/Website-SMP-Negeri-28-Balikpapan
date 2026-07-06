/**
 * Portal Login - JavaScript
 * Menangani role switching, form validation, dan interaksi UI
 */

document.addEventListener('DOMContentLoaded', function() {
    const roleSwitch = document.getElementById('roleSwitch');
    const portalLoginForm = document.getElementById('portalLoginForm');
    const roleInput = document.getElementById('roleInput');
    const roleTitle = document.getElementById('roleTitle');
    const roleSubtitle = document.getElementById('roleSubtitle');
    const identifierLabel = document.getElementById('identifierLabel');
    const identifierInput = document.getElementById('identifier');
    const identifierHelp = document.getElementById('identifierHelp');
    const passwordGroup = document.getElementById('passwordGroup');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitLoader = document.getElementById('submitLoader');

    // Role configuration
    if (typeof window.portalRoleConfig === 'undefined') {
        console.error('Portal role config not loaded');
        return;
    }

    // Handle role switch
    if (roleSwitch) {
        const roleTabs = roleSwitch.querySelectorAll('.role-tab');
        roleTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const newRole = this.dataset.role;
                switchRole(newRole);
            });
        });
    }

    function switchRole(role) {
        // Update active tab
        if (roleSwitch) {
            const tabs = roleSwitch.querySelectorAll('.role-tab');
            tabs.forEach(tab => {
                tab.classList.toggle('active', tab.dataset.role === role);
            });
        }

        // Update form configuration
        const config = window.portalRoleConfig[role];
        if (!config) return;

        roleInput.value = role;
        roleTitle.textContent = config.title;
        roleSubtitle.textContent = config.subtitle;
        identifierLabel.textContent = config.label;
        identifierInput.placeholder = config.placeholder;
        identifierHelp.textContent = config.helper;
        submitText.textContent = config.button;

        // Show password field for all roles
        if (passwordGroup) passwordGroup.classList.remove('hidden');
        if (passwordInput) {
            passwordInput.required = true;
            passwordInput.placeholder = config.passwordPlaceholder || 'Masukkan password';
            passwordInput.value = '';
        }

        // Clear previous values
        identifierInput.value = '';
        identifierInput.focus();

        // Clear error if exists
        const formAlert = document.querySelector('.form-alert');
        if (formAlert) {
            formAlert.style.display = 'none';
        }
    }

    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            // Update icon
            const icon = this.querySelector('svg');
            if (isPassword) {
                icon.innerHTML = '<path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5.34" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M1 1l22 22M9 9h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
            } else {
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>';
            }
        });
    }

    // Form submission
    if (portalLoginForm) {
        portalLoginForm.addEventListener('submit', function(e) {
            // Validate
            const identifier = identifierInput.value.trim();
            
            if (!identifier) {
                showError('Silakan masukkan identifier terlebih dahulu');
                e.preventDefault();
                return;
            }

            const password = passwordInput.value.trim();
            if (!password) {
                showError('Silakan masukkan password');
                e.preventDefault();
                return;
            }

            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        });
    }

    function showError(message) {
        // Clear existing alert
        let formAlert = document.querySelector('.form-alert');
        if (formAlert) {
            formAlert.remove();
        }

        // Create new alert
        formAlert = document.createElement('div');
        formAlert.className = 'form-alert';
        formAlert.innerHTML = `<span>${message}</span>`;
        
        if (roleSwitch && roleSwitch.parentNode) {
            roleSwitch.parentNode.insertBefore(formAlert, roleSwitch.nextSibling);
        } else if (portalLoginForm && portalLoginForm.parentNode) {
            portalLoginForm.parentNode.insertBefore(formAlert, portalLoginForm);
        }

        // Auto remove after 5 seconds
        setTimeout(() => {
            formAlert.style.display = 'none';
        }, 5000);
    }

    // Clear error on input
    if (identifierInput) {
        identifierInput.addEventListener('focus', function() {
            const formAlert = document.querySelector('.form-alert');
            if (formAlert) {
                formAlert.style.display = 'none';
            }
        });
    }

    // Prevent paste in identifier field if needed (optional)
    // identifierInput.addEventListener('paste', (e) => {
    //     e.preventDefault();
    //     showError('Paste tidak diizinkan');
    // });

    // Enter key to submit
    if (identifierInput) {
        identifierInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                portalLoginForm.submit();
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                portalLoginForm.submit();
            }
        });
    }

    // Auto-focus identifier field on load
    setTimeout(() => {
        identifierInput.focus();
    }, 100);
});

/**
 * Utility: Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 4000);
}
