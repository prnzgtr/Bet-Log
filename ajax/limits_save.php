<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Manual session check for AJAX — don't redirect, return JSON error
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input  = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$action = $input['action'] ?? 'save'; 
$type   = $input['type']   ?? '';    
$value  = isset($input['value']) ? floatval($input['value']) : null;

// Allowed limit column names — whitelist for security
$allowedTypes = [
    'daily_loss',
    'weekly_loss',
    'monthly_loss',
    'session_loss',
    'max_single_bet',
    'max_daily_wager',
    'min_credits',
];

if (!in_array($type, $allowedTypes)) {
    echo json_encode(['error' => 'Invalid limit type']);
    exit();
}

if ($action === 'save' && ($value === null || $value <= 0)) {
    echo json_encode(['error' => 'Value must be greater than 0']);
    exit();
}

try {
    // Check if user already has a limits row
    $stmt = $conn->prepare("SELECT id, {$type} FROM user_limits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'remove') {
        if ($existing) {
            $stmt = $conn->prepare("UPDATE user_limits SET {$type} = NULL WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        echo json_encode(['success' => true, 'action' => 'removed', 'type' => $type]);
        exit();
    }

    $cooldownActive = false;
    if ($existing && $existing[$type] !== null && $value > floatval($existing[$type])) {
        $cooldownActive = true;
    }

    if ($existing) {
        $stmt = $conn->prepare("UPDATE user_limits SET {$type} = ? WHERE user_id = ?");
        $stmt->execute([$value, $userId]);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO user_limits (user_id, {$type}) VALUES (?, ?)"
        );
        $stmt->execute([$userId, $value]);
    }

    echo json_encode([
        'success'          => true,
        'action'           => 'saved',
        'type'             => $type,
        'value'            => $value,
        'cooldown_active'  => $cooldownActive,
        'message'          => $cooldownActive
            ? 'Limit increase saved. It will take effect in 24 hours.'
            : 'Limit saved successfully.',
    ]);

} catch (PDOException $e) {
    error_log("limits_save error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to save limit. Please try again.']);
}