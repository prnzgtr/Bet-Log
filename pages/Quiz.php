<?php
require_once '../includes/config.php';
$page_title = 'Knowledge Quiz';
$userId = $_SESSION['user_id'] ?? null;

$completedQuizzes = [];
$currentBalance   = 0;

if ($userId) {
    try {
        $stmt = $conn->prepare(
            "SELECT content_key FROM user_content_completions WHERE user_id = ? AND content_type = 'quiz'"
        );
        $stmt->execute([$userId]);
        $completedQuizzes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}

    try {
        $stmt = $conn->prepare("SELECT COALESCE(demo_credits, 0) FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentBalance = intval($stmt->fetchColumn());
    } catch (PDOException $e) {}
}

include '../includes/header.php';
?>

<style>
/* ══ QUIZ PAGE ══ */
.quiz-page { font-family: 'Segoe UI', system-ui, sans-serif; }

/* ── Hero ── */
.quiz-hero {
    background: linear-gradient(135deg, #0c0e1c 0%, #0e1025 60%, #0c0e1c 100%);
    border: 1px solid rgba(255,27,141,0.12);
    border-radius: 18px; padding: 28px 30px;
    margin-bottom: 28px; position: relative; overflow: hidden;
}
.quiz-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(168,85,247,0.1) 0%, transparent 70%);
    pointer-events: none;
}
.quiz-hero::after {
    content: '';
    position: absolute; bottom: -40px; left: -40px;
    width: 160px; height: 160px;
    background: radial-gradient(circle, rgba(255,27,141,0.07) 0%, transparent 70%);
    pointer-events: none;
}
.qh-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
.qh-title h2 { font-size: 22px; font-weight: 800; color: #f0e8d0; margin: 0 0 5px; }
.qh-title p  { font-size: 13px; color: #5a5a7a; margin: 0; }
.qh-stats { display: flex; gap: 10px; flex-wrap: wrap; }
.qh-stat {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px; padding: 10px 18px; text-align: center; min-width: 80px;
}
.qh-stat-val { font-size: 20px; font-weight: 800; color: #fff; line-height: 1; }
.qh-stat-val.pink   { color: var(--primary-pink); }
.qh-stat-val.green  { color: #4cbb7a; }
.qh-stat-val.gold   { color: #FFD700; }
.qh-stat-lbl { font-size: 9px; color: #4a4a6a; text-transform: uppercase; letter-spacing: 0.7px; margin-top: 3px; }
.qh-credits-row {
    display: flex; align-items: center; gap: 14px;
    padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.05);
    flex-wrap: wrap;
}
.qh-credits-pill {
    display: flex; align-items: center; gap: 9px;
    background: rgba(76,187,122,0.07);
    border: 1px solid rgba(76,187,122,0.18);
    border-radius: 10px; padding: 7px 16px;
}
.qh-credits-num { font-size: 19px; font-weight: 800; color: #4cbb7a; line-height: 1; }
.qh-credits-lbl { font-size: 9px; color: #3d6e50; text-transform: uppercase; letter-spacing: 0.7px; margin-top: 2px; }
.qh-play-link {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 9px;
    background: rgba(76,187,122,0.1); border: 1px solid rgba(76,187,122,0.25);
    color: #4cbb7a; font-size: 12px; font-weight: 700;
    text-decoration: none; transition: all 0.18s;
}
.qh-play-link:hover { background: rgba(76,187,122,0.2); }

/* ── Quiz cards grid ── */
.quiz-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px; margin-bottom: 8px;
}

.quiz-card {
    background: #0d0f1e;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px; overflow: hidden;
    cursor: pointer; transition: all 0.22s;
    position: relative;
}
.quiz-card:hover:not(.is-done):not(.is-locked) {
    border-color: rgba(255,27,141,0.35);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(255,27,141,0.1);
}
.quiz-card.is-done { border-color: rgba(76,187,122,0.22); cursor: default; }
.quiz-card.is-locked { opacity: 0.55; cursor: not-allowed; }

.qc-accent { height: 3px; width: 100%; }

.qc-body { padding: 20px 20px 16px; }
.qc-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 14px;
    background: rgba(255,255,255,0.05);
}
.qc-tag {
    font-size: 9px; font-weight: 800; letter-spacing: 1.2px;
    text-transform: uppercase; margin-bottom: 4px;
}
.qc-title { font-size: 15px; font-weight: 700; color: #e8e0d0; margin-bottom: 8px; line-height: 1.3; }
.qc-desc  { font-size: 12px; color: #5a5a7a; line-height: 1.6; margin-bottom: 14px; }

.qc-meta {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.05);
}
.qc-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10px; font-weight: 700;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 20px; padding: 3px 9px; color: #5a5a7a;
}
.qc-pill.reward { background: rgba(255,215,0,0.06); border-color: rgba(255,215,0,0.15); color: rgba(255,215,0,0.7); }
.qc-pill.done   { background: rgba(76,187,122,0.1);  border-color: rgba(76,187,122,0.25); color: #4cbb7a; }
.qc-start-btn {
    margin-left: auto; display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 8px;
    background: var(--primary-pink); border: none;
    color: #fff; font-size: 11px; font-weight: 700;
    cursor: pointer; transition: all 0.18s;
}
.qc-start-btn:hover { background: #ff3da0; }
.qc-done-badge {
    margin-left: auto; display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; color: #4cbb7a;
}

/* ══ QUIZ MODAL (full-screen overlay) ══ */
.quiz-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(4,5,14,0.97);
    z-index: 5000; align-items: center; justify-content: center;
    padding: 20px;
}
.quiz-overlay.active { display: flex; }

.quiz-modal {
    background: #0d0f1e;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 22px; width: 100%; max-width: 600px;
    overflow: hidden; animation: slide-up 0.28s ease;
    max-height: 90vh; display: flex; flex-direction: column;
}
@keyframes slide-up {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}

/* Modal header */
.qm-header {
    padding: 22px 26px 18px; flex-shrink: 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.qm-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.qm-title { font-size: 17px; font-weight: 800; color: #f0e8d0; }
.qm-close {
    width: 32px; height: 32px; border-radius: 8px;
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
    color: #6a6a7a; font-size: 13px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.18s;
}
.qm-close:hover { background: rgba(239,68,68,0.12); color: #f87171; border-color: rgba(239,68,68,0.2); }

/* Progress bar */
.qm-progress-wrap { position: relative; }
.qm-progress-label {
    display: flex; justify-content: space-between;
    font-size: 10px; color: #4a4a6a; margin-bottom: 6px;
}
.qm-progress-track {
    height: 5px; background: rgba(255,255,255,0.06); border-radius: 10px; overflow: hidden;
}
.qm-progress-fill {
    height: 100%; background: linear-gradient(90deg, #FF1B8D, #A855F7);
    border-radius: 10px; transition: width 0.4s ease;
}

/* Modal body — scrollable */
.qm-body { padding: 24px 26px; overflow-y: auto; flex: 1; }

/* Question */
.qm-question-num {
    font-size: 10px; font-weight: 800; letter-spacing: 1px;
    text-transform: uppercase; color: var(--primary-pink); margin-bottom: 8px;
}
.qm-question-text {
    font-size: 17px; font-weight: 700; color: #f0e8d0;
    line-height: 1.5; margin-bottom: 22px;
}

/* Answer options */
.qm-options { display: flex; flex-direction: column; gap: 10px; }
.qm-option {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 16px; border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.02);
    cursor: pointer; transition: all 0.18s;
    font-size: 14px; color: #c8c0b0; line-height: 1.4;
}
.qm-option:hover:not(.answered) {
    border-color: rgba(255,27,141,0.4);
    background: rgba(255,27,141,0.05);
    color: #f0e8d0;
}
.qm-option.selected { border-color: var(--primary-pink); background: rgba(255,27,141,0.08); color: #f0e8d0; }
.qm-option.correct  { border-color: #4cbb7a; background: rgba(76,187,122,0.1); color: #e0f0e8; }
.qm-option.wrong    { border-color: #f87171; background: rgba(239,68,68,0.08); color: #f0d8d8; }
.qm-option.reveal-correct { border-color: rgba(76,187,122,0.4); background: rgba(76,187,122,0.05); }

.qm-option-letter {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    background: rgba(255,255,255,0.06); color: #5a5a7a;
    transition: all 0.18s;
}
.qm-option.selected .qm-option-letter { background: var(--primary-pink); color: #fff; }
.qm-option.correct  .qm-option-letter { background: #4cbb7a; color: #fff; }
.qm-option.wrong    .qm-option-letter { background: #f87171; color: #fff; }
.qm-option.reveal-correct .qm-option-letter { background: rgba(76,187,122,0.3); color: #4cbb7a; }

/* Explanation box */
.qm-explanation {
    display: none;
    margin-top: 16px; padding: 14px 16px;
    border-radius: 10px; font-size: 13px; line-height: 1.6;
}
.qm-explanation.correct-exp { background: rgba(76,187,122,0.08); border: 1px solid rgba(76,187,122,0.2); color: #a0d8b0; }
.qm-explanation.wrong-exp   { background: rgba(239,68,68,0.06);  border: 1px solid rgba(239,68,68,0.18); color: #d0a8a8; }
.qm-explanation i { margin-right: 6px; }

/* Modal footer */
.qm-footer {
    padding: 16px 26px 22px; flex-shrink: 0;
    border-top: 1px solid rgba(255,255,255,0.06);
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.qm-score-badge {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #5a5a7a;
}
.qm-score-badge strong { color: #FFD700; font-size: 16px; }
.qm-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 11px 24px; border-radius: 10px;
    font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.18s; border: none;
}
.qm-btn.primary { background: var(--gradient-primary); color: #fff; }
.qm-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(255,27,141,0.35); }
.qm-btn.secondary { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #aaa; }
.qm-btn.secondary:hover { border-color: rgba(255,255,255,0.2); color: #fff; }
.qm-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

/* ── Results screen ── */
.qm-results { text-align: center; padding: 10px 0; }
.qm-results-icon { font-size: 64px; margin-bottom: 16px; line-height: 1; }
.qm-results-title { font-size: 24px; font-weight: 800; color: #f0e8d0; margin-bottom: 8px; }
.qm-results-sub   { font-size: 14px; color: #5a5a7a; margin-bottom: 24px; line-height: 1.6; }
.qm-results-score {
    display: inline-flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px; padding: 16px 28px; margin-bottom: 24px;
}
.qm-results-score-num { font-size: 42px; font-weight: 900; color: #FFD700; line-height: 1; }
.qm-results-score-lbl { font-size: 11px; color: #5a5a7a; text-transform: uppercase; letter-spacing: 0.7px; margin-top: 3px; }
.qm-credits-earned {
    display: inline-flex; align-items: center; gap: 10px;
    background: rgba(76,187,122,0.08); border: 1px solid rgba(76,187,122,0.25);
    border-radius: 12px; padding: 12px 22px; margin-bottom: 8px;
    font-size: 14px; color: #4cbb7a; font-weight: 700;
}
.qm-credits-earned .amt { font-size: 22px; font-weight: 900; }
.qm-pass-bar {
    display: flex; align-items: center; gap: 6px;
    justify-content: center; font-size: 12px; color: #4a4a6a; margin-bottom: 20px;
}
.qm-pass-bar .pass-mark { color: #4cbb7a; font-weight: 700; }

/* ── Toast ── */
.credit-toast {
    position: fixed; top: 24px; right: 24px; z-index: 9999;
    background: #0f1f18; border: 1px solid rgba(76,187,122,0.45);
    border-radius: 14px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    transform: translateX(130%);
    transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
    min-width: 260px; max-width: 320px;
}
.credit-toast.show { transform: translateX(0); }
.credit-toast-title { font-size: 13px; font-weight: 700; color: #4cbb7a; margin-bottom: 2px; }
.credit-toast-sub   { font-size: 11px; color: #5a8a6a; line-height: 1.4; }
.credit-toast-amount { margin-left: auto; font-size: 20px; font-weight: 800; color: #4cbb7a; flex-shrink: 0; }
</style>

<?php

// ── Quiz data ──
$quizzes = [
    [
        'key'    => 'quiz_1',
        'title'  => 'Responsible Gambling Basics',
        'tag'    => 'Beginner',
        'color'  => '#FF1B8D',
        'icon'   => 'fas fa-shield-alt',
        'desc'   => 'Test your understanding of the core principles of responsible gambling.',
        'reward' => 75,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'What is the best way to think about money spent on gambling?',
             'opts'=>['A way to earn extra income','Entertainment spending you are prepared to lose','An investment with potential returns','A skill-based profit activity'],
             'answer'=>1,'exp'=>'Gambling should always be treated as entertainment spending — like paying for a movie or concert. Never gamble money you cannot afford to lose.'],
            ['q'=>'Which of the following is a core principle of responsible gambling?',
             'opts'=>['Gambling more after a win to maximise profits','Believing gambling can solve financial problems','Keeping gambling balanced with other life activities','Chasing losses to win money back'],
             'answer'=>2,'exp'=>'Responsible gambling means keeping it balanced alongside other activities. It should never dominate your time, money, or emotions.'],
            ['q'=>'When is the best time to set limits for a gambling session?',
             'opts'=>['After you have lost your first bet','When you feel like stopping','Before you start playing, while calm','When you are ahead and feeling confident'],
             'answer'=>2,'exp'=>'Limits must be set before you start — not during a session when emotions and excitement can cloud your judgement.'],
            ['q'=>'What does the "house edge" mean?',
             'opts'=>['The casino gives players a built-in advantage','The physical structure of the casino building','A mathematical advantage ensuring the casino wins over time','A bonus given to loyal customers'],
             'answer'=>2,'exp'=>'The house edge is the mathematical advantage built into every casino game ensuring the casino profits over time. It cannot be eliminated by any strategy.'],
            ['q'=>'Which statement about responsible gambling is TRUE?',
             'opts'=>['Skilled players can beat any casino game consistently','Gambling should be fun, not a source of stress or income','The more you gamble, the better your odds become','Winning streaks prove you have a system that works'],
             'answer'=>1,'exp'=>'Responsible gambling is about fun and entertainment. The moment it causes stress, financial pressure, or feels like a job, something has gone wrong.'],
        ],
    ],
    [
        'key'    => 'quiz_2',
        'title'  => 'Warning Signs & Problem Gambling',
        'tag'    => 'Awareness',
        'color'  => '#F59E0B',
        'icon'   => 'fas fa-exclamation-triangle',
        'desc'   => 'Learn to identify the warning signs of problem gambling in yourself and others.',
        'reward' => 75,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'Which of the following is a financial warning sign of problem gambling?',
             'opts'=>['Setting a budget before gambling','Taking regular breaks','Borrowing money to gamble or cover gambling debts','Playing only with entertainment money'],
             'answer'=>2,'exp'=>'Borrowing money to gamble — or to pay off gambling debts — is one of the most serious financial warning signs of a developing gambling problem.'],
            ['q'=>'What is "chasing losses" in gambling?',
             'opts'=>['Trying to find the best odds across different casinos','Gambling more to try to win back money you have already lost','Tracking your wins and losses in a journal','Playing multiple games simultaneously'],
             'answer'=>1,'exp'=>'Chasing losses — gambling more to recover money you have lost — is one of the most dangerous gambling behaviours and a key warning sign of problem gambling.'],
            ['q'=>'Problem gambling is best described as:',
             'opts'=>['A sign of weak character or poor willpower','A rare condition affecting only extreme cases','A recognised behavioural health disorder that can affect anyone','Something that only affects low-income individuals'],
             'answer'=>2,'exp'=>'Problem gambling is a recognised health disorder that can affect people of any background, income level, or personality type. It is never a sign of weakness.'],
            ['q'=>'How many warning signs should prompt you to consider seeking help?',
             'opts'=>['At least ten very serious signs','Only if you are losing money every single day','Two or more warning signs','Only when friends or family raise concerns'],
             'answer'=>2,'exp'=>'If you recognise two or more warning signs — financial, emotional, or behavioural — it is worth speaking to a professional. Early help leads to better outcomes.'],
            ['q'=>'Gambling to escape stress or problems is an example of which type of warning sign?',
             'opts'=>['Financial warning sign','Emotional and behavioural warning sign','Physical warning sign','Social warning sign'],
             'answer'=>1,'exp'=>'Using gambling as an escape from problems or negative emotions is an emotional and behavioural warning sign. Gambling should never be used as a coping mechanism.'],
        ],
    ],
    [
        'key'    => 'quiz_3',
        'title'  => 'Psychology & Cognitive Biases',
        'tag'    => 'Psychology',
        'color'  => '#A855F7',
        'icon'   => 'fas fa-brain',
        'desc'   => 'Understand how your brain can trick you while gambling.',
        'reward' => 100,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'What is the "Gambler\'s Fallacy"?',
             'opts'=>['The belief that casinos always cheat','Believing a win is "due" after a series of losses','The idea that professional gamblers always win','Thinking that betting systems beat the house'],
             'answer'=>1,'exp'=>'The Gambler\'s Fallacy is the mistaken belief that previous outcomes affect future ones. Each spin, roll, or hand is completely independent — a losing streak does not make a win more likely.'],
            ['q'=>'What does "Variable Ratio Reinforcement" mean in gambling?',
             'opts'=>['Casinos vary the amount of chips given to players','Random, unpredictable rewards are the most psychologically compelling','Jackpot sizes vary based on the number of players','The return percentage changes over time'],
             'answer'=>1,'exp'=>'Variable ratio reinforcement — random, unpredictable rewards — is the most powerful form of psychological conditioning. It is the exact mechanism that makes slot machines so compelling.'],
            ['q'=>'What is the "Near-Miss Effect"?',
             'opts'=>['When a player almost catches a cheating dealer','When almost winning feels like progress and encourages more play','When a player narrowly avoids losing everything','When two players both win at the same time'],
             'answer'=>1,'exp'=>'Near misses are deliberately programmed into slot machines to feel psychologically meaningful. Mathematically, a near miss is identical to any other loss — but it feels like you were close, driving continued play.'],
            ['q'=>'Why do gamblers often remember wins more vividly than losses?',
             'opts'=>['Wins are much more common than losses','The brain naturally highlights emotionally positive events — Confirmation Bias','Casinos use technology to enhance win celebrations','Wins happen at the end of sessions'],
             'answer'=>1,'exp'=>'Confirmation Bias causes our brains to remember and emphasise wins while minimising or forgetting losses. This distorts our sense of how often we actually win, making gambling seem more profitable than it is.'],
            ['q'=>'What is the "Sunk Cost Fallacy" in gambling?',
             'opts'=>['Believing that a new slot machine will be luckier','Thinking you must keep playing to recover money already lost','Choosing games based on their age','Believing the casino owes you a win'],
             'answer'=>1,'exp'=>'The Sunk Cost Fallacy is the belief that money already lost justifies continued gambling to win it back. This is a logical trap — past losses cannot be changed and should not drive future decisions.'],
        ],
    ],
    [
        'key'    => 'quiz_4',
        'title'  => 'Odds, Mathematics & House Edge',
        'tag'    => 'Mathematics',
        'color'  => '#6366F1',
        'icon'   => 'fas fa-calculator',
        'desc'   => 'Master the numbers behind casino games and what they really mean.',
        'reward' => 100,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'Which casino game typically has the LOWEST house edge?',
             'opts'=>['American Roulette (5.26%)','Keno (25-40%)','Blackjack with optimal strategy (0.5%)','High-variance slots (3-8%)'],
             'answer'=>2,'exp'=>'Blackjack, played with optimal strategy, has a house edge of around 0.5% — the lowest of any common casino game. This means for every £100 wagered, the expected loss is only about 50p on average.'],
            ['q'=>'What does RTP (Return to Player) of 96% on a slot machine mean?',
             'opts'=>['You will receive 96p back for every £1 you personally bet','Across millions of spins, 96% of total bets are returned to players on average','The machine pays out 96 times per hour','96 out of 100 players will profit'],
             'answer'=>1,'exp'=>'RTP is calculated across millions of spins, not individual sessions. A 96% RTP means over the long run, the machine returns £96 for every £100 wagered. In any single session, results can vary wildly.'],
            ['q'=>'Why do shorter gambling sessions give you a better chance of winning?',
             'opts'=>['Casinos are more generous at the start of sessions','Short sessions are subject to less variance from the house edge','Dealers make more mistakes early in a shift','Short sessions always beat the house'],
             'answer'=>1,'exp'=>'The longer you play, the more your results converge toward the house edge. Short sessions allow variance to work in your favour temporarily — the house edge catches up over time.'],
            ['q'=>'Can any betting system (like Martingale) eliminate the house edge?',
             'opts'=>['Yes, Martingale is mathematically proven to work','Yes, but only on European Roulette','No — betting systems manage bankroll but cannot change game mathematics','Yes, if used perfectly'],
             'answer'=>2,'exp'=>'No betting system can change the mathematical house edge. The Martingale system requires infinite bankroll and has no maximum bet limit — both impossible in reality. Every bet still carries the same house edge.'],
            ['q'=>'European Roulette has a house edge of 2.7%. American Roulette has 5.26%. What causes the difference?',
             'opts'=>['American dealers spin the wheel faster','American Roulette adds a second green "00" pocket to the wheel','European casinos are more generous by regulation','American wheels are rigged more heavily'],
             'answer'=>1,'exp'=>'American Roulette adds a second green "00" pocket alongside the "0", increasing the number of non-winning outcomes and nearly doubling the house edge from 2.7% to 5.26%.'],
        ],
    ],
    [
        'key'    => 'quiz_5',
        'title'  => 'Getting Help & Recovery',
        'tag'    => 'Support',
        'color'  => '#10B981',
        'icon'   => 'fas fa-hand-holding-heart',
        'desc'   => 'Know where to turn and what recovery looks like.',
        'reward' => 75,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'What is the National Problem Gambling Helpline number in the US?',
             'opts'=>['1-800-GAMBLER (1-800-522-4700)','988','1-800-123-HELP','1-800-PROBLEM'],
             'answer'=>0,'exp'=>'The National Problem Gambling Helpline is 1-800-522-4700 (1-800-GAMBLER). It is free, confidential, and available 24 hours a day, 7 days a week.'],
            ['q'=>'Which type of therapy is considered the gold standard for treating gambling disorder?',
             'opts'=>['Hypnotherapy','Cognitive Behavioural Therapy (CBT)','Art therapy','Physical exercise therapy'],
             'answer'=>1,'exp'=>'Cognitive Behavioural Therapy (CBT) is considered the most effective treatment for gambling disorder. It helps identify and challenge distorted thinking patterns and builds healthier coping strategies.'],
            ['q'=>'What is "self-exclusion" in gambling?',
             'opts'=>['Choosing to only play games you enjoy','Excluding yourself from casino promotions and emails','A formal agreement to block your own access to gambling for a set period','Limiting your session to one hour maximum'],
             'answer'=>2,'exp'=>'Self-exclusion is a formal agreement — with a site, casino, or national scheme like GamStop — to block your own access to gambling for a chosen period. Research shows it is highly effective.'],
            ['q'=>'When is the best time to seek help for a gambling problem?',
             'opts'=>['Only after you have lost everything','When gambling has caused any noticeable concern — the earlier the better','Only if a doctor formally diagnoses you','After at least two years of problem gambling'],
             'answer'=>1,'exp'=>'The earlier help is sought, the better the outcome. You do not need to hit rock bottom. If gambling is causing any financial, emotional, or relationship concern, reaching out now is always the right choice.'],
            ['q'=>'What is "urge surfing" as a relapse prevention tool?',
             'opts'=>['Swimming as a distraction from gambling urges','Searching online for gambling urge reduction techniques','Observing a craving without acting on it until it passes naturally','Playing small bets to satisfy urges safely'],
             'answer'=>2,'exp'=>'Urge surfing means observing a gambling craving without acting on it — like watching a wave rise and fall. Urges typically peak and pass within 15-30 minutes. This technique breaks the automatic link between urge and action.'],
        ],
    ],
    [
        'key'    => 'quiz_6',
        'title'  => 'Bankroll Management & Mental Health',
        'tag'    => 'Management',
        'color'  => '#06B6D4',
        'icon'   => 'fas fa-wallet',
        'desc'   => 'Learn how to manage your gambling budget and protect your mental wellbeing.',
        'reward' => 100,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'What is "bankroll management" in gambling?',
             'opts'=>['Depositing the maximum allowed amount to access all games','Deciding in advance how much money you are willing to risk and sticking to it','Tracking which games pay out the most','Spreading bets across multiple casinos to reduce risk'],
             'answer'=>1,'exp'=>'Bankroll management means setting a fixed amount you are comfortable losing before you start, then strictly sticking to it. It transforms gambling into a controlled entertainment activity rather than a financial risk.'],
            ['q'=>'What is the "1-5% rule" commonly recommended for gambling bankrolls?',
             'opts'=>['Always bet between 1% and 5% of your total net worth','Never spend more than 1-5% of your monthly income on gambling entertainment','Place no single bet larger than 1-5% of your session bankroll','Limit gambling to 1-5 hours per week'],
             'answer'=>1,'exp'=>'A widely recommended guideline is to allocate no more than 1-5% of your monthly disposable income to gambling entertainment. This keeps losses within a range that will not cause financial stress regardless of outcomes.'],
            ['q'=>'Which of the following best describes "tilt" in gambling?',
             'opts'=>['A technical fault causing a slot machine to malfunction','Gambling emotionally after a loss or win, leading to poor decision-making','The angle at which a roulette wheel is set','A strategy for managing multiple bets simultaneously'],
             'answer'=>1,'exp'=>'"Tilt" — borrowed from poker — describes an emotional state where frustration, excitement, or desperation causes you to abandon rational decision-making. Recognising when you are on tilt and stopping is a key bankroll management skill.'],
            ['q'=>'How does gambling disorder most commonly affect mental health?',
             'opts'=>['It typically has no effect on mental health unless money is lost','It commonly co-occurs with depression, anxiety, and stress disorders','It generally improves mood and reduces anxiety through excitement','It only affects mental health in people with pre-existing conditions'],
             'answer'=>1,'exp'=>'Problem gambling has a high co-occurrence with depression, anxiety, and stress-related disorders. The financial pressure, shame, and cycle of chasing losses create a feedback loop that significantly worsens mental health over time.'],
            ['q'=>'What is a "session limit" and why is it important?',
             'opts'=>['The maximum number of different games you can play at once','A pre-set maximum amount of time or money for a single gambling session','The limit casinos place on how long you can play per visit','A cap on the number of bets placed per hour'],
             'answer'=>1,'exp'=>'A session limit is a personal cap — on time, money, or both — set before you start playing. It prevents extended sessions where fatigue and emotional investment cloud judgement, which is when most problem gambling behaviour occurs.'],
        ],
    ],
    [
        'key'    => 'quiz_7',
        'title'  => 'Recovery & Avoiding Relapse',
        'tag'    => 'Recovery',
        'color'  => '#10B981',
        'icon'   => 'fas fa-seedling',
        'desc'   => 'Understand the recovery journey and the strategies that make it last.',
        'reward' => 100,
        'pass_pct' => 60,
        'questions' => [
            ['q'=>'What is the first and most important step toward recovering from problem gambling?',
             'opts'=>['Switching to lower-stakes games to reduce risk','Acknowledging that gambling has become a problem and that help is needed','Telling your employer so they can monitor your finances','Paying off all gambling debts before seeking any help'],
             'answer'=>1,'exp'=>'Acknowledging the problem is the essential first step. Denial is one of the biggest barriers to recovery — until someone recognises that gambling is causing harm and that help is needed, meaningful change cannot begin.'],
            ['q'=>'What does HALT stand for in relapse prevention?',
             'opts'=>['Habit, Awareness, Limit, Trigger','Hungry, Angry, Lonely, Tired','Help, Abstain, Learn, Trust','Halt, Analyse, Listen, Think'],
             'answer'=>1,'exp'=>'HALT — Hungry, Angry, Lonely, Tired — is a relapse prevention checklist. These four vulnerable states are among the most common triggers for cravings and impulsive decisions. Checking in with HALT helps you catch high-risk moments before acting on them.'],
            ['q'=>'Gamblers Anonymous (GA) is based on which recovery model?',
             'opts'=>['A medical prescription programme supervised by psychiatrists','A 12-step peer support programme similar to Alcoholics Anonymous','A government-funded one-to-one counselling scheme','An online-only self-help course with no group element'],
             'answer'=>1,'exp'=>'Gamblers Anonymous follows a 12-step programme modelled on Alcoholics Anonymous, emphasising peer support, personal accountability, and spiritual or personal growth. Many people find the shared experience of others in recovery invaluable.'],
            ['q'=>'Which of the following is a healthy coping strategy to replace gambling urges?',
             'opts'=>['Using alcohol to take the edge off urges','Replacing gambling with another high-risk thrill-seeking activity','Physical exercise, creative hobbies, or calling a support person when urges strike','Keeping a small gambling account active to manage urges in a "controlled" way'],
             'answer'=>2,'exp'=>'Replacing gambling with healthy alternatives — exercise, hobbies, social connection, or contacting a support person — addresses the underlying need for stimulation or escapism without the harmful consequences. Building a structured routine is key.'],
            ['q'=>'How long can post-acute withdrawal symptoms (cravings, mood swings, sleep problems) last after stopping gambling?',
             'opts'=>['Only 24-48 hours — the same as a hangover','Up to one to two years in some cases','Exactly 30 days for everyone','Withdrawal from gambling has no physical or psychological symptoms'],
             'answer'=>1,'exp'=>'Post-acute withdrawal from gambling can last months or even one to two years for some people, including cravings, mood instability, anxiety, and sleep disturbance. Understanding this helps people stay committed to recovery rather than expecting an immediate return to normal.'],
        ],
    ],
];
?>

<div class="container">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="content-wrapper">
            <div class="content-nav">
                <a href="../index.php" class="content-nav-item">Be responsible</a>
                <a href="lessons.php" class="content-nav-item">Lessons</a>
                <a href="myths-vs-facts.php" class="content-nav-item">Myths vs Facts</a>
                <a href="quiz.php" class="content-nav-item active">Quiz</a>
            </div>

            <div class="content-main">
                <div class="responsible-section quiz-page">

                    <?php
                    $totalQuizzes    = count($quizzes);
                    $completedCount  = count(array_intersect(array_column($quizzes,'key'), $completedQuizzes));
                    $totalReward     = array_sum(array_column($quizzes,'reward'));
                    $earnedReward    = 0;
                    foreach ($quizzes as $q) {
                        if (in_array($q['key'], $completedQuizzes)) $earnedReward += $q['reward'];
                    }
                    ?>

                    <!-- Hero -->
                    <div class="quiz-hero">
                        <div class="qh-top">
                            <div class="qh-title">
                                <h2>Knowledge Quiz</h2>
                                <p>Test what you know about responsible gambling. Pass each quiz to earn demo credits.</p>
                            </div>
                            <div class="qh-stats">
                                <div class="qh-stat">
                                    <div class="qh-stat-val green"><?php echo $completedCount; ?>/<?php echo $totalQuizzes; ?></div>
                                    <div class="qh-stat-lbl">Passed</div>
                                </div>
                                <div class="qh-stat">
                                    <div class="qh-stat-val gold"><?php echo $earnedReward; ?></div>
                                    <div class="qh-stat-lbl">Credits Earned</div>
                                </div>
                                <div class="qh-stat">
                                    <div class="qh-stat-val pink"><?php echo $totalReward - $earnedReward; ?></div>
                                    <div class="qh-stat-lbl">Still Available</div>
                                </div>
                                <?php if ($userId): ?>
                                <div class="qh-stat">
                                    <div class="qh-stat-val green" id="balanceDisplay"><?php echo number_format($currentBalance); ?></div>
                                    <div class="qh-stat-lbl">Demo Credits</div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($userId && $completedCount > 0): ?>
                        <div class="qh-credits-row">
                            <a href="demo.php" class="qh-play-link">
                                <i class="fas fa-dice" style="font-size:11px;"></i> Play Casino
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quiz cards -->
                    <div class="quiz-cards-grid">
                    <?php foreach ($quizzes as $i => $quiz):
                        $done   = in_array($quiz['key'], $completedQuizzes);
                        $locked = false; // all quizzes open
                    ?>
                    <div class="quiz-card <?php echo $done ? 'is-done' : ''; ?> <?php echo $locked ? 'is-locked' : ''; ?>"
                         data-quiz="<?php echo $i; ?>">

                        <div class="qc-accent" style="background:<?php echo $quiz['color']; ?>;opacity:<?php echo $done ? '1' : '0.4'; ?>;"></div>

                        <div class="qc-body">
                            <div class="qc-icon" style="border:1px solid <?php echo $quiz['color']; ?>33;color:<?php echo $quiz['color']; ?>;">
                                <?php if ($done): ?>
                                    <i class="fas fa-check-circle" style="color:#4cbb7a;"></i>
                                <?php else: ?>
                                    <i class="<?php echo $quiz['icon']; ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="qc-tag" style="color:<?php echo $quiz['color']; ?>;"><?php echo $quiz['tag']; ?></div>
                            <div class="qc-title"><?php echo $quiz['title']; ?></div>
                            <div class="qc-desc"><?php echo $quiz['desc']; ?></div>

                            <div class="qc-meta">
                                <span class="qc-pill"><i class="fas fa-question-circle" style="font-size:9px;"></i> <?php echo count($quiz['questions']); ?> Questions</span>
                                <span class="qc-pill"><i class="fas fa-check" style="font-size:9px;"></i> <?php echo $quiz['pass_pct']; ?>% to pass</span>
                                <?php if ($done): ?>
                                <span class="qc-pill done"><i class="fas fa-coins" style="font-size:9px;"></i> <?php echo $quiz['reward']; ?> earned</span>
                                <span class="qc-done-badge"><i class="fas fa-trophy" style="font-size:11px;"></i> Passed</span>
                                <?php else: ?>
                                <span class="qc-pill reward"><i class="fas fa-coins" style="font-size:9px;"></i> +<?php echo $quiz['reward']; ?> credits</span>
                                <?php if (!$locked): ?>
                                <button class="qc-start-btn" onclick="startQuiz(<?php echo $i; ?>)">
                                    <i class="fas fa-play" style="font-size:9px;"></i> Start
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <div style="background:rgba(255,27,141,0.05);border:1px solid rgba(255,27,141,0.12);border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <i class="fas fa-info-circle" style="color:var(--primary-pink);font-size:18px;flex-shrink:0;"></i>
                        <div>
                            <strong style="color:#d0a8b8;font-size:13px;">Score <?php echo $quizzes[0]['pass_pct']; ?>% or higher to earn credits.</strong>
                            <span style="font-size:12px;color:#5a5a7a;"> You can retake any quiz — credits are only awarded once per quiz.</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<!-- Quiz overlay -->
<div class="quiz-overlay" id="quizOverlay">
    <div class="quiz-modal" id="quizModal">

        <!-- Header -->
        <div class="qm-header">
            <div class="qm-top">
                <div class="qm-title" id="qmTitle">Quiz Title</div>
                <button class="qm-close" onclick="closeQuiz()"><i class="fas fa-times"></i></button>
            </div>
            <div class="qm-progress-wrap">
                <div class="qm-progress-label">
                    <span id="qmProgressLabel">Question 1 of 5</span>
                    <span id="qmScoreLabel">Score: 0</span>
                </div>
                <div class="qm-progress-track">
                    <div class="qm-progress-fill" id="qmProgressFill" style="width:0%;"></div>
                </div>
            </div>
        </div>

        <!-- Body: question or results -->
        <div class="qm-body" id="qmBody"></div>

        <!-- Footer -->
        <div class="qm-footer" id="qmFooter">
            <div class="qm-score-badge">Score: <strong id="qmScore">0</strong></div>
            <div style="display:flex;gap:8px;">
                <button class="qm-btn secondary" onclick="closeQuiz()">Quit</button>
                <button class="qm-btn primary" id="qmNextBtn" onclick="nextStep()" disabled>
                    <span id="qmNextLabel">Select an answer</span> <i class="fas fa-arrow-right" style="font-size:11px;"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<div class="credit-toast" id="creditToast">
    <div style="font-size:22px;flex-shrink:0;">🪙</div>
    <div>
        <div class="credit-toast-title" id="toastTitle">Credits Earned!</div>
        <div class="credit-toast-sub"   id="toastSub"></div>
    </div>
    <div class="credit-toast-amount" id="toastAmount">+75</div>
</div>

<?php include '../includes/modals.php'; ?>
<?php include '../includes/footer.php'; ?>

<script>
const QUIZZES = <?php echo json_encode($quizzes); ?>;
const USER_LOGGED_IN = <?php echo $userId ? 'true' : 'false'; ?>;
const COMPLETED = <?php echo json_encode($completedQuizzes); ?>;

let activeQuiz    = null;
let currentQ      = 0;
let score         = 0;
let selectedOpt   = null;
let answered      = false;
let creditsAwarded = false;
const letters     = ['A','B','C','D'];

function startQuiz(idx) {
    activeQuiz     = QUIZZES[idx];
    currentQ       = 0;
    score          = 0;
    selectedOpt    = null;
    answered       = false;
    creditsAwarded = false;

    document.getElementById('qmTitle').textContent = activeQuiz.title;

    // Restore static footer in case a previous quiz replaced it
    document.getElementById('qmFooter').innerHTML = `
        <div class="qm-score-badge">Score: <strong id="qmScore">0</strong></div>
        <div style="display:flex;gap:8px;">
            <button class="qm-btn secondary" onclick="closeQuiz()">Quit</button>
            <button class="qm-btn primary" id="qmNextBtn" onclick="nextStep()" disabled>
                <span id="qmNextLabel">Select an answer</span> <i class="fas fa-arrow-right" style="font-size:11px;"></i>
            </button>
        </div>
    `;

    document.getElementById('quizOverlay').classList.add('active');

    renderQuestion();
}

function closeQuiz() {
    document.getElementById('quizOverlay').classList.remove('active');
    activeQuiz = null;
}

function renderQuestion() {
    const q     = activeQuiz.questions[currentQ];
    const total = activeQuiz.questions.length;

    // Progress
    document.getElementById('qmProgressLabel').textContent = `Question ${currentQ+1} of ${total}`;
    document.getElementById('qmProgressFill').style.width  = `${(currentQ/total)*100}%`;
    document.getElementById('qmScore').textContent         = score;
    document.getElementById('qmScoreLabel').textContent    = `Score: ${score}/${currentQ}`;

    // Reset footer
    answered    = false;
    selectedOpt = null;
    const nextBtn = document.getElementById('qmNextBtn');
    nextBtn.disabled = true;
    document.getElementById('qmNextLabel').textContent = currentQ === total-1 ? 'See Results' : 'Next Question';

    // Render body
    const optionsHtml = q.opts.map((opt, i) => `
        <div class="qm-option" data-idx="${i}" onclick="selectOption(this, ${i})">
            <div class="qm-option-letter">${letters[i]}</div>
            <div>${opt}</div>
        </div>
    `).join('');

    document.getElementById('qmBody').innerHTML = `
        <div class="qm-question-num">Question ${currentQ+1} of ${total}</div>
        <div class="qm-question-text">${q.q}</div>
        <div class="qm-options" id="qmOptions">${optionsHtml}</div>
        <div class="qm-explanation" id="qmExplanation"></div>
    `;
}

function selectOption(el, idx) {
    if (answered) return;
    // Deselect all
    document.querySelectorAll('.qm-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedOpt = idx;
    document.getElementById('qmNextBtn').disabled = false;
    document.getElementById('qmNextLabel').textContent =
        currentQ === activeQuiz.questions.length - 1 ? 'Submit' : 'Confirm Answer';
}

function nextStep() {
    if (!answered && selectedOpt === null) return;

    if (!answered) {
        // Lock in answer
        answered = true;
        const q       = activeQuiz.questions[currentQ];
        const correct = q.answer;
        const isRight = selectedOpt === correct;

        if (isRight) score++;

        // Style options
        document.querySelectorAll('.qm-option').forEach((opt, i) => {
            opt.classList.add('answered');
            opt.style.cursor = 'default';
            if (i === correct && isRight) opt.classList.add('correct');
            else if (i === selectedOpt && !isRight) opt.classList.add('wrong');
            else if (i === correct && !isRight) opt.classList.add('reveal-correct');
        });

        // Explanation
        const expEl = document.getElementById('qmExplanation');
        expEl.style.display = 'flex';
        expEl.className = 'qm-explanation ' + (isRight ? 'correct-exp' : 'wrong-exp');
        expEl.innerHTML = `<i class="fas ${isRight ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${q.exp}`;

        // Update button
        const isLast = currentQ === activeQuiz.questions.length - 1;
        document.getElementById('qmNextLabel').textContent = isLast ? 'See Results' : 'Next Question';
        document.getElementById('qmScore').textContent     = score;
        document.getElementById('qmScoreLabel').textContent = `Score: ${score}/${currentQ+1}`;
        return;
    }

    // Move to next question or results
    currentQ++;
    if (currentQ < activeQuiz.questions.length) {
        renderQuestion();
    } else {
        showResults();
    }
}

async function showResults() {
    const total     = activeQuiz.questions.length;
    const pct       = Math.round((score / total) * 100);
    const passed    = pct >= activeQuiz.pass_pct;
    const alreadyDone = COMPLETED.includes(activeQuiz.key);

    // Update progress to 100%
    document.getElementById('qmProgressFill').style.width = '100%';
    document.getElementById('qmProgressLabel').textContent = 'Completed!';

    let creditsHtml = '';
    if (passed && USER_LOGGED_IN && !alreadyDone) {
        // Award credits
        try {
            const res  = await fetch('../ajax/credits_award.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content_type: 'quiz', content_key: activeQuiz.key }),
            });
            const data = await res.json();
            if (data.success && !data.already_earned) {
                creditsAwarded = true;
                COMPLETED.push(activeQuiz.key);
                // Update balance display
                const balEl = document.getElementById('balanceDisplay');
                if (balEl) balEl.textContent = Math.floor(data.balance).toLocaleString();
                // Show toast after modal closes or alongside
                showToast('Quiz Passed!', `+${data.credits_earned} credits awarded`, '+' + data.credits_earned);
                creditsHtml = `
                    <div class="qm-credits-earned">
                        <span style="font-size:20px;">🪙</span>
                        <div>
                            <div style="font-size:11px;color:#3d6e50;text-transform:uppercase;letter-spacing:0.7px;margin-bottom:2px;">Credits Earned</div>
                            <span class="amt">+${data.credits_earned}</span>
                        </div>
                    </div>`;
            } else if (data.already_earned) {
                creditsHtml = `<div style="font-size:13px;color:#5a5a7a;margin-bottom:12px;">Credits were already awarded for this quiz.</div>`;
            }
        } catch(e) { console.error(e); }
    } else if (passed && alreadyDone) {
        creditsHtml = `<div style="font-size:13px;color:#5a5a7a;margin-bottom:12px;">You already earned credits for this quiz.</div>`;
    } else if (!USER_LOGGED_IN && passed) {
        creditsHtml = `<div style="font-size:13px;color:#5a5a7a;margin-bottom:12px;">Log in to earn credits for passing.</div>`;
    }

    const emoji = pct === 100 ? '🏆' : passed ? '🎉' : '📚';
    const title = pct === 100 ? 'Perfect Score!' : passed ? 'Quiz Passed!' : 'Keep Studying!';
    const sub   = passed
        ? `You scored ${score} out of ${total} — well done!`
        : `You scored ${score} out of ${total}. You need ${activeQuiz.pass_pct}% to pass. Try again!`;

    document.getElementById('qmBody').innerHTML = `
        <div class="qm-results">
            <div class="qm-results-icon">${emoji}</div>
            <div class="qm-results-title">${title}</div>
            <div class="qm-results-sub">${sub}</div>
            <div class="qm-results-score">
                <div>
                    <div class="qm-results-score-num">${pct}%</div>
                    <div class="qm-results-score-lbl">Your Score</div>
                </div>
                <div style="width:1px;height:40px;background:rgba(255,255,255,0.08);"></div>
                <div>
                    <div class="qm-results-score-num" style="color:${passed?'#4cbb7a':'#f87171'}">${score}/${total}</div>
                    <div class="qm-results-score-lbl">Correct</div>
                </div>
            </div>
            ${creditsHtml}
            <div class="qm-pass-bar">
                Pass mark: <span class="pass-mark">${activeQuiz.pass_pct}%</span>
                &nbsp;·&nbsp; Status: <span style="color:${passed?'#4cbb7a':'#f87171'};font-weight:700;">${passed?'PASSED':'NOT PASSED'}</span>
            </div>
        </div>
    `;

    // Update footer
    document.getElementById('qmScore').textContent = `${score}/${total}`;
    document.getElementById('qmFooter').innerHTML = `
        <div class="qm-score-badge">Final: <strong style="color:${passed?'#4cbb7a':'#f87171'}">${pct}%</strong></div>
        <div style="display:flex;gap:8px;">
            ${!passed ? `<button class="qm-btn secondary" onclick="retakeQuiz()"><i class="fas fa-redo" style="font-size:10px;"></i> Retake</button>` : ''}
            <button class="qm-btn primary" onclick="closeQuiz()">
                <i class="fas fa-home" style="font-size:11px;"></i> Done
            </button>
        </div>
    `;

    // Refresh card UI if passed
    if (passed && !alreadyDone) {
        const cards = document.querySelectorAll('.quiz-card');
        const quizIdx = QUIZZES.findIndex(q => q.key === activeQuiz.key);
        if (cards[quizIdx]) {
            cards[quizIdx].classList.add('is-done');
            const startBtn = cards[quizIdx].querySelector('.qc-start-btn');
            if (startBtn) startBtn.remove();
            const rewardPill = cards[quizIdx].querySelector('.qc-pill.reward');
            if (rewardPill) {
                rewardPill.className = 'qc-pill done';
                rewardPill.innerHTML = `<i class="fas fa-coins" style="font-size:9px;"></i> ${activeQuiz.reward} earned`;
            }
            const meta = cards[quizIdx].querySelector('.qc-meta');
            if (meta && !meta.querySelector('.qc-done-badge')) {
                const badge = document.createElement('span');
                badge.className = 'qc-done-badge';
                badge.innerHTML = '<i class="fas fa-trophy" style="font-size:11px;"></i> Passed';
                meta.appendChild(badge);
            }
            const icon = cards[quizIdx].querySelector('.qc-icon');
            if (icon) icon.innerHTML = '<i class="fas fa-check-circle" style="color:#4cbb7a;"></i>';
            const accent = cards[quizIdx].querySelector('.qc-accent');
            if (accent) accent.style.opacity = '1';
        }
    }
}

function retakeQuiz() {
    const idx = QUIZZES.findIndex(q => q.key === activeQuiz.key);
    startQuiz(idx);
}

let toastTimer = null;
function showToast(title, sub, amount) {
    document.getElementById('toastTitle').textContent  = title;
    document.getElementById('toastSub').textContent    = sub;
    document.getElementById('toastAmount').textContent = amount;
    const t = document.getElementById('creditToast');
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 5000);
}

// Close on backdrop click
document.getElementById('quizOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeQuiz();
});

// Keyboard support
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeQuiz();
    if (!activeQuiz || answered) return;
    const keys = { '1': 0, '2': 1, '3': 2, '4': 3, 'a': 0, 'b': 1, 'c': 2, 'd': 3 };
    if (keys[e.key.toLowerCase()] !== undefined) {
        const opts = document.querySelectorAll('.qm-option');
        if (opts[keys[e.key.toLowerCase()]]) opts[keys[e.key.toLowerCase()]].click();
    }
    if (e.key === 'Enter') {
        const btn = document.getElementById('qmNextBtn');
        if (btn && !btn.disabled) btn.click();
    }
});
</script>