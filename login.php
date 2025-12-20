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

$discordAuthUrl = getDiscordAuthUrl();

// Check if there's a login error to display
if (isset($_SESSION['login_error'])) {
    $errorMessage = htmlspecialchars($_SESSION['login_error']);
    unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Login</title>
    <meta http-equiv="refresh" content="3;url=<?= htmlspecialchars($discordAuthUrl) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1b26 0%, #24283b 100%);
            font-family: 'Inter', sans-serif;
            color: #c0caf5;
        }
        .card {
            background: rgba(36, 40, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            max-width: 400px;
            border: 1px solid rgba(192, 202, 245, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; color: #c0caf5; }
        p { color: #a9b1d6; margin-bottom: 1.5rem; line-height: 1.6; }
        .redirect-text { font-size: 0.875rem; color: #7aa2f7; }
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(122, 162, 247, 0.3);
            border-top-color: #7aa2f7;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        a {
            color: #7aa2f7;
            text-decoration: none;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔄</div>
        <h1>Session Expired</h1>
        <p><?= $errorMessage ?></p>
        <p class="redirect-text">
            <span class="spinner"></span>
            Redirecting to Discord... or <a href="<?= htmlspecialchars($discordAuthUrl) ?>">click here</a>
        </p>
    </div>
</body>
</html>
<?php
    exit;
}

// Redirect to Discord authorization
header('Location: ' . $discordAuthUrl);
exit;
