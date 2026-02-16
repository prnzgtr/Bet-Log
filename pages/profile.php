<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

try {
    $columnsStmt = $conn->query("DESCRIBE users");
    $allColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);

    $selectFields = ['*'];
    if (in_array('date_of_birth',    $allColumns)) $selectFields[] = "DATE_FORMAT(date_of_birth, '%Y-%m-%d') as dob_formatted";
    if (in_array('registration_date',$allColumns)) $selectFields[] = "DATE_FORMAT(registration_date, '%M %d, %Y') as reg_formatted";
    if (in_array('last_login',       $allColumns)) $selectFields[] = "DATE_FORMAT(last_login, '%M %d, %Y %h:%i %p') as login_formatted";

    $selectFields = array_unique($selectFields);
    $stmt = $conn->prepare("SELECT " . implode(', ', $selectFields) . " FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate age
$age = null;
if (!empty($user['date_of_birth'])) {
    try {
        $age = (new DateTime())->diff(new DateTime($user['date_of_birth']))->y;
    } catch (Exception $e) {}
}

$page_title = 'Account Settings';
include '../includes/header.php';
?>

<style>
:root {
    --pk:  #FF1B8D;
    --pu:  #A855F7;
    --nv:  #0B0E1A;
    --cy:  #00D9FF;

    --pk10: rgba(255,27,141,.10);
    --pk20: rgba(255,27,141,.20);
    --pu10: rgba(168,85,247,.10);
    --pu20: rgba(168,85,247,.20);
    --cy10: rgba(0,217,255,.10);
    --cy20: rgba(0,217,255,.20);

    --s1: #0F1325;
    --s2: #141830;
    --s3: #1A1F3A;

    --bd:  rgba(168,85,247,.14);
    --bdh: rgba(255,27,141,.28);

    --t1: #F4F2FF;
    --t2: #9090B0;
    --t3: #484870;

    --grad:  linear-gradient(135deg,#FF1B8D 0%,#A855F7 55%,#00D9FF 100%);
    --gpink: linear-gradient(135deg,#FF1B8D,#C837AB);
    --gpur:  linear-gradient(135deg,#A855F7,#7C3AED);
    --gcyan: linear-gradient(135deg,#00D9FF,#0088BB);

    --ff: 'Outfit', sans-serif;
    --fm: 'DM Mono', monospace;
    --r:  11px;
    --rL: 18px;
    --rXL:26px;
}

/* Fonts */
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap');

/* Reset scoped to this page */
.ps-wrap * { box-sizing: border-box; }

/* ── Page wrapper ── */
.ps-wrap {
    font-family: var(--ff);
    color: var(--t1);
    padding: 28px 0 48px;
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.ps-banner {
    position: relative;
    border-radius: var(--rXL);
    overflow: hidden;
    border: 1px solid var(--bd);
}

.ps-banner-bg {
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(ellipse 80% 70% at 8%  50%, rgba(255,27,141,.17) 0%, transparent 55%),
        radial-gradient(ellipse 65% 75% at 78% 20%, rgba(168,85,247,.15) 0%, transparent 55%),
        radial-gradient(ellipse 50% 55% at 55% 100%,rgba(0,217,255,.09)  0%, transparent 55%),
        var(--s2);
}

/* subtle dot grid */
.ps-banner-bg::after {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(168,85,247,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(168,85,247,.04) 1px, transparent 1px);
    background-size: 36px 36px;
    mask-image: radial-gradient(ellipse 100% 100% at 50% 50%, black 10%, transparent 80%);
}

.ps-banner-line {
    position: absolute; bottom: 0; left: 0; right: 0;
    height: 2px; background: var(--grad); opacity: .55;
}

.ps-banner-inner {
    position: relative; z-index: 1;
    padding: 34px 36px;
    display: flex; align-items: center; gap: 26px; flex-wrap: wrap;
}

/* Avatar */
.ps-av-wrap  { position: relative; flex-shrink: 0; }

.ps-av-ring {
    display: none;
}

@keyframes ps-hue { to { filter: hue-rotate(360deg); } }

.ps-av-img {
    position: relative; z-index: 1;
    width: 84px; height: 84px;
    border-radius: 16px;
    object-fit: cover;
    border: 3px solid var(--nv);
    display: block;
    background: var(--s3);
    transition: opacity .3s;
}

.ps-av-btn {
    position: absolute; bottom: -8px; right: -8px; z-index: 2;
    width: 30px; height: 30px;
    background: var(--s1); border: 1.5px solid var(--pk);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .2s;
    box-shadow: 0 0 12px rgba(255,27,141,.3);
    color: var(--pk); font-size: 12px;
}

.ps-av-btn:hover { background: var(--pk); color: #fff; transform: scale(1.1); }

/* User info */
.ps-user { flex: 1; min-width: 0; }

.ps-name {
    font-size: 24px; font-weight: 800;
    letter-spacing: -.7px; color: var(--t1); line-height: 1.1;
    margin-bottom: 5px;
}

.ps-handle {
    font-size: 12px; color: var(--t2);
    display: flex; align-items: center; gap: 5px;
    font-family: var(--fm);
}

.ps-handle i { color: var(--cy); font-size: 10px; }

.ps-pills {
    display: flex; flex-wrap: wrap; gap: 7px; margin-top: 13px;
}

.ps-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 100px;
    font-size: 11px; font-weight: 600; letter-spacing: .3px;
}

.ps-pill-cy { background: var(--cy10); border: 1px solid var(--cy20); color: var(--cy); }
.ps-pill-pu { background: var(--pu10); border: 1px solid var(--pu20); color: var(--pu); }
.ps-pill i  { font-size: 8px; }

/* Banner stats */
.ps-stats {
    display: flex; gap: 1px;
    background: rgba(168,85,247,.12);
    border: 1px solid var(--bd);
    border-radius: 14px;
    overflow: hidden;
    flex-shrink: 0;
}

.ps-stat {
    padding: 16px 24px; text-align: center;
    background: rgba(11,14,26,.6);
}

.ps-stat-n {
    font-size: 22px; font-weight: 800; letter-spacing: -1px; line-height: 1;
    background: var(--grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

.ps-stat-l { font-size: 10px; color: var(--t3); text-transform: uppercase; letter-spacing: .7px; margin-top: 4px; }

.ps-alert {
    padding: 13px 16px; border-radius: var(--rL);
    display: flex; align-items: center; gap: 11px;
    font-size: 13px; font-weight: 500;
    animation: ps-drop .3s ease both;
}

@keyframes ps-drop { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:none} }

.ps-alert-ok  { background:rgba(0,217,255,.08); border:1px solid rgba(0,217,255,.3); color:var(--cy); }
.ps-alert-err { background:rgba(255,27,141,.08); border:1px solid rgba(255,27,141,.3); color:var(--pk); }

.ps-layout {
    display: grid;
    grid-template-columns: 1fr 290px;
    gap: 22px;
    align-items: start;
}

.ps-main { display: flex; flex-direction: column; gap: 20px; }
.ps-side { display: flex; flex-direction: column; gap: 20px; }

.ps-card {
    background: var(--s1);
    border: 1px solid var(--bd);
    border-radius: var(--rL);
    overflow: hidden;
    transition: border-color .3s, box-shadow .3s;
}

.ps-card:hover { border-color: var(--bdh); box-shadow: 0 6px 28px rgba(255,27,141,.06); }

.ps-card-head {
    display: flex; align-items: center; gap: 12px;
    padding: 18px 22px;
    border-bottom: 1px solid var(--bd);
    background: rgba(255,255,255,.012);
}

.ps-card-ico {
    width: 34px; height: 34px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}

.ico-pk { background: var(--pk10); border: 1px solid var(--pk20); color: var(--pk); }
.ico-pu { background: var(--pu10); border: 1px solid var(--pu20); color: var(--pu); }
.ico-cy { background: var(--cy10); border: 1px solid var(--cy20); color: var(--cy); }

.ps-card-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--t1); }
.ps-card-sub   { font-size: 11px; color: var(--t3); margin-top: 2px; }

.ps-card-body { padding: 22px; }

.ps-fg   { display: grid; gap: 15px; }
.ps-fg-2 { grid-template-columns: 1fr 1fr; }
.ps-full { grid-column: 1/-1; }

.ps-field { display: flex; flex-direction: column; gap: 6px; }

.ps-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: var(--t2);
    display: flex; align-items: center; gap: 4px;
}

.ps-label .req { color: var(--pk); }

.ps-input-wrap { position: relative; }

.ps-ico {
    position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
    color: var(--t3); font-size: 12px; pointer-events: none;
    transition: color .2s;
}

.ps-input,
.ps-select {
    width: 100%;
    padding: 11px 13px 11px 38px;
    background: var(--s2);
    border: 1px solid var(--bd);
    border-radius: var(--r);
    color: var(--t1);
    font-size: 14px;
    font-family: var(--ff);
    transition: all .25s;
}

.ps-input:focus,
.ps-select:focus {
    outline: none;
    border-color: var(--pk);
    background: rgba(255,27,141,.04);
    box-shadow: 0 0 0 3px rgba(255,27,141,.11);
}

.ps-input:focus + .ps-ico,
.ps-input-wrap:focus-within .ps-ico { color: var(--pk); }

.ps-input::placeholder { color: var(--t3); }

.ps-input[readonly] {
    background: rgba(255,255,255,.02);
    border-style: dashed;
    cursor: not-allowed;
    opacity: .5;
}

.ps-input.ps-bad {
    border-color: var(--pk) !important;
    box-shadow: 0 0 0 3px rgba(255,27,141,.14) !important;
}

.ps-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 12 12'%3E%3Cpath fill='%23A855F7' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 13px center;
    background-color: var(--s2);
    cursor: pointer;
}

.ps-select:focus { border-color: var(--pu); background-color: rgba(168,85,247,.04); box-shadow: 0 0 0 3px rgba(168,85,247,.11); }
.ps-select option { background: var(--s2); }

.ps-hint {
    font-size: 11px; color: var(--t3);
    display: flex; align-items: center; gap: 5px;
}

.ps-hint i { color: var(--pu); font-size: 10px; }

/* Password strength */
.ps-str-track { height: 3px; border-radius: 2px; background: var(--s3); margin-top: 6px; overflow: hidden; }
.ps-str-fill  { height: 100%; width: 0; border-radius: 2px; transition: width .35s ease, background .35s ease; }

.ps-widget {
    background: var(--s1);
    border: 1px solid var(--bd);
    border-radius: var(--rL);
    overflow: hidden;
}

.ps-widget-head {
    padding: 14px 18px;
    border-bottom: 1px solid var(--bd);
    display: flex; align-items: center; justify-content: space-between;
}

.ps-widget-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--t2); }

.ps-widget-body { padding: 14px 18px; }

/* Status rows */
.ps-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid rgba(168,85,247,.07);
    font-size: 12px;
}

.ps-row:last-child { border-bottom: none; padding-bottom: 0; }
.ps-row-k { color: var(--t2); }
.ps-row-v { color: var(--t1); font-weight: 600; text-align: right; max-width: 130px; word-break: break-all; }

.ps-badge-on {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 100px;
    background: var(--cy10); border: 1px solid var(--cy20);
    color: var(--cy); font-size: 10px; font-weight: 700;
}

.ps-badge-on i { font-size: 6px; animation: ps-ping 2s ease-in-out infinite; }

@keyframes ps-ping { 0%,100%{opacity:1} 50%{opacity:.3} }

/* Timeline */
.ps-tl { display: flex; flex-direction: column; }

.ps-tl-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(168,85,247,.07);
}

.ps-tl-item:last-child { border-bottom: none; padding-bottom: 0; }
.ps-tl-item:first-child { padding-top: 0; }

.ps-tl-ico {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
}

.tli-pk { background: var(--pk10); border: 1px solid var(--pk20); color: var(--pk); }
.tli-pu { background: var(--pu10); border: 1px solid var(--pu20); color: var(--pu); }
.tli-cy { background: var(--cy10); border: 1px solid var(--cy20); color: var(--cy); }

.ps-tl-l { font-size: 10px; color: var(--t3); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.ps-tl-v { font-size: 12px; font-weight: 600; color: var(--t1); }


.ps-actions {
    display: flex; justify-content: flex-end; gap: 10px;
    padding-top: 18px; border-top: 1px solid var(--bd);
    margin-top: 4px;
}

.ps-btn {
    padding: 10px 22px;
    font-size: 13px; font-weight: 700; font-family: var(--ff);
    border-radius: var(--r); border: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: all .25s;
}

.ps-btn-ghost {
    background: transparent;
    color: var(--t2); border: 1px solid var(--bd);
}

.ps-btn-ghost:hover { background: var(--s3); color: var(--t1); border-color: var(--pu20); }

.ps-btn-save {
    background: var(--grad);
    color: #fff; position: relative; overflow: hidden;
    box-shadow: 0 4px 18px rgba(255,27,141,.28);
}

.ps-btn-save::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg,rgba(255,255,255,.15),transparent);
    opacity: 0; transition: opacity .2s;
}

.ps-btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(255,27,141,.42); }
.ps-btn-save:hover::before { opacity: 1; }
.ps-btn-save:active { transform: none; }


.ps-toasts {
    position: fixed; bottom: 26px; right: 26px; z-index: 9999;
    display: flex; flex-direction: column; gap: 9px; pointer-events: none;
}

.ps-toast {
    padding: 12px 16px; border-radius: var(--rL);
    display: flex; align-items: center; gap: 11px;
    font-size: 13px; font-weight: 500; font-family: var(--ff);
    backdrop-filter: blur(20px);
    pointer-events: all; max-width: 320px;
    animation: ps-tin .35s cubic-bezier(.34,1.56,.64,1) both;
}

@keyframes ps-tin  { from{opacity:0;transform:translateX(28px) scale(.9)} to{opacity:1;transform:none} }
@keyframes ps-tout { to  {opacity:0;transform:translateX(28px) scale(.9)} }

.ps-toast-ok   { background:rgba(0,217,255,.12); border:1px solid rgba(0,217,255,.32); color:var(--cy); }
.ps-toast-err  { background:rgba(255,27,141,.12); border:1px solid rgba(255,27,141,.32); color:var(--pk); }
.ps-toast-warn { background:rgba(245,158,11,.12); border:1px solid rgba(245,158,11,.32); color:#F59E0B; }

.ps-toast i { font-size: 15px; flex-shrink: 0; }


@media (max-width: 1024px) {
    .ps-layout { grid-template-columns: 1fr; }
    .ps-side   { flex-direction: row; flex-wrap: wrap; }
    .ps-side > * { flex: 1 1 240px; }
}

@media (max-width: 640px) {
    .ps-banner-inner { padding: 24px 18px; }
    .ps-stats        { display: none; }
    .ps-fg-2         { grid-template-columns: 1fr; }
    .ps-actions      { flex-direction: column; }
    .ps-btn          { width: 100%; justify-content: center; }
    .ps-card-body    { padding: 16px; }
}

#profileImageInput { display: none; }
</style>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="ps-wrap">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="ps-alert ps-alert-ok">
                    <i class="fas fa-circle-check"></i>
                    <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="ps-alert ps-alert-err">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- ── BANNER ── -->
            <div class="ps-banner">
                <div class="ps-banner-bg"></div>
                <div class="ps-banner-line"></div>

                <div class="ps-banner-inner">
                    <!-- Avatar -->
                    <div class="ps-av-wrap">
                        <div class="ps-av-ring"></div>
                        <img src="<?php echo isset($user['profile_image']) && $user['profile_image']
                            ? '../' . htmlspecialchars($user['profile_image'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode(($user['first_name'] ?? '') . '+' . ($user['last_name'] ?? $user['username'])) . '&size=200&background=A855F7&color=fff&bold=true'; ?>"
                             alt="Profile"
                             class="ps-av-img"
                             id="profileImagePreview">
                        <label for="profileImageInput" class="ps-av-btn" title="Change photo">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>

                    <!-- User info -->
                    <div class="ps-user">
                        <div class="ps-name">
                            <?php echo htmlspecialchars(
                                isset($user['first_name']) && $user['first_name']
                                    ? trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''))
                                    : $user['username']
                            ); ?>
                        </div>
                        <div class="ps-handle">
                            <i class="fas fa-at"></i>
                            <span><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <div class="ps-pills">
                            <span class="ps-pill ps-pill-cy"><i class="fas fa-circle"></i> Active</span>
                            <span class="ps-pill ps-pill-pu"><i class="fas fa-shield-halved"></i> Verified</span>
                        </div>
                    </div>


                </div>
            </div>

            <!-- ── FORM ── -->
            <form id="ps-form" method="POST" action="update_profile.php" enctype="multipart/form-data">

                <?php if (function_exists('generate_csrf_token')): ?>
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <?php endif; ?>

                <input type="file" id="profileImageInput" name="profile_image"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="psAvatar(this)">

                <div class="ps-layout">

                    <!-- LEFT: form cards -->
                    <div class="ps-main">

                        <!-- Personal Info -->
                        <div class="ps-card">
                            <div class="ps-card-head">
                                <div class="ps-card-ico ico-pk"><i class="fas fa-user"></i></div>
                                <div>
                                    <div class="ps-card-title">Personal Information</div>
                                    <div class="ps-card-sub">Your public profile details</div>
                                </div>
                            </div>
                            <div class="ps-card-body">
                                <div class="ps-fg ps-fg-2">

                                    <div class="ps-field">
                                        <label class="ps-label">First Name <span class="req">*</span></label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-user ps-ico"></i>
                                            <input type="text" name="first_name" class="ps-input"
                                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                                   placeholder="First name" required>
                                        </div>
                                    </div>

                                    <div class="ps-field">
                                        <label class="ps-label">Last Name <span class="req">*</span></label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-user ps-ico"></i>
                                            <input type="text" name="last_name" class="ps-input"
                                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                                   placeholder="Last name" required>
                                        </div>
                                    </div>

                                    <div class="ps-field">
                                        <label class="ps-label">Date of Birth</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-calendar ps-ico"></i>
                                            <input type="date" name="date_of_birth" class="ps-input"
                                                   value="<?php echo htmlspecialchars($user['dob_formatted'] ?? ''); ?>"
                                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                        </div>
                                        <span class="ps-hint"><i class="fas fa-info-circle"></i>
                                            Must be 18 or older<?php echo $age !== null ? ' · Age: ' . $age : ''; ?>
                                        </span>
                                    </div>

                                    <div class="ps-field">
                                        <label class="ps-label">Gender</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-venus-mars ps-ico"></i>
                                            <select name="sex" class="ps-select">
                                                <option value="">Select gender</option>
                                                <?php foreach (['Male','Female','Other','Prefer not to say'] as $g): ?>
                                                    <option value="<?php echo $g; ?>"
                                                        <?php echo (($user['sex'] ?? '') === $g) ? 'selected' : ''; ?>>
                                                        <?php echo $g; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div class="ps-card">
                            <div class="ps-card-head">
                                <div class="ps-card-ico ico-pu"><i class="fas fa-envelope"></i></div>
                                <div>
                                    <div class="ps-card-title">Contact Information</div>
                                    <div class="ps-card-sub">How we reach you</div>
                                </div>
                            </div>
                            <div class="ps-card-body">
                                <div class="ps-fg ps-fg-2">

                                    <div class="ps-field">
                                        <label class="ps-label">Phone Number <span class="req">*</span></label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-phone ps-ico"></i>
                                            <input type="tel" name="phone" class="ps-input"
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                                   placeholder="+1 (555) 000-0000" required>
                                        </div>
                                    </div>

                                    <div class="ps-field">
                                        <label class="ps-label">Email Address</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-envelope ps-ico"></i>
                                            <input type="email" class="ps-input"
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                        </div>
                                        <span class="ps-hint"><i class="fas fa-lock"></i> Contact support to change</span>
                                    </div>

                                    <div class="ps-field ps-full">
                                        <label class="ps-label">Address</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-map-pin ps-ico"></i>
                                            <input type="text" name="permanent_address" class="ps-input"
                                                   value="<?php echo htmlspecialchars($user['permanent_address'] ?? ''); ?>"
                                                   placeholder="Street, City, Country">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="ps-card">
                            <div class="ps-card-head">
                                <div class="ps-card-ico ico-cy"><i class="fas fa-lock"></i></div>
                                <div>
                                    <div class="ps-card-title">Security Settings</div>
                                    <div class="ps-card-sub">Change your password</div>
                                </div>
                            </div>
                            <div class="ps-card-body">
                                <div class="ps-fg ps-fg-2">

                                    <div class="ps-field">
                                        <label class="ps-label">Current Password</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-key ps-ico"></i>
                                            <input type="password" name="current_password" class="ps-input" placeholder="Current password">
                                        </div>
                                        <span class="ps-hint"><i class="fas fa-info-circle"></i> Required to change password</span>
                                    </div>

                                    <div class="ps-field">
                                        <label class="ps-label">New Password</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-lock ps-ico"></i>
                                            <input type="password" name="new_password" id="ps_np" class="ps-input"
                                                   placeholder="New password" oninput="psStrength(this.value)">
                                        </div>
                                        <div class="ps-str-track"><div class="ps-str-fill" id="psStrBar"></div></div>
                                        <span class="ps-hint" id="psStrHint">
                                            <i class="fas fa-shield"></i> Min 8 · Upper · Number · Symbol
                                        </span>
                                    </div>

                                    <div class="ps-field ps-full">
                                        <label class="ps-label">Confirm New Password</label>
                                        <div class="ps-input-wrap">
                                            <i class="fas fa-lock ps-ico"></i>
                                            <input type="password" name="confirm_password" id="ps_cp" class="ps-input"
                                                   placeholder="Re-enter new password" oninput="psMatch()">
                                        </div>
                                        <span class="ps-hint" id="psMatchHint">
                                            <i class="fas fa-check-circle"></i> Must match new password
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="ps-actions">
                            <button type="button" class="ps-btn ps-btn-ghost"
                                    onclick="window.location.href='../index.php'">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="ps-btn ps-btn-save" id="psSaveBtn">
                                <i class="fas fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>

                    </div>

                    <!-- RIGHT: sidebar -->
                    <div class="ps-side">

                        <!-- Account Status -->
                        <div class="ps-widget">
                            <div class="ps-widget-head">
                                <span class="ps-widget-title">Account Status</span>
                                <span class="ps-badge-on"><i class="fas fa-circle"></i> Active</span>
                            </div>
                            <div class="ps-widget-body">
                                <div class="ps-row">
                                    <span class="ps-row-k">Username</span>
                                    <span class="ps-row-v"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                                <div class="ps-row">
                                    <span class="ps-row-k">Email</span>
                                    <span class="ps-row-v"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Account History -->
                        <div class="ps-widget">
                            <div class="ps-widget-head">
                                <span class="ps-widget-title">Account History</span>
                            </div>
                            <div class="ps-widget-body">
                                <div class="ps-tl">
                                    <div class="ps-tl-item">
                                        <div class="ps-tl-ico tli-pk"><i class="fas fa-calendar-plus"></i></div>
                                        <div>
                                            <div class="ps-tl-l">Member Since</div>
                                            <div class="ps-tl-v">
                                                <?php echo isset($user['reg_formatted'])
                                                    ? $user['reg_formatted']
                                                    : (!empty($user['registration_date'])
                                                        ? date('M d, Y', strtotime($user['registration_date']))
                                                        : 'N/A'); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($user['login_formatted']) || !empty($user['last_login'])): ?>
                                    <div class="ps-tl-item">
                                        <div class="ps-tl-ico tli-cy"><i class="fas fa-clock"></i></div>
                                        <div>
                                            <div class="ps-tl-l">Last Login</div>
                                            <div class="ps-tl-v">
                                                <?php echo isset($user['login_formatted'])
                                                    ? $user['login_formatted']
                                                    : date('M d, Y h:i A', strtotime($user['last_login'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($user['date_of_birth'])): ?>
                                    <div class="ps-tl-item">
                                        <div class="ps-tl-ico tli-pu"><i class="fas fa-birthday-cake"></i></div>
                                        <div>
                                            <div class="ps-tl-l">Date of Birth</div>
                                            <div class="ps-tl-v"><?php echo date('M d, Y', strtotime($user['date_of_birth'])); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </form>

        </div><
    </main>
</div>

<div class="ps-toasts" id="psToasts"></div>

<script>
/* ── Avatar preview ── */
function psAvatar(input) {
    if (!input.files[0]) return;
    const f = input.files[0];
    if (f.size > 5*1024*1024) { psToast('Image must be under 5 MB.','err'); input.value=''; return; }
    if (!['image/jpeg','image/png','image/gif','image/webp'].includes(f.type)) {
        psToast('Use JPG, PNG, GIF or WEBP.','err'); input.value=''; return;
    }
    const r = new FileReader();
    const img = document.getElementById('profileImagePreview');
    img.style.opacity = '.4';
    r.onload = e => { img.src = e.target.result; img.style.opacity = '1'; psToast('Photo ready — save to apply.','warn'); };
    r.readAsDataURL(f);
}

/* ── Password strength ── */
function psStrength(v) {
    let s = 0;
    if (v.length >= 8)                          s++;
    if (/[A-Z]/.test(v))                        s++;
    if (/[a-z]/.test(v))                        s++;
    if (/[0-9]/.test(v))                        s++;
    if (/[!@#$%^&*(),.?":{}|<>_\-]/.test(v))   s++;
    const cols  = ['#EF4444','#F97316','#F59E0B','#A855F7','#00D9FF'];
    const labs  = ['Weak','Fair','Moderate','Strong','Very Strong'];
    const bar   = document.getElementById('psStrBar');
    const hint  = document.getElementById('psStrHint');
    bar.style.width      = (s/5*100) + '%';
    bar.style.background = cols[s-1] || 'transparent';
    hint.innerHTML = s > 0
        ? `<i class="fas fa-shield-halved" style="color:${cols[s-1]}"></i> ${labs[s-1]}`
        : `<i class="fas fa-shield"></i> Min 8 · Upper · Number · Symbol`;
}

/* ── Match indicator ── */
function psMatch() {
    const nw = document.getElementById('ps_np').value;
    const cf = document.getElementById('ps_cp').value;
    const el = document.getElementById('psMatchHint');
    if (!cf) { el.innerHTML='<i class="fas fa-check-circle"></i> Must match new password'; el.style.color=''; return; }
    if (nw === cf) { el.innerHTML='<i class="fas fa-check-circle"></i> Passwords match'; el.style.color='var(--cy)'; }
    else           { el.innerHTML='<i class="fas fa-times-circle"></i> Passwords do not match'; el.style.color='var(--pk)'; }
}

/* ── Toast ── */
function psToast(msg, type='ok') {
    const icons = { ok:'fa-circle-check', err:'fa-circle-exclamation', warn:'fa-triangle-exclamation' };
    const t = document.createElement('div');
    t.className = `ps-toast ps-toast-${type}`;
    t.innerHTML = `<i class="fas ${icons[type]}"></i><span>${msg}</span>`;
    document.getElementById('psToasts').appendChild(t);
    setTimeout(() => { t.style.animation='ps-tout .3s ease forwards'; setTimeout(()=>t.remove(),300); }, 4000);
}

/* ── Form validation ── */
document.getElementById('ps-form').addEventListener('submit', function(e) {
    document.querySelectorAll('.ps-input').forEach(el => el.classList.remove('ps-bad'));
    let ok = true;

    [['first_name','First Name'],['last_name','Last Name'],['phone','Phone']].forEach(([n]) => {
        const el = document.querySelector(`[name="${n}"]`);
        if (el && !el.value.trim()) { el.classList.add('ps-bad'); ok = false; }
    });

    if (!ok) { e.preventDefault(); psToast('Please fill in all required fields.','err'); return; }

    const cur = document.querySelector('[name="current_password"]').value;
    const nw  = document.getElementById('ps_np').value;
    const cf  = document.getElementById('ps_cp').value;

    if (cur || nw || cf) {
        if (!cur) { e.preventDefault(); psToast('Enter your current password.','err'); return; }
        if (!nw)  { e.preventDefault(); psToast('Enter a new password.','err'); return; }
        if (nw.length < 8) { e.preventDefault(); psToast('Password must be at least 8 characters.','err'); return; }
        if (!/[A-Z]/.test(nw)||!/[a-z]/.test(nw)||!/[0-9]/.test(nw)||!/[!@#$%^&*(),.?":{}|<>]/.test(nw)) {
            e.preventDefault(); psToast('Password needs uppercase, lowercase, number & symbol.','err'); return;
        }
        if (nw !== cf) { e.preventDefault(); psToast('Passwords do not match.','err'); return; }
    }

    const dob = document.querySelector('[name="date_of_birth"]').value;
    if (dob && Math.floor((Date.now()-new Date(dob))/(365.25*24*60*60*1000)) < 18) {
        e.preventDefault(); psToast('You must be at least 18 years old.','err'); return;
    }

    const btn = document.getElementById('psSaveBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
    btn.disabled = true;
});
</script>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>