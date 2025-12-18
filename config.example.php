<?php
/**
 * ChillGC Tierlist Configuration
 * 
 * Update the database credentials and Discord OAuth settings below.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');      // UPDATE THIS
define('DB_PASS', 'your_password');  // UPDATE THIS
define('DB_CHARSET', 'utf8mb4');

// ============================================
// DISCORD OAUTH CONFIGURATION
// ============================================
define('DISCORD_CLIENT_ID', 'your_client_id');
define('DISCORD_CLIENT_SECRET', 'your_client_secret');
define('DISCORD_BOT_TOKEN', 'your_bot_token');
define('DISCORD_REDIRECT_URI', 'https://your-domain.com/callback.php');

// Discord API endpoints
define('DISCORD_API_URL', 'https://discord.com/api/v10');
define('DISCORD_AUTH_URL', 'https://discord.com/api/oauth2/authorize');
define('DISCORD_TOKEN_URL', 'https://discord.com/api/oauth2/token');

// OAuth scopes - 'identify' gets user info, 'guilds' optional for server restriction
define('DISCORD_SCOPES', 'identify');

// ============================================
// ACCESS RESTRICTIONS (OPTIONAL)
// ============================================
// Set to a Discord Guild ID to restrict access to members of that server
// Leave empty string to allow anyone with Discord
define('REQUIRED_GUILD_ID', '');

// ============================================
// APPLICATION SETTINGS
// ============================================
define('APP_NAME', 'Friend Tierlist');
define('APP_URL', 'https://your-domain.com');

// Tier definitions with colors
define('TIERS', [
    'S' => ['name' => 'S Tier', 'color' => '#FFD700', 'bg' => 'linear-gradient(135deg, #FFD700, #FFA500)'],
    'A' => ['name' => 'A Tier', 'color' => '#9B59B6', 'bg' => 'linear-gradient(135deg, #9B59B6, #8E44AD)'],
    'B' => ['name' => 'B Tier', 'color' => '#3498DB', 'bg' => 'linear-gradient(135deg, #3498DB, #2980B9)'],
    'C' => ['name' => 'C Tier', 'color' => '#2ECC71', 'bg' => 'linear-gradient(135deg, #2ECC71, #27AE60)'],
    'D' => ['name' => 'D Tier', 'color' => '#F39C12', 'bg' => 'linear-gradient(135deg, #F39C12, #E67E22)'],
    'F' => ['name' => 'F Tier', 'color' => '#E74C3C', 'bg' => 'linear-gradient(135deg, #E74C3C, #C0392B)'],
]);
