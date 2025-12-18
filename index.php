<?php
/**
 * ChillGC Tierlist - Main Page
 */

require_once __DIR__ . '/includes/auth.php';

// Check if request is from a bot/crawler (for Discord embeds, etc.)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = preg_match('/bot|crawl|slurp|spider|discord|facebook|twitter|telegram|whatsapp|slack|linkedin/i', $userAgent);

// Require authentication for real users, but let bots see the meta tags
if (!$isBot) {
    requireAuth();
    $user = getCurrentUser();
    $avatarUrl = getDiscordAvatarUrl($user['id'], $user['avatar']);
} else {
    $user = null;
    $avatarUrl = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Rank your Discord GC friends on a tierlist from S to F tier">
    <title><?= APP_NAME ?> - Rank ChillGC</title>
    
    <!-- Open Graph / Discord Embed -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://chillgc.benji.link/">
    <meta property="og:title" content="<?= APP_NAME ?> - Rank ChillGC">
    <meta property="og:description" content="🏆 Rank your GC friends on a tierlist from S to F tier!">
    <meta property="og:image" content="https://chillgc.benji.link/assets/embed.png">
    <meta name="theme-color" content="#5865f2">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= APP_NAME ?> - Rank ChillGC">
    <meta name="twitter:description" content="🏆 Rank your GC friends on a tierlist from S to F tier!">
    <meta name="twitter:image" content="https://chillgc.benji.link/assets/embed.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="logo">
            <span class="logo-icon">🏆</span>
            <span><?= APP_NAME ?></span>
        </div>
        
        <nav class="nav">
            <a href="/" class="nav-link active">Tierlist</a>
            <a href="/results.php" class="nav-link">Results</a>
        </nav>
        
        <?php if ($user): ?>
        <div class="user-info">
            <img class="user-avatar" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
            <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
            <a href="/logout.php" class="btn btn-secondary">Logout</a>
        </div>
        <?php endif; ?>
    </header>
    
    <main class="main">
        <h1 class="page-title">Rank ChillGC</h1>
        <p class="page-subtitle">Drag and drop people into tiers. Your rankings are saved automatically.</p>
        
        <div class="tierlist-container">
            <!-- Unranked Pool (Left Side) -->
            <div class="unranked-section">
                <div class="section-header">
                    <h2 class="section-title">Unranked</h2>
                </div>
                <?php if ($user && isAdmin($user['id'])): ?>
                <form id="add-person-form" class="add-person-form">
                    <input type="text" name="discord_id" placeholder="Discord User ID..." autocomplete="off">
                    <button type="submit" class="btn btn-primary">Add</button>
                </form>
                <?php endif; ?>
                <div id="unranked-pool" class="unranked-pool">
                    <div class="loading">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
            
            <!-- Tierlist (Right Side) -->
            <div class="tierlist">
                <div class="tier-row" data-tier="S">
                    <div class="tier-label">S</div>
                    <div class="tier-content"></div>
                </div>
                <div class="tier-row" data-tier="A">
                    <div class="tier-label">A</div>
                    <div class="tier-content"></div>
                </div>
                <div class="tier-row" data-tier="B">
                    <div class="tier-label">B</div>
                    <div class="tier-content"></div>
                </div>
                <div class="tier-row" data-tier="C">
                    <div class="tier-label">C</div>
                    <div class="tier-content"></div>
                </div>
                <div class="tier-row" data-tier="D">
                    <div class="tier-label">D</div>
                    <div class="tier-content"></div>
                </div>
                <div class="tier-row" data-tier="F">
                    <div class="tier-label">F</div>
                    <div class="tier-content"></div>
                </div>
            </div>
        </div>
    </main>
    
    <div id="toast-container" class="toast-container"></div>
    
    <script>
        // Current user's Discord ID for self-vote prevention
        window.CURRENT_USER_DISCORD_ID = <?= $user ? "'" . htmlspecialchars($user['id']) . "'" : 'null' ?>;
    </script>
    <script src="/assets/js/tierlist.js"></script>
</body>
</html>
