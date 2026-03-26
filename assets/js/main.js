// Modal Functionality
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Form Validation
function validateLoginForm() {
    const email    = document.querySelector('#loginModal input[name="email"]').value;
    const password = document.querySelector('#loginModal input[name="password"]').value;

    if (!email || !password) {
        alert('Please fill in all fields');
        return false;
    }
    return true;
}

function validateRegisterForm() {
    const username = document.querySelector('#registerModal input[name="username"]').value;
    const email    = document.querySelector('#registerModal input[name="email"]').value;
    const phone    = document.querySelector('#registerModal input[name="phone"]').value;
    const password = document.querySelector('#registerModal input[name="password"]').value;

    if (!username || !email || !phone || !password) {
        alert('Please fill in all fields');
        return false;
    }

    // FIX: Password rules now match server-side (register_process.php)
    // Previously only checked length >= 6, server requires 8+ with complexity
    if (password.length < 8) {
        alert('Password must be at least 8 characters long');
        return false;
    }
    if (!/[A-Z]/.test(password)) {
        alert('Password must contain at least one uppercase letter');
        return false;
    }
    if (!/[a-z]/.test(password)) {
        alert('Password must contain at least one lowercase letter');
        return false;
    }
    if (!/[0-9]/.test(password)) {
        alert('Password must contain at least one number');
        return false;
    }
    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        alert('Password must contain at least one special character (!@#$%^&* etc.)');
        return false;
    }

    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Active Sidebar Menu Item
// FIX: Removed - active state is now handled server-side in sidebar.php
// (JS-based active detection was unreliable with PHP routing)

// FIX: Mobile menu toggle - was missing, menu button did nothing on mobile
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const navMenu   = document.querySelector('.nav-menu');

    if (toggleBtn && navMenu) {
        toggleBtn.addEventListener('click', function () {
            navMenu.classList.toggle('active');
            // Swap icon between hamburger and X
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });

        // Close menu when a nav link is clicked
        navMenu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                navMenu.classList.remove('active');
                const icon = toggleBtn.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-bars');
                    icon.classList.remove('fa-times');
                }
            });
        });
    }
});

// Profile Image Preview
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Smooth Scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});