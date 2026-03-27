<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('loginModal')">&times;</span>
        <h2>Login to Your Account</h2>

        <!-- Inline error box -->
        <div id="loginError" style="display:none;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.4);border-radius:10px;padding:12px 16px;color:#f87171;font-size:13px;margin-bottom:16px;display:none;align-items:center;gap:8px;">
            <i class="fas fa-exclamation-circle"></i>
            <span id="loginErrorMsg"></span>
        </div>

        <!-- Inline success box -->
        <div id="loginSuccess" style="display:none;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.4);border-radius:10px;padding:12px 16px;color:#34D399;font-size:13px;margin-bottom:16px;align-items:center;gap:8px;">
            <i class="fas fa-check-circle"></i>
            <span id="loginSuccessMsg"></span>
        </div>

        <form id="loginForm" onsubmit="handleLogin(event)">
            <input type="hidden" id="loginCsrf" value="<?php echo generate_csrf_token(); ?>">

            <div class="form-group">
                <label for="loginEmail">Email</label>
                <input type="email" id="loginEmail" name="email" required placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" required placeholder="Enter your password">
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="rememberMe" name="remember" style="width:auto;margin:0;">
                <label for="rememberMe" style="margin:0;cursor:pointer;">Remember me for 30 days</label>
            </div>

            <button type="submit" id="loginBtn" class="btn btn-primary">Login</button>

            <div style="text-align:center;margin-top:15px;">
                <a href="#" onclick="closeModal('loginModal'); openModal('forgotModal'); return false;"
                   style="color:var(--primary-pink);text-decoration:none;">Forgot Password?</a>
            </div>

            <div style="text-align:center;margin-top:15px;">
                <p>Don't have an account?
                    <a href="#" onclick="closeModal('loginModal'); openModal('registerModal'); return false;"
                       style="color:var(--primary-pink);">Register here</a>
                </p>
            </div>
        </form>
    </div>
</div>


<div id="registerModal" class="modal">
    <div class="modal-content reg-modal-content">
        <span class="close reg-close" onclick="closeModal('registerModal')">&times;</span>

        <!-- Header -->
        <div class="reg-header">
            <div class="reg-logo-ring">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 class="reg-title">Create Account</h2>
            <p class="reg-subtitle">Join Bet-Log and gamble responsibly</p>
        </div>

        <!-- Step Indicators -->
        <div class="reg-steps">
            <div class="reg-step active" id="regStep1Ind" onclick="regGoToStep(1)">
                <div class="reg-step-dot"><span>1</span></div>
                <div class="reg-step-label">Account</div>
            </div>
            <div class="reg-step-line" id="regLine1"></div>
            <div class="reg-step" id="regStep2Ind" onclick="regGoToStep(2)">
                <div class="reg-step-dot"><span>2</span></div>
                <div class="reg-step-label">Security</div>
            </div>
            <div class="reg-step-line" id="regLine2"></div>
            <div class="reg-step" id="regStep3Ind" onclick="regGoToStep(3)">
                <div class="reg-step-dot"><span>3</span></div>
                <div class="reg-step-label">Confirm</div>
            </div>
        </div>

        <!-- Inline error box for register -->
        <div id="registerError" style="display:none;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.4);border-radius:10px;padding:12px 16px;color:#f87171;font-size:13px;margin:0 36px 12px;align-items:center;gap:8px;">
            <i class="fas fa-exclamation-circle" style="flex-shrink:0;"></i>
            <span id="registerErrorMsg"></span>
        </div>

        <form id="registerForm" onsubmit="handleRegister(event)">
            <input type="hidden" id="registerCsrf" value="<?php echo generate_csrf_token(); ?>">

            <!-- ── Step 1: Account Info ── -->
            <div class="reg-step-panel" id="regPanel1">
                <div class="reg-field-group">
                    <div class="reg-input-row">
                        <!-- Username -->
                        <div class="form-group reg-full">
                            <label for="registerUsername" class="reg-label">
                                <i class="fas fa-at reg-label-icon"></i> Username <span class="reg-req">*</span>
                            </label>
                            <div class="reg-input-wrap">
                                <input type="text"
                                       id="registerUsername"
                                       name="username"
                                       required
                                       placeholder="e.g. lucky_gamer67"
                                       minlength="3"
                                       maxlength="50"
                                       pattern="[a-zA-Z0-9_]+"
                                       oninput="regValidateUsername(this)"
                                       autocomplete="username">
                                <span class="reg-field-status" id="usernameStatus"></span>
                            </div>
                            <small class="reg-hint">3–50 chars · letters, numbers, underscores</small>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="registerEmail" class="reg-label">
                            <i class="fas fa-envelope reg-label-icon"></i> Email <span class="reg-req">*</span>
                        </label>
                        <div class="reg-input-wrap">
                            <input type="email"
                                   id="registerEmail"
                                   name="email"
                                   required
                                   placeholder="your@email.com"
                                   oninput="regValidateEmail(this)"
                                   autocomplete="email">
                            <span class="reg-field-status" id="emailStatus"></span>
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label for="registerPhone" class="reg-label">
                            <i class="fas fa-phone reg-label-icon"></i> Phone Number <span class="reg-req">*</span>
                        </label>
                        <div class="reg-input-wrap">
                            <input type="tel"
                                   id="registerPhone"
                                   name="phone"
                                   required
                                   placeholder="+63 917 123 4567"
                                   pattern="[\+]?[0-9\s\-\(\)]+"
                                   oninput="regValidatePhone(this)"
                                   autocomplete="tel">
                            <span class="reg-field-status" id="phoneStatus"></span>
                        </div>
                        <small class="reg-hint">Format: +63 917 123 4567 or (02) 1234-5678</small>
                    </div>
                </div>

                <button type="button" class="btn btn-primary reg-next-btn" onclick="regNextStep(1)">
                    Continue <i class="fas fa-arrow-right" style="font-size:13px;"></i>
                </button>
            </div>

            <!-- ── Step 2: Password + Security ── -->
            <div class="reg-step-panel" id="regPanel2" style="display:none;">

                <!-- Password -->
                <div class="form-group">
                    <label for="registerPassword" class="reg-label">
                        <i class="fas fa-lock reg-label-icon"></i> Password <span class="reg-req">*</span>
                    </label>
                    <div class="reg-input-wrap">
                        <input type="password"
                               id="registerPassword"
                               name="password"
                               required
                               placeholder="Create a strong password"
                               minlength="8"
                               oninput="regStrength(this.value); regMatchCheck();"
                               style="padding-right:44px;"
                               autocomplete="new-password">
                        <button type="button" onclick="regToggle('registerPassword', this)" class="reg-eye-btn">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <!-- Strength bar -->
                    <div class="reg-str-track">
                        <div id="regStrBar" class="reg-str-fill"></div>
                    </div>
                    <!-- Requirement chips -->
                    <div class="reg-reqs">
                        <span class="reg-req-chip" id="rreq-len"><i class="fas fa-circle"></i> 8+ chars</span>
                        <span class="reg-req-chip" id="rreq-upper"><i class="fas fa-circle"></i> Uppercase</span>
                        <span class="reg-req-chip" id="rreq-lower"><i class="fas fa-circle"></i> Lowercase</span>
                        <span class="reg-req-chip" id="rreq-num"><i class="fas fa-circle"></i> Number</span>
                        <span class="reg-req-chip" id="rreq-sym"><i class="fas fa-circle"></i> Symbol</span>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="registerConfirmPassword" class="reg-label">
                        <i class="fas fa-lock-open reg-label-icon"></i> Confirm Password <span class="reg-req">*</span>
                    </label>
                    <div class="reg-input-wrap">
                        <input type="password"
                               id="registerConfirmPassword"
                               name="confirm_password"
                               required
                               placeholder="Re-enter your password"
                               oninput="regMatchCheck()"
                               style="padding-right:44px;"
                               autocomplete="new-password">
                        <button type="button" onclick="regToggle('registerConfirmPassword', this)" class="reg-eye-btn">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small id="regMatchHint" class="reg-hint"></small>
                </div>

                <!-- Security Question box -->
                <div class="reg-security-box">
                    <div class="reg-security-header">
                        <i class="fas fa-shield-alt"></i>
                        <span>Account Recovery</span>
                        <span class="reg-security-badge">Required</span>
                    </div>
                    <p class="reg-security-desc">Used to verify your identity if you forget your password.</p>

                    <div class="form-group" style="margin-bottom:14px;">
                        <label for="registerSecurityQuestion" class="reg-label">Security Question <span class="reg-req">*</span></label>
                        <select id="registerSecurityQuestion" name="security_question" required class="reg-select">
                            <option value="" disabled selected>— Choose a question —</option>
                            <optgroup label="Childhood &amp; Family">
                                <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="What was the name of your childhood best friend?">What was the name of your childhood best friend?</option>
                                <option value="What city were you born in?">What city were you born in?</option>
                                <option value="What is the name of the street you grew up on?">What is the name of the street you grew up on?</option>
                                <option value="What was the first school you attended?">What was the first school you attended?</option>
                            </optgroup>
                            <optgroup label="Favourites">
                                <option value="What is your favourite movie?">What is your favourite movie?</option>
                                <option value="What is your favourite book?">What is your favourite book?</option>
                                <option value="What is your favourite sports team?">What is your favourite sports team?</option>
                                <option value="What is your favourite food?">What is your favourite food?</option>
                                <option value="What was the make of your first car?">What was the make of your first car?</option>
                            </optgroup>
                            <optgroup label="Personal Milestones">
                                <option value="In what city did you meet your spouse or partner?">In what city did you meet your spouse or partner?</option>
                                <option value="What is the middle name of your oldest sibling?">What is the middle name of your oldest sibling?</option>
                                <option value="What was the name of your first employer?">What was the name of your first employer?</option>
                                <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="registerSecurityAnswer" class="reg-label">Your Answer <span class="reg-req">*</span></label>
                        <input type="text"
                               id="registerSecurityAnswer"
                               name="security_answer"
                               required
                               placeholder="Enter your answer (case-insensitive)"
                               autocomplete="off">
                    </div>
                </div>

                <div class="reg-step-btns">
                    <button type="button" class="reg-back-btn" onclick="regGoToStep(1)">
                        <i class="fas fa-arrow-left" style="font-size:12px;"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary reg-next-half" onclick="regNextStep(2)">
                        Continue <i class="fas fa-arrow-right" style="font-size:13px;"></i>
                    </button>
                </div>
            </div>

            <!-- ── Step 3: Review + Confirm ── -->
            <div class="reg-step-panel" id="regPanel3" style="display:none;">
                <!-- Summary card -->
                <div class="reg-review-card">
                    <div class="reg-review-title">
                        <i class="fas fa-clipboard-check"></i> Review your details
                    </div>
                    <div class="reg-review-grid">
                        <div class="reg-review-row">
                            <span class="reg-review-label">Username</span>
                            <span class="reg-review-val" id="rev-username">—</span>
                        </div>
                        <div class="reg-review-row">
                            <span class="reg-review-label">Email</span>
                            <span class="reg-review-val" id="rev-email">—</span>
                        </div>
                        <div class="reg-review-row">
                            <span class="reg-review-label">Phone</span>
                            <span class="reg-review-val" id="rev-phone">—</span>
                        </div>
                        <div class="reg-review-row">
                            <span class="reg-review-label">Password</span>
                            <span class="reg-review-val" id="rev-password">—</span>
                        </div>
                        <div class="reg-review-row">
                            <span class="reg-review-label">Security Q</span>
                            <span class="reg-review-val reg-review-q" id="rev-question">—</span>
                        </div>
                    </div>
                </div>

                <!-- Age + Terms -->
                <div class="reg-check-row">
                    <label class="reg-check-label">
                        <input type="checkbox" id="agreeTerms" required class="reg-checkbox">
                        <span class="reg-check-custom"></span>
                        <span>I confirm I am at least <strong>18 years old</strong> and agree to the <a href="#" style="color:var(--primary-pink);">Terms of Service</a> and <a href="#" style="color:var(--primary-pink);">Privacy Policy</a></span>
                    </label>
                </div>

                <!-- Responsible gambling notice -->
                <div class="reg-notice">
                    <i class="fas fa-shield-alt reg-notice-icon"></i>
                    <span>Bet-Log is committed to responsible gambling. Use our limit tools to stay in control.</span>
                </div>

                <div class="reg-step-btns">
                    <button type="button" class="reg-back-btn" onclick="regGoToStep(2)">
                        <i class="fas fa-arrow-left" style="font-size:12px;"></i> Back
                    </button>
                    <button type="submit" class="btn btn-primary reg-next-half reg-submit-btn">
                        <i class="fas fa-check" style="font-size:13px;"></i> Create Account
                    </button>
                </div>
            </div>
        </form>

        <div style="text-align:center;margin-top:18px;">
            <p style="color:var(--text-secondary);font-size:13px;">Already have an account?
                <a href="#" onclick="closeModal('registerModal'); openModal('loginModal'); return false;"
                   style="color:var(--primary-pink);font-weight:600;">Login here</a>
            </p>
        </div>
    </div>
</div>


<div id="forgotModal" class="modal">
    <div class="modal-content fp-modal">
        <span class="close fp-close" onclick="closeModal('forgotModal'); fpReset();">&times;</span>

        <!-- Progress dots -->
        <div class="fp-progress">
            <div class="fp-dot active" id="fpDot1"></div>
            <div class="fp-dot" id="fpDot2"></div>
            <div class="fp-dot" id="fpDot3"></div>
        </div>

        <!-- Step 1: Email -->
        <div id="fp-step1" class="fp-step">
            <h2>Forgot Password</h2>
            <p class="fp-sub">Enter your registered email address and we'll verify your identity.</p>
            <div class="form-group">
                <label>Email Address</label>
                <div class="fp-input-wrap">
                    <i class="fas fa-envelope fp-input-icon"></i>
                    <input type="email" id="fpEmail" placeholder="your@email.com" autocomplete="email" class="fp-input">
                </div>
            </div>
            <div id="fp-err1" class="fp-error" style="display:none;"></div>
            <button class="btn-primary fp-btn" id="fpBtn1" onclick="fpStep1()">
                <span>Continue</span> <i class="fas fa-arrow-right"></i>
            </button>
            <div class="fp-footer-link">
                <a href="#" onclick="closeModal('forgotModal'); openModal('loginModal'); return false;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>

        <!-- Step 2: Security Question -->
        <div id="fp-step2" class="fp-step" style="display:none;">
            <h2>Security Question</h2>
            <p class="fp-sub">Answering as <strong id="fpUsername" class="fp-username"></strong></p>
            <div class="fp-question-box">
                <i class="fas fa-question-circle"></i>
                <span id="fpQuestion"></span>
            </div>
            <div class="form-group">
                <label>Your Answer</label>
                <div class="fp-input-wrap">
                    <i class="fas fa-key fp-input-icon"></i>
                    <input type="text" id="fpAnswer" placeholder="Enter your answer" autocomplete="off" class="fp-input">
                </div>
                <small class="fp-hint">Answers are case-insensitive</small>
            </div>
            <div id="fp-err2" class="fp-error" style="display:none;"></div>
            <div id="fp-attempts" class="fp-warn" style="display:none;">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="fp-attempts-msg"></span>
            </div>
            <button class="btn-primary fp-btn" id="fpBtn2" onclick="fpStep2()">
                <span>Verify Answer</span> <i class="fas fa-check"></i>
            </button>
            <div class="fp-footer-link">
                <a href="#" onclick="fpReset(); return false;">
                    <i class="fas fa-redo"></i> Start over
                </a>
            </div>
        </div>

        <!-- Step 3: New Password -->
        <div id="fp-step3" class="fp-step" style="display:none;">
            <h2>New Password</h2>
            <p class="fp-sub">Choose a strong password you haven't used before.</p>
            <div class="form-group">
                <label>New Password</label>
                <div class="fp-input-wrap">
                    <i class="fas fa-lock fp-input-icon"></i>
                    <input type="password" id="fpNewPw" placeholder="New password" class="fp-input" oninput="fpStrength(this.value)">
                    <button type="button" class="fp-eye" onclick="fpToggle('fpNewPw', this)" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="fp-str-track"><div id="fpStrBar"></div></div>
                <small id="fpStrHint" class="fp-hint">Min 8 · Upper · Lower · Number · Symbol</small>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="fp-input-wrap">
                    <i class="fas fa-lock fp-input-icon"></i>
                    <input type="password" id="fpConfPw" placeholder="Re-enter password" class="fp-input" oninput="fpMatchCheck()">
                    <button type="button" class="fp-eye" onclick="fpToggle('fpConfPw', this)" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small id="fpMatchHint" class="fp-hint"></small>
            </div>
            <div class="fp-reqs">
                <div class="fp-req" id="req-len">  <i class="fas fa-circle"></i> At least 8 characters</div>
                <div class="fp-req" id="req-upper"><i class="fas fa-circle"></i> Uppercase letter</div>
                <div class="fp-req" id="req-lower"><i class="fas fa-circle"></i> Lowercase letter</div>
                <div class="fp-req" id="req-num">  <i class="fas fa-circle"></i> Number</div>
                <div class="fp-req" id="req-sym">  <i class="fas fa-circle"></i> Special character</div>
            </div>
            <div id="fp-err3" class="fp-error" style="display:none;"></div>
            <button class="btn-primary fp-btn" id="fpBtn3" onclick="fpStep3()">
                <span>Reset Password</span> <i class="fas fa-save"></i>
            </button>
        </div>

        <!-- Step 4: Success -->
        <div id="fp-step4" class="fp-step" style="display:none;">
            <div class="fp-success-anim">
                <div class="fp-check-circle"><i class="fas fa-check"></i></div>
            </div>
            <h2 class="fp-success-title">Password Reset!</h2>
            <p class="fp-sub">Your password has been updated. You can now log in with your new password.</p>
            <button class="btn-primary fp-btn" onclick="closeModal('forgotModal'); fpReset(); openModal('loginModal');">
                <i class="fas fa-sign-in-alt"></i> <span>Go to Login</span>
            </button>
        </div>

    </div>
</div>
<style>

.reg-modal-content {
    max-width: 500px !important;
    padding: 0 !important;
    overflow: hidden;
}

/* Header */
.reg-header {
    background: rgba(255,27,141,0.06);
    border-bottom: 1px solid rgba(255,27,141,0.15);
    padding: 28px 36px 24px;
    text-align: center;
    position: relative;
}
.reg-header::after {
    content: '';
    position: absolute;
    bottom: -1px; left: 50%; transform: translateX(-50%);
    width: 60px; height: 2px;
    background: var(--gradient-primary);
    border-radius: 2px;
}
.reg-logo-ring {
    width: 54px; height: 54px;
    border-radius: 50%;
    background: rgba(255,27,141,0.12);
    border: 1.5px solid rgba(255,27,141,0.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    color: var(--primary-pink);
    margin: 0 auto 12px;
    
}
.reg-title {
    background: var(--primary-pink);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-size: 22px !important;
    font-weight: 800;
    margin: 0 0 4px !important;
    text-align: center;
}
.reg-subtitle {
    color: var(--text-secondary);
    font-size: 13px;
    margin: 0;
}

.reg-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 36px 0;
    gap: 0;
}
.reg-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: opacity 0.2s;
}
.reg-step:not(.active):not(.done) { opacity: 0.45; pointer-events: none; }
.reg-step-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: 2px solid rgba(255,27,141,0.25);
    background: var(--card-bg);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    color: var(--text-secondary);
    transition: all 0.3s ease;
}
.reg-step.active .reg-step-dot {
    border-color: var(--primary-pink);
    background: var(--primary-pink);
    color: #fff;
    
}
.reg-step.done .reg-step-dot {
    border-color: var(--success);
    background: var(--success);
    color: #fff;
}
.reg-step.done .reg-step-dot span::before {
    content: '✓';
}
.reg-step-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.6px;
}
.reg-step.active .reg-step-label { color: var(--primary-pink); }
.reg-step.done .reg-step-label   { color: var(--success); }
.reg-step-line {
    flex: 1;
    height: 2px;
    background: rgba(255,27,141,0.12);
    margin: 0 8px;
    margin-bottom: 22px;
    border-radius: 1px;
    transition: background 0.3s;
    max-width: 60px;
}
.reg-step-line.done { background: var(--success); }

.reg-step-panel {
    padding: 22px 36px 28px;
    animation: regPanelIn 0.28s ease;
}
@keyframes regPanelIn {
    from { opacity: 0; transform: translateX(16px); }
    to   { opacity: 1; transform: translateX(0); }
}

.reg-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
    color: var(--primary-pink);
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.reg-label-icon { font-size: 11px; opacity: 0.8; }
.reg-req { color: #f87171; font-weight: 700; }
.reg-hint {
    color: var(--text-muted);
    font-size: 11px;
    display: block;
    margin-top: 5px;
}

.reg-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.reg-input-wrap input {
    padding-right: 38px;
}
.reg-field-status {
    position: absolute;
    right: 13px;
    font-size: 13px;
    pointer-events: none;
    transition: all 0.2s;
}
.reg-field-status.ok   { color: var(--success); }
.reg-field-status.err  { color: #f87171; }

.reg-eye-btn {
    position: absolute; right: 12px;
    background: none; border: none;
    color: var(--text-muted); cursor: pointer;
    font-size: 13px; padding: 4px;
    transition: color 0.2s;
}
.reg-eye-btn:hover { color: var(--text-primary); }

.reg-str-track {
    height: 4px;
    background: rgba(255,255,255,0.06);
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
}
.reg-str-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.35s, background 0.35s;
    width: 0;
}

.reg-reqs {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
    margin-bottom: 4px;
}
.reg-req-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    color: var(--text-muted);
    transition: all 0.25s;
}
.reg-req-chip i { font-size: 7px; }
.reg-req-chip.met {
    background: rgba(16,185,129,0.1);
    border-color: rgba(16,185,129,0.3);
    color: #34D399;
}
.reg-req-chip.met i { font-size: 11px; }
.reg-req-chip.met i::before { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }

.reg-security-box {
    border: 1px solid rgba(168,85,247,0.2);
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 20px;
    background: rgba(168,85,247,0.04);
}
.reg-security-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #c084fc;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
}
.reg-security-badge {
    margin-left: auto;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 20px;
    background: rgba(168,85,247,0.15);
    border: 1px solid rgba(168,85,247,0.3);
    color: #c084fc;
    font-weight: 700;
}
.reg-security-desc {
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 14px;
    line-height: 1.5;
}

.reg-select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px !important;
    cursor: pointer;
}
.reg-select optgroup { background: #131827; color: #94a3b8; font-size: 11px; font-weight: 700; }
.reg-select option   { background: #131827; color: #ffffff; font-size: 13.5px; }

.reg-review-card {
    background: rgba(255,27,141,0.04);
    border: 1px solid rgba(255,27,141,0.15);
    border-radius: 12px;
    padding: 16px 18px;
    margin-bottom: 18px;
}
.reg-review-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--primary-pink);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 7px;
}
.reg-review-grid { display: flex; flex-direction: column; gap: 0; }
.reg-review-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    gap: 12px;
}
.reg-review-row:last-child { border-bottom: none; }
.reg-review-label {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
    min-width: 72px;
}
.reg-review-val {
    font-size: 13px;
    color: var(--text-primary);
    font-weight: 600;
    text-align: right;
    word-break: break-all;
}
.reg-review-q {
    font-size: 11.5px;
    color: var(--text-secondary);
    font-weight: 500;
    text-align: right;
    line-height: 1.4;
    max-width: 220px;
}

.reg-check-row {
    margin-bottom: 16px;
}
.reg-check-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.5;
}
.reg-checkbox {
    display: none;
}
.reg-check-custom {
    flex-shrink: 0;
    width: 18px; height: 18px;
    border: 2px solid rgba(255,27,141,0.3);
    border-radius: 5px;
    background: rgba(19,24,39,0.5);
    transition: all 0.2s;
    position: relative;
    margin-top: 1px;
}
.reg-checkbox:checked + .reg-check-custom {
    background: var(--primary-pink);
    border-color: var(--primary-pink);

}
.reg-checkbox:checked + .reg-check-custom::after {
    content: '✓';
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
}

.reg-notice {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: rgba(16,185,129,0.06);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 10px;
    padding: 11px 14px;
    font-size: 12px;
    color: #34D399;
    line-height: 1.5;
    margin-bottom: 20px;
}
.reg-notice-icon { flex-shrink: 0; margin-top: 1px; font-size: 13px; }

.reg-step-btns {
    display: flex;
    gap: 10px;
}
.reg-next-btn {
    width: 100%;
    margin-top: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.reg-next-half {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 0;
}
.reg-back-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 14px 20px;
    background: rgba(148,163,184,0.08);
    border: 1px solid rgba(148,163,184,0.15);
    border-radius: 12px;
    color: var(--text-secondary);
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.reg-back-btn:hover {
    background: rgba(148,163,184,0.15);
    color: var(--text-primary);
}
.reg-submit-btn {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%) !important;
}
.reg-submit-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
}

/* Close button positioning */
.reg-close {
    top: 14px !important;
    right: 16px !important;
    z-index: 10;
}

.fp-modal {
    max-width: 440px !important;
    padding: 36px 36px 32px !important;
    position: relative;
    overflow: hidden;
}
.fp-modal::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 180px; height: 180px;
    background: none;
    pointer-events: none;
}
.fp-progress { display: flex; justify-content: center; gap: 8px; margin-bottom: 28px; }
.fp-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.1); transition: all 0.35s ease; }
.fp-dot.active { background: var(--primary-pink); width: 24px; border-radius: 4px; }
.fp-step { animation: fpFadeIn 0.3s ease; }
@keyframes fpFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.fp-icon { width: 56px; height: 56px; margin: 0 auto 18px; border-radius: 16px; background: rgba(255,27,141,0.12); border: 1px solid rgba(255,27,141,0.25); display: flex; align-items: center; justify-content: center; font-size: 22px; color: var(--primary-pink); }
.fp-icon--purple { background: rgba(168,85,247,0.12); border-color: rgba(168,85,247,0.25); color: #c084fc; }
.fp-icon--green  { background: rgba(76,187,122,0.12);  border-color: rgba(76,187,122,0.25);  color: #4cbb7a; }
.fp-sub { color: var(--text-secondary); font-size: 13.5px; text-align: center; margin-bottom: 24px; line-height: 1.6; }
.fp-username { color: #c084fc; font-weight: 700; }
.fp-input-wrap { position: relative; display: flex; align-items: center; }
.fp-input-icon { position: absolute; left: 14px; color: var(--text-muted); font-size: 13px; pointer-events: none; }
.fp-input { padding-left: 38px !important; }
.fp-eye { position: absolute; right: 12px; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 13px; padding: 4px; transition: color 0.2s; }
.fp-eye:hover { color: var(--text-primary); }
.fp-error { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; padding: 10px 14px; color: #f87171; font-size: 12.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.fp-warn  { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); border-radius: 8px; padding: 10px 14px; color: #f59e0b; font-size: 12.5px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.fp-hint  { color: var(--text-muted); font-size: 11px; display: block; margin-top: 5px; }
.fp-question-box { background: rgba(168,85,247,0.07); border: 1px solid rgba(168,85,247,0.2); border-radius: 10px; padding: 13px 16px; font-size: 13.5px; color: #c084fc; margin-bottom: 18px; line-height: 1.6; display: flex; gap: 10px; align-items: flex-start; }
.fp-question-box i { flex-shrink: 0; margin-top: 2px; }
.fp-str-track { height: 3px; background: rgba(255,255,255,0.06); border-radius: 2px; margin-top: 7px; overflow: hidden; }
#fpStrBar { height: 100%; border-radius: 2px; transition: all 0.3s; width: 0; }
.fp-reqs { display: grid; grid-template-columns: 1fr 1fr; gap: 5px 10px; margin-bottom: 18px; padding: 12px 14px; background: rgba(255,255,255,0.02); border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); }
.fp-req { font-size: 11.5px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; transition: color 0.25s; }
.fp-req i { font-size: 6px; transition: all 0.25s; }
.fp-req.met { color: #4cbb7a; }
.fp-req.met i { font-size: 11px; color: #4cbb7a; }
.fp-req.met i::before { content: '\f058'; font-family: 'Font Awesome 6 Free'; font-weight: 900; }
.fp-btn { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 4px; }
.fp-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none !important; }
.fp-btn .fa-spinner { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.fp-footer-link { text-align: center; margin-top: 16px; }
.fp-footer-link a { color: var(--text-muted); font-size: 13px; text-decoration: none; transition: color 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.fp-footer-link a:hover { color: var(--primary-pink); }
.fp-close { top: 14px !important; right: 16px !important; }
.fp-success-anim { display: flex; justify-content: center; margin-bottom: 22px; }
.fp-check-circle { width: 72px; height: 72px; border-radius: 50%; background: rgba(76,187,122,0.12); border: 2px solid #4cbb7a; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #4cbb7a; animation: fpPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
@keyframes fpPop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.fp-success-title { text-align: center; background: linear-gradient(135deg, #4cbb7a, #22c55e) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; background-clip: text !important; margin-bottom: 10px; }
</style>


<script>
function showLoginError(msg) {
    const box = document.getElementById('loginError');
    const txt = document.getElementById('loginErrorMsg');
    const suc = document.getElementById('loginSuccess');
    if (suc) suc.style.display = 'none';
    if (txt) txt.innerHTML = msg;
    if (box) { box.style.display = 'flex'; }
}
function showLoginSuccess(msg) {
    const box = document.getElementById('loginSuccess');
    const txt = document.getElementById('loginSuccessMsg');
    const err = document.getElementById('loginError');
    if (err) err.style.display = 'none';
    if (txt) txt.textContent = msg;
    if (box) { box.style.display = 'flex'; }
}

async function handleLogin(e) {
    e.preventDefault();
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const remember = document.getElementById('rememberMe').checked;
    const csrf     = document.getElementById('loginCsrf').value;
    const btn      = document.getElementById('loginBtn');

    // Hide previous messages
    document.getElementById('loginError').style.display   = 'none';
    document.getElementById('loginSuccess').style.display = 'none';

    if (!email || !password) { showLoginError('Please fill in all fields.'); return; }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showLoginError('Please enter a valid email address.'); return; }

    // Loading state
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in…';

    const inPages = window.location.pathname.includes('/pages/');
    const url = inPages ? '../pages/login_process.php' : 'pages/login_process.php';

    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password, remember, csrf_token: csrf })
        });
        const data = await res.json();

        if (data.success) {
            showLoginSuccess(data.message || 'Login successful! Redirecting…');
            window.location.reload();
        } else {
            showLoginError(data.error || 'Login failed. Please try again.');
            btn.disabled  = false;
            btn.innerHTML = origText;
        }
    } catch (err) {
        showLoginError('Could not reach the server. Please try again.');
        btn.disabled  = false;
        btn.innerHTML = origText;
    }
}

var regCurrentStep = 1;

function regGoToStep(step) {
    if (step > regCurrentStep) return; // only allow going back freely
    regShowPanel(step);
}

function regShowPanel(step) {
    [1, 2, 3].forEach(i => {
        const p = document.getElementById('regPanel' + i);
        if (p) p.style.display = (i === step) ? '' : 'none';
        const ind = document.getElementById('regStep' + i + 'Ind');
        if (!ind) return;
        ind.classList.remove('active', 'done');
        if (i === step)       ind.classList.add('active');
        else if (i < step)    ind.classList.add('done');
    });
    // Step connector lines
    const l1 = document.getElementById('regLine1');
    const l2 = document.getElementById('regLine2');
    if (l1) l1.classList.toggle('done', step > 1);
    if (l2) l2.classList.toggle('done', step > 2);
    // Update done dots to show checkmark
    [1, 2, 3].forEach(i => {
        const ind = document.getElementById('regStep' + i + 'Ind');
        if (!ind) return;
        const span = ind.querySelector('.reg-step-dot span');
        if (!span) return;
        if (ind.classList.contains('done')) {
            span.textContent = '';  
        } else {
            span.textContent = i;
        }
    });
    regCurrentStep = step;
}

function regNextStep(fromStep) {
    if (fromStep === 1) {
        if (!regValidateStep1()) return;
        regShowPanel(2);
    } else if (fromStep === 2) {
        if (!regValidateStep2()) return;
        regPopulateReview();
        regShowPanel(3);
    }
}

function regValidateStep1() {
    const username = document.getElementById('registerUsername').value.trim();
    const email    = document.getElementById('registerEmail').value.trim();
    const phone    = document.getElementById('registerPhone').value.trim();
    let err = '';
    if (!username)                                  err = 'Username is required.';
    else if (!/^[a-zA-Z0-9_]{3,50}$/.test(username)) err = 'Username: 3–50 chars, letters/numbers/underscores only.';
    else if (!email)                                err = 'Email is required.';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) err = 'Please enter a valid email address.';
    else if (!phone)                                err = 'Phone number is required.';
    else if (!/^[\+]?[0-9\s\-\(\)]+$/.test(phone)) err = 'Please enter a valid phone number.';
    if (err) { alert(err); return false; }
    return true;
}

function regValidateStep2() {
    const password = document.getElementById('registerPassword').value;
    const confirm  = document.getElementById('registerConfirmPassword').value;
    const question = document.getElementById('registerSecurityQuestion').value;
    const answer   = document.getElementById('registerSecurityAnswer').value.trim();
    let err = '';
    if (!password)                                         err = 'Password is required.';
    else if (password.length < 8)                          err = 'Password must be at least 8 characters.';
    else if (!/[A-Z]/.test(password))                      err = 'Password needs at least one uppercase letter.';
    else if (!/[a-z]/.test(password))                      err = 'Password needs at least one lowercase letter.';
    else if (!/[0-9]/.test(password))                      err = 'Password needs at least one number.';
    else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password))    err = 'Password needs at least one special character.';
    else if (password !== confirm)                         err = 'Passwords do not match.';
    else if (!question)                                    err = 'Please select a security question.';
    else if (!answer || answer.length < 2)                 err = 'Security answer must be at least 2 characters.';
    if (err) { alert(err); return false; }
    return true;
}

function regPopulateReview() {
    var u = document.getElementById('registerUsername').value.trim();
    var e = document.getElementById('registerEmail').value.trim();
    var p = document.getElementById('registerPhone').value.trim();
    var q = document.getElementById('registerSecurityQuestion').value;

    var ru = document.getElementById('rev-username');
    var re = document.getElementById('rev-email');
    var rp = document.getElementById('rev-phone');
    var rw = document.getElementById('rev-password');
    var rq = document.getElementById('rev-question');

    if (ru) ru.textContent = u || '—';
    if (re) re.textContent = e || '—';
    if (rp) rp.textContent = p || '—';
    if (rw) rw.textContent = '••••••••';
    if (rq) rq.textContent = q || '—';
}

function showRegisterError(msg) {
    const box = document.getElementById('registerError');
    const txt = document.getElementById('registerErrorMsg');
    if (txt) txt.innerHTML = msg;
    if (box) { box.style.display = 'flex'; box.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }
}
function hideRegisterError() {
    const box = document.getElementById('registerError');
    if (box) box.style.display = 'none';
}

async function handleRegister(e) {
    e.preventDefault();
    hideRegisterError();

    // Client-side validation first
    if (!regValidateStep1()) { regShowPanel(1); return; }
    if (!regValidateStep2()) { regShowPanel(2); return; }
    const terms = document.getElementById('agreeTerms').checked;
    if (!terms) { showRegisterError('You must agree to the Terms of Service to register.'); return; }

    const submitBtn = document.querySelector('.reg-submit-btn');
    const origText  = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account…'; }

    const inPages = window.location.pathname.includes('/pages/');
    const url = inPages ? '../pages/register_process.php' : 'pages/register_process.php';

    const payload = {
        csrf_token:        document.getElementById('registerCsrf').value,
        username:          document.getElementById('registerUsername').value.trim(),
        email:             document.getElementById('registerEmail').value.trim(),
        phone:             document.getElementById('registerPhone').value.trim(),
        password:          document.getElementById('registerPassword').value,
        confirm_password:  document.getElementById('registerConfirmPassword').value,
        security_question: document.getElementById('registerSecurityQuestion').value,
        security_answer:   document.getElementById('registerSecurityAnswer').value.trim(),
    };

    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
            showRegisterError('');
            hideRegisterError();
            closeModal('registerModal');
            setTimeout(() => {
                openModal('loginModal');
                const suc = document.getElementById('loginSuccess');
                const txt = document.getElementById('loginSuccessMsg');
                if (txt) txt.textContent = 'Registration successful! Please log in.';
                if (suc) suc.style.display = 'flex';
            }, 300);
        } else {
            showRegisterError(data.error || 'Registration failed. Please try again.');
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
        }
    } catch (err) {
        showRegisterError('Could not reach the server. Please try again.');
        if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origText; }
    }
}

function validateRegisterForm() { return true; }

function regValidateUsername(input) {
    const val = input.value.trim();
    const ok = /^[a-zA-Z0-9_]{3,50}$/.test(val);
    regSetStatus('usernameStatus', val.length > 0 ? ok : null);
}
function regValidateEmail(input) {
    const val = input.value.trim();
    const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    regSetStatus('emailStatus', val.length > 0 ? ok : null);
}
function regValidatePhone(input) {
    const val = input.value.trim();
    const ok = /^[\+]?[0-9\s\-\(\)]{7,}$/.test(val);
    regSetStatus('phoneStatus', val.length > 0 ? ok : null);
}
function regSetStatus(id, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    if (ok === null) { el.textContent = ''; el.className = 'reg-field-status'; return; }
    el.textContent = ok ? '✓' : '✗';
    el.className   = 'reg-field-status ' + (ok ? 'ok' : 'err');
}

function regToggle(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    const icon = btn.querySelector('i');
    if (icon) icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
}

function regStrength(pw) {
    const checks = {
        'rreq-len':   pw.length >= 8,
        'rreq-upper': /[A-Z]/.test(pw),
        'rreq-lower': /[a-z]/.test(pw),
        'rreq-num':   /[0-9]/.test(pw),
        'rreq-sym':   /[!@#$%^&*(),.?":{}|<>]/.test(pw),
    };
    let score = 0;
    Object.entries(checks).forEach(([id, met]) => {
        const el = document.getElementById(id);
        if (el) el.className = 'reg-req-chip' + (met ? ' met' : '');
        if (met) score++;
    });
    const bar    = document.getElementById('regStrBar');
    const colors = ['#ef4444','#f97316','#eab308','#22c55e','#4cbb7a'];
    if (bar) { bar.style.width = (score * 20) + '%'; bar.style.background = colors[score - 1] || '#ef4444'; }
}

function regMatchCheck() {
    const pw = document.getElementById('registerPassword').value;
    const cf = document.getElementById('registerConfirmPassword').value;
    const el = document.getElementById('regMatchHint');
    if (!el || !cf) return;
    if (pw === cf) { el.textContent = '✓ Passwords match'; el.style.color = '#4cbb7a'; }
    else           { el.textContent = '✗ Passwords do not match'; el.style.color = '#f87171'; }
}

(function () {
    'use strict';

    const FP_URL = (function () {
        const inPages = window.location.pathname.includes('/pages/');
        return inPages ? '../ajax/forgot_password.php' : 'ajax/forgot_password.php';
    })();

    let fpAnswerAttempts = 0;
    const FP_MAX_ATTEMPTS = 3;

    function fpSetDots(step) {
        [1, 2, 3].forEach(i => {
            const d = document.getElementById('fpDot' + i);
            if (d) d.className = 'fp-dot' + (i <= step ? ' active' : '');
        });
    }

    function fpShowStep(num) {
        [1, 2, 3, 4].forEach(i => {
            const el = document.getElementById('fp-step' + i);
            if (el) el.style.display = (i === num) ? '' : 'none';
        });
        if (num <= 3) fpSetDots(num);
    }

    function fpShowErr(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
        el.style.display = 'flex';
    }

    function fpHideErr(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    function fpSetLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = loading;
        if (loading) {
            btn.dataset.orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner"></i> <span>Please wait…</span>';
        } else {
            btn.innerHTML = btn.dataset.orig || btn.innerHTML;
        }
    }

    window.fpReset = function () {
        fpAnswerAttempts = 0;
        ['fpEmail','fpAnswer','fpNewPw','fpConfPw'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['fp-err1','fp-err2','fp-err3','fp-attempts'].forEach(fpHideErr);
        const bar = document.getElementById('fpStrBar');
        const hint = document.getElementById('fpStrHint');
        if (bar)  { bar.style.width = '0'; bar.style.background = ''; }
        if (hint) hint.textContent = 'Min 8 · Upper · Lower · Number · Symbol';
        ['req-len','req-upper','req-lower','req-num','req-sym'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.className = 'fp-req';
        });
        const mh = document.getElementById('fpMatchHint');
        if (mh) mh.textContent = '';
        fpShowStep(1);
    };

    window.fpStep1 = async function () {
        fpHideErr('fp-err1');
        const email = document.getElementById('fpEmail').value.trim();
        if (!email) { fpShowErr('fp-err1', 'Please enter your email address.'); return; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { fpShowErr('fp-err1', 'Please enter a valid email address.'); return; }
        fpSetLoading('fpBtn1', true);
        try {
            const res  = await fetch(FP_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ step:'email', email }) });
            const data = await res.json();
            if (data.error) { fpShowErr('fp-err1', data.error); return; }
            document.getElementById('fpUsername').textContent = data.username;
            document.getElementById('fpQuestion').textContent  = data.question;
            fpShowStep(2);
            setTimeout(() => document.getElementById('fpAnswer').focus(), 50);
        } catch (e) {
            fpShowErr('fp-err1', 'Could not reach the server. Please try again.');
        } finally {
            fpSetLoading('fpBtn1', false);
        }
    };

    window.fpStep2 = async function () {
        fpHideErr('fp-err2');
        fpHideErr('fp-attempts');
        if (fpAnswerAttempts >= FP_MAX_ATTEMPTS) {
            fpShowErr('fp-err2', 'Too many incorrect attempts. Please start over.');
            setTimeout(fpReset, 2000); return;
        }
        const answer = document.getElementById('fpAnswer').value.trim();
        if (!answer) { fpShowErr('fp-err2', 'Please enter your answer.'); return; }
        fpSetLoading('fpBtn2', true);
        try {
            const res  = await fetch(FP_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ step:'answer', answer }) });
            const data = await res.json();
            if (data.error) {
                fpAnswerAttempts++;
                const remaining = FP_MAX_ATTEMPTS - fpAnswerAttempts;
                fpShowErr('fp-err2', data.error);
                if (remaining > 0) {
                    const warn = document.getElementById('fp-attempts');
                    const msg  = document.getElementById('fp-attempts-msg');
                    if (warn && msg) { msg.textContent = remaining + ' attempt' + (remaining === 1 ? '' : 's') + ' remaining.'; warn.style.display = 'flex'; }
                } else {
                    fpShowErr('fp-err2', 'Too many incorrect attempts. Restarting…');
                    setTimeout(fpReset, 2000);
                }
                document.getElementById('fpAnswer').value = '';
                return;
            }
            fpShowStep(3);
            setTimeout(() => document.getElementById('fpNewPw').focus(), 50);
        } catch (e) {
            fpShowErr('fp-err2', 'Could not reach the server. Please try again.');
        } finally {
            fpSetLoading('fpBtn2', false);
        }
    };

    window.fpStep3 = async function () {
        fpHideErr('fp-err3');
        const password = document.getElementById('fpNewPw').value;
        const confirm  = document.getElementById('fpConfPw').value;
        if (!password) { fpShowErr('fp-err3', 'Please enter a new password.'); return; }
        if (password !== confirm) { fpShowErr('fp-err3', 'Passwords do not match.'); return; }
        fpSetLoading('fpBtn3', true);
        try {
            const res  = await fetch(FP_URL, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ step:'reset', password, confirm }) });
            const data = await res.json();
            if (data.error) { fpShowErr('fp-err3', data.error); return; }
            fpShowStep(4);
            [1,2,3].forEach(i => { const d = document.getElementById('fpDot'+i); if (d) d.className = 'fp-dot active'; });
        } catch (e) {
            fpShowErr('fp-err3', 'Could not reach the server. Please try again.');
        } finally {
            fpSetLoading('fpBtn3', false);
        }
    };

    window.fpStrength = function (pw) {
        const checks = {
            'req-len':   pw.length >= 8,
            'req-upper': /[A-Z]/.test(pw),
            'req-lower': /[a-z]/.test(pw),
            'req-num':   /[0-9]/.test(pw),
            'req-sym':   /[!@#$%^&*(),.?":{}|<>]/.test(pw),
        };
        let score = 0;
        Object.entries(checks).forEach(([id, met]) => {
            const el = document.getElementById(id);
            if (el) el.className = 'fp-req' + (met ? ' met' : '');
            if (met) score++;
        });
        const bar    = document.getElementById('fpStrBar');
        const hint   = document.getElementById('fpStrHint');
        const colors = ['#ef4444','#f97316','#eab308','#22c55e','#4cbb7a'];
        const labels = ['Very weak','Weak','Fair','Good','Strong'];
        if (bar) { bar.style.width = (score * 20) + '%'; bar.style.background = colors[score-1] || '#ef4444'; }
        if (hint) hint.textContent = pw.length > 0 ? (labels[score-1] || '') : 'Min 8 · Upper · Lower · Number · Symbol';
    };

    window.fpMatchCheck = function () {
        const pw = document.getElementById('fpNewPw').value;
        const cf = document.getElementById('fpConfPw').value;
        const el = document.getElementById('fpMatchHint');
        if (!el || !cf) return;
        if (pw === cf) { el.textContent = '✓ Passwords match'; el.style.color = '#4cbb7a'; }
        else           { el.textContent = '✗ Passwords do not match'; el.style.color = '#f87171'; }
    };

    window.fpToggle = function (inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        const icon = btn.querySelector('i');
        if (icon) icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('fpEmail') ?.addEventListener('keydown', e => { if (e.key === 'Enter') fpStep1(); });
        document.getElementById('fpAnswer')?.addEventListener('keydown', e => { if (e.key === 'Enter') fpStep2(); });
        document.getElementById('fpConfPw')?.addEventListener('keydown', e => { if (e.key === 'Enter') fpStep3(); });
    });

})();
</script>