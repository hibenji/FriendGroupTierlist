<?php
/**
 * Discord OAuth Login - Initiates the authorization flow
 */

require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to home
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

// Redirect to Discord authorization
header('Location: ' . getDiscordAuthUrl());
exit;
