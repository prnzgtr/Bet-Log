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
    const email = document.querySelector('#loginModal input[name="email"]').value;
    const password = document.querySelector('#loginModal input[name="password"]').value;
    
    if (!email || !password) {
        alert('Please fill in all fields');
        return false;
    }
    
    return true;
}

function validateRegisterForm() {
    const username = document.querySelector('#registerModal input[name="username"]').value;
    const email = document.querySelector('#registerModal input[name="email"]').value;
    const phone = document.querySelector('#registerModal input[name="phone"]').value;
    const password = document.querySelector('#registerModal input[name="password"]').value;
    
    if (!username || !email || !phone || !password) {
        alert('Please fill in all fields');
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return false;
    }
    
    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Active Menu Item
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.sidebar-menu a');
    
    menuItems.forEach(item => {
        if (item.getAttribute('href') === currentPage) {
            item.classList.add('active');
        }
    });
});

// Profile Image 
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('profileImagePreview').src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});
