<!-- Login Modal -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('loginModal')">&times;</span>
        <h2>Login to Your Account</h2>
        <form method="POST" action="pages/login_process.php" onsubmit="return validateLoginForm()">
            <!-- MDTM Assign1: Module 6 - CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="form-group">
                <label for="loginEmail">Email</label>
                <input type="email" id="loginEmail" name="email" required placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" required placeholder="Enter your password">
            </div>
            
            <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <!-- MDTM Assign3: Module 9 - Remember Me Checkbox -->
                <input type="checkbox" id="rememberMe" name="remember" style="width: auto; margin: 0;">
                <label for="rememberMe" style="margin: 0; cursor: pointer;">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="pages/forgot-password.php" style="color: var(--primary-gold); text-decoration: none;">Forgot Password?</a>
            </div>
            
            <div style="text-align: center; margin-top: 15px;">
                <p>Don't have an account? <a href="#" onclick="closeModal('loginModal'); openModal('registerModal'); return false;" style="color: var(--primary-gold);">Register here</a></p>
            </div>
        </form>
    </div>
</div>

<!-- Register Modal -->
<div id="registerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('registerModal')">&times;</span>
        <h2>Create New Account</h2>
        <form method="POST" action="pages/register_process.php" onsubmit="return validateRegisterForm()">
            <!-- MDTM Assign1: Module 6 - CSRF Protection -->
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="form-group">
                <label for="registerUsername">Username <span style="color: red;">*</span></label>
                <input type="text" 
                       id="registerUsername" 
                       name="username" 
                       required 
                       placeholder="Choose a username"
                       minlength="3"
                       maxlength="50"
                       pattern="[a-zA-Z0-9_]+">
                <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                    3-50 characters, letters, numbers and underscores only
                </small>
            </div>
            
            <div class="form-group">
                <label for="registerEmail">Email <span style="color: red;">*</span></label>
                <input type="email" 
                       id="registerEmail" 
                       name="email" 
                       required 
                       placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label for="registerPhone">Phone Number <span style="color: red;">*</span></label>
                <input type="tel" 
                       id="registerPhone" 
                       name="phone" 
                       required 
                       placeholder="Enter your phone number"
                       pattern="[\+]?[0-9\s\-\(\)]+">
                <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                    Format: +63 917 123 4567 or (02) 1234-5678
                </small>
            </div>
            
            <div class="form-group">
                <label for="registerPassword">Password <span style="color: red;">*</span></label>
                <input type="password" 
                       id="registerPassword" 
                       name="password" 
                       required 
                       placeholder="Create a password"
                       minlength="8">
                <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                    Minimum 8 characters with uppercase, lowercase, number and special character
                </small>
            </div>
            
            <div class="form-group">
                <label for="registerConfirmPassword">Confirm Password <span style="color: red;">*</span></label>
                <input type="password" 
                       id="registerConfirmPassword" 
                       name="confirm_password" 
                       required 
                       placeholder="Re-enter your password">
            </div>
            
            <div class="form-group" style="display: flex; align-items: flex-start; gap: 8px;">
                <input type="checkbox" id="agreeTerms" required style="width: auto; margin-top: 4px;">
                <label for="agreeTerms" style="margin: 0; cursor: pointer; font-size: 14px;">
                    I confirm that I am at least 18 years old and agree to the Terms of Service and Privacy Policy
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Register</button>
            
            <div style="text-align: center; margin-top: 15px;">
                <p>Already have an account? <a href="#" onclick="closeModal('registerModal'); openModal('loginModal'); return false;" style="color: var(--primary-gold);">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<script>
// Enhanced form validation - MDTM Assign1: Module 6
function validateLoginForm() {
    const email = document.querySelector('#loginModal input[name="email"]').value.trim();
    const password = document.querySelector('#loginModal input[name="password"]').value;
    
    if (!email || !password) {
        alert('Please fill in all fields');
        return false;
    }
    
    // Email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }
    
    return true;
}

function validateRegisterForm() {
    const username = document.querySelector('#registerModal input[name="username"]').value.trim();
    const email = document.querySelector('#registerModal input[name="email"]').value.trim();
    const phone = document.querySelector('#registerModal input[name="phone"]').value.trim();
    const password = document.querySelector('#registerModal input[name="password"]').value;
    const confirmPassword = document.querySelector('#registerModal input[name="confirm_password"]').value;
    const agreeTerms = document.getElementById('agreeTerms').checked;
    
    // Check all fields are filled
    if (!username || !email || !phone || !password || !confirmPassword) {
        alert('Please fill in all required fields');
        return false;
    }
    
    // Username validation
    const usernameRegex = /^[a-zA-Z0-9_]{3,50}$/;
    if (!usernameRegex.test(username)) {
        alert('Username must be 3-50 characters and contain only letters, numbers, and underscores');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }
    
    // Phone validation
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]+$/;
    if (!phoneRegex.test(phone)) {
        alert('Please enter a valid phone number');
        return false;
    }
    
    // Password strength validation - MDTM Assign1: Module 6
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
        alert('Password must contain at least one special character');
        return false;
    }
    
    // Confirm password match
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }
    
    // Terms agreement
    if (!agreeTerms) {
        alert('You must agree to the Terms of Service to register');
        return false;
    }
    
    return true;
}

// Show password strength indicator
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('registerPassword');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            console.log('Password strength:', strength);
        });
    }
});
</script>