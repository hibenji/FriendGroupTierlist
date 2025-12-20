<?php
/**
 * Discord OAuth Callback Handler
 * 
 * This page receives the authorization code from Discord,
 * exchanges it for an access token, and creates the user session.
 */

require_once __DIR__ . '/includes/auth.php';

// Check for errors from Discord
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    $errorDesc = htmlspecialchars($_GET['error_description'] ?? 'Unknown error');
    die("Discord authorization failed: {$error} - {$errorDesc}");
}

// Verify we have an authorization code
if (!isset($_GET['code'])) {
    die('No authorization code received from Discord.');
}

// Validate CSRF state
if (!isset($_GET['state']) || !validateState($_GET['state'])) {
    $_SESSION['login_error'] = 'Your session expired. Please try logging in again.';
    header('Location: /login.php');
    exit;
}

$code = $_GET['code'];

// Exchange authorization code for access token
$tokenData = exchangeCodeForToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    die('Failed to obtain access token from Discord.');
}

$accessToken = $tokenData['access_token'];
$refreshToken = $tokenData['refresh_token'] ?? '';
$expiresIn = $tokenData['expires_in'] ?? 604800; // Default 7 days

// Fetch user info from Discord
$discordUser = fetchDiscordUser($accessToken);

if (!$discordUser || !isset($discordUser['id'])) {
    die('Failed to fetch user information from Discord.');
}

// Create or update user in database
try {
    $user = upsertUser($discordUser, $accessToken, $refreshToken, $expiresIn);
} catch (Exception $e) {
    error_log('Failed to save user: ' . $e->getMessage());
    die('Failed to save user data. Please try again.');
}

// Set session
$_SESSION['user_id'] = $discordUser['id'];
$_SESSION['username'] = $discordUser['username'];
$_SESSION['avatar'] = $discordUser['avatar'];

// Redirect to home page
header('Location: /');
exit;
