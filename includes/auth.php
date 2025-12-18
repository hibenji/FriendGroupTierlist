<?php
/**
 * Authentication Helper Functions
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return getUserById($_SESSION['user_id']);
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require admin privileges
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin($_SESSION['user_id'])) {
        http_response_code(403);
        die(json_encode(['error' => 'Admin access required']));
    }
}

/**
 * Get Discord avatar URL for a user
 */
function getDiscordAvatarUrl(string $userId, ?string $avatarHash, int $size = 128): string {
    if ($avatarHash) {
        $ext = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';
        return "https://cdn.discordapp.com/avatars/{$userId}/{$avatarHash}.{$ext}?size={$size}";
    }
    // Default avatar
    $defaultIndex = (int)$userId % 5;
    return "https://cdn.discordapp.com/embed/avatars/{$defaultIndex}.png";
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken(string $code): ?array {
    $data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => DISCORD_REDIRECT_URI,
    ];
    
    $ch = curl_init(DISCORD_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log('Discord token exchange failed: ' . $response);
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetch Discord user info using access token
 */
function fetchDiscordUser(string $accessToken): ?array {
    $ch = curl_init(DISCORD_API_URL . '/users/@me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log('Discord user fetch failed: ' . $response);
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Fetch any Discord user's info using bot token
 */
function fetchDiscordUserByBot(string $userId): ?array {
    $ch = curl_init(DISCORD_API_URL . '/users/' . $userId);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bot ' . DISCORD_BOT_TOKEN,
            'Content-Type: application/json',
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log('Discord bot user fetch failed: ' . $response);
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Generate Discord OAuth URL
 */
function getDiscordAuthUrl(): string {
    $params = [
        'client_id' => DISCORD_CLIENT_ID,
        'redirect_uri' => DISCORD_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => DISCORD_SCOPES,
        'state' => generateState(),
    ];
    
    return DISCORD_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Generate and store CSRF state token
 */
function generateState(): string {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    return $state;
}

/**
 * Validate CSRF state token
 */
function validateState(string $state): bool {
    if (!isset($_SESSION['oauth_state'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['oauth_state'], $state);
    unset($_SESSION['oauth_state']);
    return $valid;
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get JSON request body
 */
function getJsonBody(): ?array {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}
