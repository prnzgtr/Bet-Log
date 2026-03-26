<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT username, first_name, last_name, profile_image, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $user = []; }

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (!$displayName) $displayName = $user['username'] ?? 'Player';
$initials    = strtoupper(mb_substr($displayName,0,1) . (strpos($displayName,' ')!==false ? mb_substr(strrchr($displayName,' '),1,1) : ''));
$memberSince = !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '—';

$d = [];

try {
    $s = $conn->prepare("SELECT content_type, content_key FROM user_content_completions WHERE user_id = ?");
    $s->execute([$userId]); $comps = $s->fetchAll(PDO::FETCH_ASSOC);
    $d['completed_keys'] = array_column($comps, 'content_key');
    $d['lesson_count']   = count(array_filter($comps, fn($r) => $r['content_type'] === 'lesson'));
    $d['quiz_count']     = count(array_filter($comps, fn($r) => $r['content_type'] === 'quiz'));
} catch (Exception $e) { $d['completed_keys']=[]; $d['lesson_count']=0; $d['quiz_count']=0; }

try {
    $s = $conn->prepare("SELECT COALESCE(demo_credits,0) FROM users WHERE id=?"); $s->execute([$userId]);
    $d['balance'] = floatval($s->fetchColumn());
    $s = $conn->prepare("SELECT COALESCE(SUM(amount),0) FROM demo_credit_transactions WHERE user_id=? AND type IN ('earn','bonus','reset')"); $s->execute([$userId]);
    $d['total_earned'] = floatval($s->fetchColumn());
} catch (Exception $e) { $d['balance']=0; $d['total_earned']=0; }

try {
    $s = $conn->prepare("SELECT outcome, game_type FROM bet_log WHERE user_id=? ORDER BY id ASC"); $s->execute([$userId]);
    $bets = $s->fetchAll(PDO::FETCH_ASSOC);
    $d['total_bets']   = count($bets);
    $d['aviator_bets'] = count(array_filter($bets, fn($b) => $b['game_type']==='aviator'));
    $d['slot_bets']    = count(array_filter($bets, fn($b) => $b['game_type']==='slots'));
    $d['max_streak']   = 0; $streak = 0;
    foreach ($bets as $b) {
        if ($b['outcome']==='win') { $streak++; $d['max_streak'] = max($d['max_streak'], $streak); }
        else { $streak = 0; }
    }
} catch (Exception $e) { $d['total_bets']=0; $d['aviator_bets']=0; $d['slot_bets']=0; $d['max_streak']=0; }

try {
    $s = $conn->prepare("SELECT * FROM user_limits WHERE user_id=?"); $s->execute([$userId]);
    $lims = $s->fetch(PDO::FETCH_ASSOC) ?: [];
    $lf   = ['daily_loss','weekly_loss','monthly_loss','session_loss','max_single_bet','min_credits'];
    $d['limits_set_count'] = count(array_filter($lf, fn($f) => !empty($lims[$f])));
    $s = $conn->prepare("SELECT COUNT(DISTINCT DATE(created_at)) FROM bet_log WHERE user_id=?"); $s->execute([$userId]);
    $d['betting_days'] = intval($s->fetchColumn());
} catch (Exception $e) { $d['limits_set_count']=0; $d['betting_days']=0; }

$allAchievements = [
    ['key'=>'first_lesson',    'name'=>'First Step',      'desc'=>'Complete your first responsible gambling lesson.',          'cat'=>'education',   'rarity'=>'common',    'icon'=>'fa-book-open',      'color'=>'blue',   'xp'=>50,   'cr'=>25,
     'unlocked'=>$d['lesson_count']>=1,     'progress'=>min(100,$d['lesson_count']*100),               'prog_label'=>$d['lesson_count'].' / 1 lesson'],
    ['key'=>'halfway_scholar', 'name'=>'Halfway There',   'desc'=>'Complete 5 responsible gambling lessons.',                 'cat'=>'education',   'rarity'=>'uncommon',  'icon'=>'fa-book',           'color'=>'blue',   'xp'=>100,  'cr'=>50,
     'unlocked'=>$d['lesson_count']>=5,     'progress'=>min(100,round($d['lesson_count']/5*100)),       'prog_label'=>$d['lesson_count'].' / 5 lessons'],
    ['key'=>'all_lessons',     'name'=>'Scholar',         'desc'=>'Complete all 10 responsible gambling lessons.',            'cat'=>'education',   'rarity'=>'rare',      'icon'=>'fa-graduation-cap', 'color'=>'blue',   'xp'=>250,  'cr'=>100,
     'unlocked'=>$d['lesson_count']>=10,    'progress'=>min(100,round($d['lesson_count']/10*100)),      'prog_label'=>$d['lesson_count'].' / 10 lessons'],
    ['key'=>'quiz_starter',    'name'=>'Quiz Starter',    'desc'=>'Pass your first knowledge quiz.',                          'cat'=>'education',   'rarity'=>'common',    'icon'=>'fa-question-circle','color'=>'green',  'xp'=>50,   'cr'=>25,
     'unlocked'=>$d['quiz_count']>=1,       'progress'=>min(100,$d['quiz_count']*100),                  'prog_label'=>$d['quiz_count'].' / 1 quiz'],
    ['key'=>'quiz_master',     'name'=>'Quiz Master',     'desc'=>'Complete all 7 responsible gambling quizzes.',             'cat'=>'education',   'rarity'=>'rare',      'icon'=>'fa-brain',          'color'=>'purple', 'xp'=>300,  'cr'=>150,
     'unlocked'=>$d['quiz_count']>=7,       'progress'=>min(100,round($d['quiz_count']/7*100)),          'prog_label'=>$d['quiz_count'].' / 7 quizzes'],
    ['key'=>'myths_buster',    'name'=>'Myth Buster',     'desc'=>'Complete the Myths vs Facts module.',                      'cat'=>'education',   'rarity'=>'uncommon',  'icon'=>'fa-search',         'color'=>'teal',   'xp'=>100,  'cr'=>50,
     'unlocked'=>in_array('myths_complete',$d['completed_keys']),
     'progress'=>in_array('myths_complete',$d['completed_keys'])?100:0,
     'prog_label'=>in_array('myths_complete',$d['completed_keys'])?'Completed':'Not started'],
    ['key'=>'credits_500',     'name'=>'Half a Grand',    'desc'=>'Accumulate 500 total credits earned.',                     'cat'=>'credits',     'rarity'=>'uncommon',  'icon'=>'fa-piggy-bank',     'color'=>'gold',   'xp'=>100,  'cr'=>50,
     'unlocked'=>$d['total_earned']>=500,   'progress'=>min(100,round($d['total_earned']/500*100)),     'prog_label'=>number_format($d['total_earned']).' / 500 credits'],
    ['key'=>'credits_1000',    'name'=>'High Earner',     'desc'=>'Accumulate 1,000 total credits earned.',                   'cat'=>'credits',     'rarity'=>'rare',      'icon'=>'fa-star',           'color'=>'gold',   'xp'=>200,  'cr'=>100,
     'unlocked'=>$d['total_earned']>=1000,  'progress'=>min(100,round($d['total_earned']/1000*100)),    'prog_label'=>number_format($d['total_earned']).' / 1,000 credits'],
    ['key'=>'first_bet',       'name'=>'First Bet',       'desc'=>'Place your very first bet in any demo game.',              'cat'=>'betting',     'rarity'=>'common',    'icon'=>'fa-dice',           'color'=>'green',  'xp'=>25,   'cr'=>10,
     'unlocked'=>$d['total_bets']>=1,       'progress'=>min(100,$d['total_bets']*100),                  'prog_label'=>$d['total_bets'].' / 1 bet'],
    ['key'=>'aviator_fan',     'name'=>'Aviator Fan',     'desc'=>'Play 10 rounds of Aviator.',                               'cat'=>'betting',     'rarity'=>'common',    'icon'=>'fa-plane',          'color'=>'blue',   'xp'=>75,   'cr'=>30,
     'unlocked'=>$d['aviator_bets']>=10,    'progress'=>min(100,round($d['aviator_bets']/10*100)),      'prog_label'=>$d['aviator_bets'].' / 10 rounds'],
    ['key'=>'slot_devotee',    'name'=>'Slot Devotee',    'desc'=>'Spin the slot reels 20 times.',                            'cat'=>'betting',     'rarity'=>'uncommon',  'icon'=>'fa-th',             'color'=>'orange', 'xp'=>100,  'cr'=>50,
     'unlocked'=>$d['slot_bets']>=20,       'progress'=>min(100,round($d['slot_bets']/20*100)),          'prog_label'=>$d['slot_bets'].' / 20 spins'],
    ['key'=>'hot_streak',      'name'=>'Hot Streak',      'desc'=>'Win 5 bets in a row without a single loss.',               'cat'=>'betting',     'rarity'=>'rare',      'icon'=>'fa-fire',           'color'=>'red',    'xp'=>200,  'cr'=>100,
     'unlocked'=>$d['max_streak']>=5,       'progress'=>min(100,round($d['max_streak']/5*100)),          'prog_label'=>$d['max_streak'].' / 5 streak'],
    ['key'=>'veteran_bettor',  'name'=>'Veteran Bettor',  'desc'=>'Place a total of 50 bets across demo games.',              'cat'=>'betting',     'rarity'=>'rare',      'icon'=>'fa-medal',          'color'=>'gold',   'xp'=>200,  'cr'=>100,
     'unlocked'=>$d['total_bets']>=50,      'progress'=>min(100,round($d['total_bets']/50*100)),         'prog_label'=>$d['total_bets'].' / 50 bets'],
    ['key'=>'limit_setter',    'name'=>'Limit Setter',    'desc'=>'Set your first responsible gambling limit.',               'cat'=>'responsible', 'rarity'=>'common',    'icon'=>'fa-shield-alt',     'color'=>'green',  'xp'=>50,   'cr'=>50,
     'unlocked'=>$d['limits_set_count']>=1, 'progress'=>min(100,$d['limits_set_count']*100),             'prog_label'=>$d['limits_set_count'].' / 1 limit set'],
    ['key'=>'fully_protected', 'name'=>'Fully Protected', 'desc'=>'Set 4 or more different responsible gambling limits.',    'cat'=>'responsible', 'rarity'=>'epic',      'icon'=>'fa-lock',           'color'=>'gold',   'xp'=>400,  'cr'=>200,
     'unlocked'=>$d['limits_set_count']>=4, 'progress'=>min(100,round($d['limits_set_count']/4*100)),   'prog_label'=>$d['limits_set_count'].' / 4 limits set'],
    ['key'=>'educated_gambler','name'=>'Educated Gambler','desc'=>'Complete all lessons, all quizzes, and set 2+ limits.',   'cat'=>'responsible', 'rarity'=>'legendary', 'icon'=>'fa-crown',          'color'=>'gold',   'xp'=>1000, 'cr'=>500,
     'unlocked'=>$d['lesson_count']>=10&&$d['quiz_count']>=7&&$d['limits_set_count']>=2,
     'progress'=>min(100,round((min(1,$d['lesson_count']/10)+min(1,$d['quiz_count']/7)+min(1,$d['limits_set_count']/2))/3*100)),
     'prog_label'=>$d['lesson_count'].'/10 lessons · '.$d['quiz_count'].'/7 quizzes · '.$d['limits_set_count'].'/2 limits'],
];

// Load DB unlocks & award new ones
$unlockedInDB = [];
try {
    $s = $conn->prepare("SELECT achievement_key, unlocked_at FROM user_achievements WHERE user_id=?");
    $s->execute([$userId]);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) $unlockedInDB[$r['achievement_key']] = $r['unlocked_at'];
} catch (Exception $e) {}

$newlyUnlockedOnLoad = [];
foreach ($allAchievements as &$ach) {
    if ($ach['unlocked'] && !isset($unlockedInDB[$ach['key']])) {
        try {
            $ins = $conn->prepare("INSERT IGNORE INTO user_achievements (user_id,achievement_key,credits_awarded) VALUES (?,?,?)");
            $ins->execute([$userId, $ach['key'], $ach['cr']]);
            if ($ins->rowCount() > 0) {
                if ($ach['cr'] > 0) {
                    $conn->prepare("UPDATE users SET demo_credits=COALESCE(demo_credits,0)+? WHERE id=?")->execute([$ach['cr'],$userId]);
                    $s2 = $conn->prepare("SELECT COALESCE(demo_credits,0) FROM users WHERE id=?"); $s2->execute([$userId]); $nb = floatval($s2->fetchColumn());
                    $conn->prepare("INSERT INTO demo_credit_transactions (user_id,type,amount,balance_after,description,source) VALUES (?,'bonus',?,?,?,?)")
                         ->execute([$userId,$ach['cr'],$nb,'Achievement: '.$ach['name'],'achievement_'.$ach['key']]);
                }
                $unlockedInDB[$ach['key']] = date('Y-m-d H:i:s');
                $newlyUnlockedOnLoad[]     = $ach;
            }
        } catch (Exception $e) {}
    }
    if (isset($unlockedInDB[$ach['key']])) {
        $ach['unlocked']    = true;
        $ach['unlocked_at'] = $unlockedInDB[$ach['key']];
        $ach['progress']    = 100;
    } else {
        $ach['unlocked_at'] = null;
    }
}
unset($ach);

$unlockedCount   = count(array_filter($allAchievements, fn($a) => $a['unlocked']));
$inProgressCount = count(array_filter($allAchievements, fn($a) => !$a['unlocked'] && $a['progress'] > 0));
$totalCount      = count($allAchievements);
$completionPct   = $totalCount > 0 ? round($unlockedCount / $totalCount * 100) : 0;
$totalXP         = array_sum(array_map(fn($a) => $a['unlocked'] ? $a['xp'] : 0, $allAchievements));

$xpTh = [0,100,250,500,900,1400,2000,2800,3700,4800,6100,7600,9300,11200,13300,15800,18700,22000,26000,30500];
$xpLb = ['Bronze I','Bronze II','Bronze III','Silver I','Silver II','Silver III','Gold I','Gold II','Gold III','Platinum I','Platinum II','Platinum III','Diamond I','Diamond II','Diamond III','Master I','Master II','Grandmaster I','Grandmaster II','Legend'];
$level = 1;
foreach ($xpTh as $i => $t) { if ($totalXP >= $t) $level = $i + 1; }
$level      = min($level, count($xpLb));
$levelLabel = $xpLb[$level - 1];
$nextXP     = $xpTh[$level] ?? $xpTh[count($xpTh)-1];
$prevXP     = $xpTh[$level - 1] ?? 0;
$xpPct      = $nextXP > $prevXP ? round(($totalXP - $prevXP) / ($nextXP - $prevXP) * 100) : 100;
$xpToNext   = max(0, $nextXP - $totalXP);

// Color maps for icons and rarity
$iconFg  = ['gold'=>'#c8aa50','blue'=>'#70aaee','green'=>'#4cbb7a','purple'=>'#b080ee','orange'=>'#e08840','red'=>'#e05555','teal'=>'#40c0c0','grey'=>'#5a5a6a'];
$iconBg  = ['gold'=>'rgba(200,170,80,0.1)','blue'=>'rgba(80,140,220,0.1)','green'=>'rgba(60,180,100,0.1)','purple'=>'rgba(140,80,220,0.1)','orange'=>'rgba(220,130,50,0.1)','red'=>'rgba(220,60,60,0.1)','teal'=>'rgba(50,180,180,0.1)','grey'=>'rgba(90,90,106,0.1)'];
$rarityColor = ['common'=>'#6a6a7a','uncommon'=>'#4cbb7a','rare'=>'#70aaee','epic'=>'#b080ee','legendary'=>'#c8aa50'];
$rarityLabel = ['common'=>'Common','uncommon'=>'Uncommon','rare'=>'Rare','epic'=>'Epic','legendary'=>'Legendary'];
$catLabel    = ['education'=>'Education','credits'=>'Credits','betting'=>'Betting','responsible'=>'Responsible'];
$catIcon     = ['education'=>'fa-graduation-cap','credits'=>'fa-coins','betting'=>'fa-dice','responsible'=>'fa-shield-alt'];

$page_title = 'Achievements';
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>

.main-content { padding: 0 !important; }
.ap {
    padding: 28px 32px 40px;
    width: 100%;
    font-family: 'DM Sans', system-ui, sans-serif;
    color: #8a8aa0;
}

.ap-hero {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 24px;
    padding: 32px 32px 28px;
    background: #0d0f1c;
    border: 1px solid rgba(255,27,141,0.12);
}
.ap-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: none;
    pointer-events: none;
}
.ap-hero-inner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

/* Avatar */
.ap-avatar {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: rgba(255,27,141,0.08);
    border: 2px solid rgba(255,27,141,0.2);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Rajdhani', sans-serif;
    font-size: 22px; font-weight: 700;
    color: #FF1B8D;
    flex-shrink: 0; overflow: hidden;

}
.ap-avatar img { width: 100%; height: 100%; object-fit: cover; }

/* User info */
.ap-user-info { flex: 1; min-width: 160px; }
.ap-user-name {
    font-family: 'Rajdhani', sans-serif;
    font-size: 22px; font-weight: 700;
    color: #f0eaf8; letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.ap-user-meta { font-size: 12px; color: #3a3a55; }
.ap-user-level {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 8px;
    padding: 4px 12px;
    border-radius: 20px;
    background: rgba(200,170,80,0.08);
    border: 1px solid rgba(200,170,80,0.2);
    font-size: 11px; font-weight: 600;
    color: #c8aa50; letter-spacing: 0.5px;
}
.ap-user-level i { font-size: 9px; }

/* Stat pills */
.ap-hero-stats {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-left: auto;
}
.ap-hstat {
    display: flex; flex-direction: column;
    align-items: center;
    padding: 14px 20px;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    min-width: 80px;
    text-align: center;
    transition: border-color 0.2s, background 0.2s;
}
.ap-hstat:hover {
    border-color: rgba(255,27,141,0.15);
}
.ap-hstat-val {
    font-family: 'Rajdhani', sans-serif;
    font-size: 28px; font-weight: 700;
    color: #e8e0f8; line-height: 1;
    margin-bottom: 4px;
}
.ap-hstat-val sup { font-size: 14px; }
.ap-hstat-lbl {
    font-size: 9px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 1.2px;
    color: #2e2e48;
}

/* XP bar */
.ap-xp-row {
    position: relative;
    width: 100%;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,0.04);
}
.ap-xp-head {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 8px;
    font-size: 11px; color: #3a3a55;
}
.ap-xp-head strong { color: #c8aa50; font-weight: 600; }
.ap-xp-track {
    height: 6px;
    background: rgba(255,255,255,0.04);
    border-radius: 99px;
    overflow: hidden;
    position: relative;
}
.ap-xp-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #c8aa50, #f0d070);
    position: relative;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
}
.ap-xp-fill::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 40px; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35));
    border-radius: 99px;
    animation: xp-shimmer 2s ease-in-out infinite;
}
@keyframes xp-shimmer {
    0%, 100% { opacity: 0; }
    50% { opacity: 1; }
}

/* Badges */
.ap-badges-strip {
    display: flex; align-items: center; gap: 6px;
    margin-top: 12px; flex-wrap: wrap;
}
.ap-badges-lbl {
    font-size: 10px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 1px;
    color: #2e2e48; margin-right: 4px;
}
.ap-badge {
    width: 26px; height: 26px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px;
    border: 1px solid rgba(255,255,255,0.06);
    cursor: default;
    transition: transform 0.15s;
}
.ap-badge:hover { transform: scale(1.2); }

/* Filter bar */
.ap-filters {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    gap: 12px;
    flex-wrap: wrap;
}
.ap-tabs { display: flex; gap: 4px; flex-wrap: wrap; }
.ap-tab {
    padding: 7px 16px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.06);
    background: transparent;
    color: #3a3a55;
    font-size: 12px; font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: all 0.18s;
    letter-spacing: 0.2px;
}
.ap-tab:hover {
    color: #9090b0;
    border-color: rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.03);
}
.ap-tab.active {
    background: rgba(255,27,141,0.1);
    border-color: rgba(255,27,141,0.3);
    color: #FF1B8D;
    font-weight: 600;
}
.ap-filter-count { font-size: 12px; color: #2e2e48; }
.ap-filter-count strong { color: #7060a0; }

/* Grid */
.ap-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
@media (max-width: 1100px) { .ap-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 750px)  { .ap-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .ap-grid { grid-template-columns: 1fr; } }

/* Achievement card */
.ap-card {
    background: #0d0f1c;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 14px;
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    cursor: default;
}
.ap-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, var(--card-glow, transparent), transparent 70%);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
}
.ap-card:hover {
    transform: translateY(-3px);
    border-color: rgba(255,255,255,0.1);

}
.ap-card:hover::after { opacity: 1; }

.ap-card.is-locked {
    opacity: 0.35;
    filter: grayscale(0.5);
}
.ap-card.is-unlocked {
    border-color: rgba(var(--card-rgb, 255,255,255), 0.12);
    box-shadow: 0 0 0 0 transparent;
}
.ap-card.is-unlocked:hover {

}

/* Top shimmer line */
.ap-card-shine {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(var(--card-rgb,255,255,255),0.3), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.ap-card.is-unlocked .ap-card-shine { opacity: 1; }

/* Icon */
.ap-card-top { display: flex; align-items: flex-start; gap: 14px; }
.ap-ico {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
    background: var(--ico-bg, rgba(90,90,106,0.08));
    color: var(--ico-fg, #404055);
    border: 1px solid rgba(var(--card-rgb,90,90,106), 0.15);
    transition: transform 0.2s;
}
.ap-card:hover .ap-ico { transform: scale(1.08); }
.ap-card.is-locked .ap-ico {
    background: rgba(20,20,35,0.8);
    color: #252535;
    border-color: rgba(255,255,255,0.03);
}

.ap-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 15px; font-weight: 700;
    color: #e0d8f8; line-height: 1.2;
    margin-bottom: 4px; letter-spacing: 0.3px;
}
.ap-card.is-locked .ap-title { color: #252535; }
.ap-desc { font-size: 11px; color: #303048; line-height: 1.55; }
.ap-card.is-unlocked .ap-desc { color: #484860; }

/* Rarity + cat */
.ap-tags { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ap-rarity {
    font-size: 9px; font-weight: 700; letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 3px 8px; border-radius: 4px;
    background: rgba(var(--card-rgb,90,90,106), 0.08);
    color: var(--rarity-c, #404055);
    border: 1px solid rgba(var(--card-rgb,90,90,106), 0.15);
}
.ap-cat-tag {
    font-size: 10px; color: #282840;
    display: flex; align-items: center; gap: 4px;
}
.ap-cat-tag i { font-size: 8px; }

/* Progress bar */
.ap-prog-row {
    display: flex; justify-content: space-between;
    font-size: 10px; color: #303048; margin-bottom: 6px;
}
.ap-prog-row span:last-child { color: var(--ico-fg, #5a5a6a); font-weight: 600; }
.ap-prog-track {
    height: 4px;
    background: rgba(255,255,255,0.04);
    border-radius: 99px; overflow: hidden;
}
.ap-prog-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--ico-fg, #5a5a6a), color-mix(in srgb, var(--ico-fg, #5a5a6a) 70%, white));
    transition: width 1s cubic-bezier(0.4,0,0.2,1);
}

/* Footer */
.ap-card-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.04);
    font-size: 10px;
    margin-top: auto;
}
.ap-unlock-date {
    color: #303048; display: flex; align-items: center; gap: 5px;
}
.ap-unlock-date i { color: #4cbb7a; }
.ap-reward { display: flex; align-items: center; gap: 8px; }
.ap-reward .xp { color: #c8aa50; font-weight: 700; }
.ap-reward .cr { color: #4cbb7a; font-weight: 700; }

/* Unlocked glow pulse on new unlock */
@keyframes unlockPop {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.02); }
    100% { transform: scale(1); }
}
    50%  { box-shadow: 0 0 0 12px rgba(var(--card-rgb,255,255,255),0); transform: scale(1.02); }
    100% { box-shadow: 0 0 0 0 rgba(var(--card-rgb,255,255,255),0); transform: scale(1); }
}
.ap-card.just-unlocked { animation: unlockPop 0.7s ease forwards; }

/* Empty state */
.ap-empty {
    grid-column: 1 / -1;
    padding: 56px; text-align: center;
    color: #252535; font-size: 13px;
    display: none;
}
.ap-empty i { font-size: 32px; margin-bottom: 12px; display: block; }

/* ── Toast ── */
#ap-toasts {
    position: fixed; top: 90px; right: 20px; z-index: 9999;
    display: flex; flex-direction: column; gap: 8px;
    pointer-events: none;
}
.ap-toast {
    background: #0d0f1c;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 14px 18px;
    display: flex; align-items: center; gap: 14px;
    min-width: 260px;
    opacity: 0; transform: translateX(24px) scale(0.96);
    transition: opacity 0.3s cubic-bezier(0.34,1.56,0.64,1), transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
    pointer-events: auto;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
}
.ap-toast.show { opacity: 1; transform: translateX(0) scale(1); }
.ap-toast-ico {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.ap-toast-lbl {
    font-size: 9px; text-transform: uppercase;
    letter-spacing: 1px; color: #FF1B8D;
    font-weight: 700; margin-bottom: 2px;
}
.ap-toast-name {
    font-family: 'Rajdhani', sans-serif;
    font-size: 15px; font-weight: 700; color: #e0d8f8;
}
.ap-toast-cr { font-size: 11px; color: #4cbb7a; margin-top: 2px; font-weight: 600; }

/* ── Responsive ── */
@media (max-width: 700px) {
    .ap { padding: 16px; }
    .ap-hero { padding: 20px; }
    .ap-hero-inner { flex-direction: column; align-items: flex-start; }
    .ap-hero-stats { margin-left: 0; }
    .ap-hstat { padding: 10px 16px; }
}
</style>

<div id="ap-toasts"></div>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
    <div class="ap">

        <!-- Hero banner -->
        <div class="ap-hero">
            <div class="ap-hero-inner">
                <!-- Avatar + user -->
                <div class="ap-avatar">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="">
                    <?php else: ?>
                        <?php echo htmlspecialchars($initials); ?>
                    <?php endif; ?>
                </div>
                <div class="ap-user-info">
                    <div class="ap-user-name"><?php echo htmlspecialchars($displayName); ?></div>
                    <div class="ap-user-meta">Member since <?php echo $memberSince; ?></div>
                    <div class="ap-user-level">
                        <i class="fas fa-crown"></i>
                        Level <?php echo $level; ?> — <?php echo $levelLabel; ?>
                    </div>
                </div>

                <!-- Stats -->
                <div class="ap-hero-stats">
                    <div class="ap-hstat">
                        <div class="ap-hstat-val"><?php echo $unlockedCount; ?></div>
                        <div class="ap-hstat-lbl">Unlocked</div>
                    </div>
                    <div class="ap-hstat">
                        <div class="ap-hstat-val"><?php echo $inProgressCount; ?></div>
                        <div class="ap-hstat-lbl">In Progress</div>
                    </div>
                    <div class="ap-hstat">
                        <div class="ap-hstat-val"><?php echo $completionPct; ?><sup>%</sup></div>
                        <div class="ap-hstat-lbl">Complete</div>
                    </div>
                    <div class="ap-hstat">
                        <div class="ap-hstat-val"><?php echo number_format($totalXP); ?></div>
                        <div class="ap-hstat-lbl">Total XP</div>
                    </div>
                </div>
            </div>

            <!-- XP bar -->
            <div class="ap-xp-row">
                <div class="ap-xp-head">
                    <span>XP Progress &mdash; <strong><?php echo $levelLabel; ?></strong></span>
                    <span><?php echo number_format($totalXP); ?> / <?php echo number_format($nextXP); ?> &nbsp;·&nbsp; <?php echo number_format($xpToNext); ?> XP to Level <?php echo $level+1; ?></span>
                </div>
                <div class="ap-xp-track">
                    <div class="ap-xp-fill" style="width:<?php echo $xpPct; ?>%;"></div>
                </div>
            </div>

            <!-- Badges strip -->
            <?php $earnedAchs = array_values(array_filter($allAchievements, fn($a) => $a['unlocked'])); ?>
            <?php if ($earnedAchs): ?>
            <div class="ap-badges-strip">
                <span class="ap-badges-lbl">Badges</span>
                <?php foreach (array_slice($earnedAchs, 0, 20) as $b): ?>
                <div class="ap-badge"
                     style="background:<?php echo $iconBg[$b['color']]??'rgba(90,90,106,0.1)'; ?>;color:<?php echo $iconFg[$b['color']]??'#5a5a6a'; ?>;"
                     title="<?php echo htmlspecialchars($b['name']); ?>">
                    <i class="fas <?php echo $b['icon']; ?>"></i>
                </div>
                <?php endforeach; ?>
                <?php if (count($earnedAchs) > 20): ?>
                <div class="ap-badge" style="background:rgba(90,90,60,0.1);color:#6a6040;font-size:8px;font-weight:700;">
                    +<?php echo count($earnedAchs)-20; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="ap-filters">
            <div class="ap-tabs">
                <button class="ap-tab active" data-filter="all">All</button>
                <button class="ap-tab" data-filter="unlocked">Unlocked</button>
                <button class="ap-tab" data-filter="in-progress">In Progress</button>
                <button class="ap-tab" data-filter="education">Education</button>
                <button class="ap-tab" data-filter="credits">Credits</button>
                <button class="ap-tab" data-filter="betting">Betting</button>
                <button class="ap-tab" data-filter="responsible">Responsible</button>
            </div>
            <div class="ap-filter-count">
                <strong><?php echo $unlockedCount; ?></strong> of <?php echo $totalCount; ?> achievements unlocked
            </div>
        </div>

        <!-- Grid -->
        <div class="ap-grid" id="ap-grid">
        <?php foreach ($allAchievements as $ach):
            $isLocked   = !$ach['unlocked'] && $ach['progress'] === 0;
            $isProgress = !$ach['unlocked'] && $ach['progress'] > 0;
            $unlockDate = ($ach['unlocked'] && $ach['unlocked_at']) ? date('M j, Y', strtotime($ach['unlocked_at'])) : '';
            $fg  = $iconFg[$ach['color']]  ?? '#5a5a6a';
            $bg  = $iconBg[$ach['color']]  ?? 'rgba(90,90,106,0.1)';
            $rc  = $rarityColor[$ach['rarity']] ?? '#5a5a6a';
            // Extract RGB from fg for glow effects
            $rgbMap = ['gold'=>'200,170,80','blue'=>'80,140,220','green'=>'60,180,100','purple'=>'140,80,220','orange'=>'220,130,50','red'=>'220,60,60','teal'=>'50,180,180','grey'=>'90,90,106'];
            $rgb = $rgbMap[$ach['color']] ?? '90,90,106';
            $cardClass = $isLocked ? 'is-locked' : ($ach['unlocked'] ? 'is-unlocked' : 'is-progress');
        ?>
            <div class="ap-card <?php echo $cardClass; ?>"
                 style="--card-rgb:<?php echo $rgb; ?>;--card-glow:rgba(<?php echo $rgb; ?>,0.08);--ico-bg:<?php echo $bg; ?>;--ico-fg:<?php echo $fg; ?>;--rarity-c:<?php echo $rc; ?>;"
                 data-cat="<?php echo $ach['cat']; ?>"
                 data-unlocked="<?php echo $ach['unlocked']?'1':'0'; ?>"
                 data-progress="<?php echo $isProgress?'1':'0'; ?>">

                <div class="ap-card-shine"></div>

                <!-- Icon + title -->
                <div class="ap-card-top">
                    <div class="ap-ico">
                        <i class="fas <?php echo $isLocked ? 'fa-lock' : $ach['icon']; ?>"></i>
                    </div>
                    <div>
                        <div class="ap-title"><?php echo htmlspecialchars($ach['name']); ?></div>
                        <div class="ap-desc"><?php echo htmlspecialchars($ach['desc']); ?></div>
                    </div>
                </div>

                <!-- Rarity + category -->
                <div class="ap-tags">
                    <span class="ap-rarity"><?php echo $rarityLabel[$ach['rarity']]; ?></span>
                    <span class="ap-cat-tag">
                        <i class="fas <?php echo $catIcon[$ach['cat']]; ?>" style="font-size:8px;"></i>
                        <?php echo $catLabel[$ach['cat']]; ?>
                    </span>
                </div>

                <!-- Progress (in-progress only) -->
                <?php if ($isProgress): ?>
                <div>
                    <div class="ap-prog-row">
                        <span><?php echo htmlspecialchars($ach['prog_label']); ?></span>
                        <span><?php echo $ach['progress']; ?>%</span>
                    </div>
                    <div class="ap-prog-track">
                        <div class="ap-prog-fill" style="width:<?php echo $ach['progress']; ?>%;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="ap-card-footer">
                    <?php if ($ach['unlocked']): ?>
                        <span class="ap-unlock-date">
                            <i class="fas fa-check-circle"></i> <?php echo $unlockDate; ?>
                        </span>
                    <?php elseif ($isLocked): ?>
                        <span style="font-size:10px;color:#2a2a3a;">Locked</span>
                    <?php else: ?>
                        <span style="font-size:10px;color:#3a3a4a;">In progress</span>
                    <?php endif; ?>
                    <div class="ap-reward">
                        <span class="xp">+<?php echo number_format($ach['xp']); ?> XP</span>
                        <?php if ($ach['cr'] > 0): ?>
                        <span class="cr"><?php echo $ach['unlocked'] ? '+'.$ach['cr'].' cr earned' : '+'.$ach['cr'].' cr'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
            <div class="ap-empty" id="ap-empty">
                <i class="fas fa-search"></i>
                No achievements match this filter.
            </div>
        </div>

    </div>
    </main>
</div>

<script>
// Filter
const tabs  = document.querySelectorAll('.ap-tab');
const cards = document.querySelectorAll('#ap-grid .ap-card');
const empty = document.getElementById('ap-empty');

tabs.forEach(tab => {
    tab.addEventListener('click', function() {
        tabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const f = this.dataset.filter;
        let vis = 0;
        cards.forEach(c => {
            const show = f === 'all'         ? true
                       : f === 'unlocked'    ? c.dataset.unlocked === '1'
                       : f === 'in-progress' ? c.dataset.progress === '1'
                       : c.dataset.cat === f;
            c.style.display = show ? '' : 'none';
            if (show) vis++;
        });
        empty.style.display = vis === 0 ? 'block' : 'none';
    });
});

// Toast
function showToast(name, credits, iconClass, color) {
    const fg = {gold:'#c8aa50',blue:'#70aaee',green:'#4cbb7a',purple:'#b080ee',orange:'#e08840',red:'#e05555',teal:'#40c0c0'};
    const bg = {gold:'rgba(200,170,80,0.1)',blue:'rgba(80,140,220,0.1)',green:'rgba(60,180,100,0.1)',purple:'rgba(140,80,220,0.1)',orange:'rgba(220,130,50,0.1)',red:'rgba(220,60,60,0.1)',teal:'rgba(50,180,180,0.1)'};
    const wrap = document.getElementById('ap-toasts');
    const t = document.createElement('div');
    t.className = 'ap-toast';
    t.innerHTML = `
        <div class="ap-toast-ico" style="background:${bg[color]||bg.gold};color:${fg[color]||fg.gold};">
            <i class="fas ${iconClass}"></i>
        </div>
        <div>
            <div class="ap-toast-lbl">Achievement Unlocked</div>
            <div class="ap-toast-name">${name}</div>
            ${credits > 0 ? `<div class="ap-toast-cr">+${credits} credits awarded</div>` : ''}
        </div>`;
    wrap.appendChild(t);
    requestAnimationFrame(() => requestAnimationFrame(() => t.classList.add('show')));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 4000);
}

const freshUnlocks = <?php echo json_encode(array_map(fn($a) => ['name'=>$a['name'],'cr'=>$a['cr'],'icon'=>$a['icon'],'color'=>$a['color']], $newlyUnlockedOnLoad)); ?>;
freshUnlocks.forEach((a, i) => setTimeout(() => showToast(a.name, a.cr, a.icon, a.color), i * 900));
</script>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>