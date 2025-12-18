<?php
/**
 * ChillGC Tierlist - Results Page
 * Shows aggregate rankings from all users
 */

require_once __DIR__ . '/includes/auth.php';

// Require authentication
requireAuth();

$user = getCurrentUser();
$avatarUrl = getDiscordAvatarUrl($user['id'], $user['avatar']);
$results = getAggregateResults();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View aggregate tierlist rankings from all members">
    <title><?= APP_NAME ?> - Results</title>
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
            <a href="/" class="nav-link">Tierlist</a>
            <a href="/results.php" class="nav-link active">Results</a>
        </nav>
        
        <div class="user-info">
            <img class="user-avatar" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
            <span class="user-name"><?= htmlspecialchars($user['username']) ?></span>
            <a href="/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </header>
    
    <main class="main">
        <h1 class="page-title">Aggregate Results</h1>
        <p class="page-subtitle">Combined rankings from all members. Average score: S=5, A=4, B=3, C=2, D=1, F=0</p>
        
        <div class="results-grid">
            <?php if (empty($results)): ?>
                <div class="empty-message" style="padding: 48px; text-align: center;">
                    <p>No rankings yet. Be the first to Rank ChillGC!</p>
                    <a href="/" class="btn btn-primary" style="margin-top: 16px;">Start Ranking</a>
                </div>
            <?php else: ?>
                <?php foreach ($results as $index => $person): 
                    $rankClass = '';
                    if ($index === 0) $rankClass = 'gold';
                    elseif ($index === 1) $rankClass = 'silver';
                    elseif ($index === 2) $rankClass = 'bronze';
                    
                    $avatar = $person['avatar_url'] ?: 'https://cdn.discordapp.com/embed/avatars/' . ($person['id'] % 5) . '.png';
                ?>
                    <div class="result-card">
                        <div class="result-rank <?= $rankClass ?>">#<?= $index + 1 ?></div>
                        <img class="result-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($person['name']) ?>"
                             onerror="this.src='https://cdn.discordapp.com/embed/avatars/0.png'">
                        
                        <div class="result-info">
                            <div class="result-name"><?= htmlspecialchars($person['name']) ?></div>
                            <div class="result-stats">
                                <?php if ($person['total'] > 0): ?>
                                    <?= $person['total'] ?> vote<?= $person['total'] !== 1 ? 's' : '' ?>
                                    <?php 
                                    $tierBreakdown = [];
                                    foreach ($person['tiers'] as $tier => $count) {
                                        if ($count > 0) {
                                            $tierBreakdown[] = "{$tier}:{$count}";
                                        }
                                    }
                                    if ($tierBreakdown): ?>
                                        (<?= implode(' ', $tierBreakdown) ?>)
                                    <?php endif; ?>
                                <?php else: ?>
                                    No votes yet
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($person['total'] > 0): ?>
                                <div class="tier-distribution">
                                    <?php foreach ($person['tiers'] as $tier => $count): 
                                        if ($count > 0):
                                            $width = ($count / $person['total']) * 200;
                                    ?>
                                        <div class="tier-bar" data-tier="<?= $tier ?>" style="width: <?= $width ?>px;" title="<?= $tier ?>: <?= $count ?>"></div>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-score">
                            <?php if ($person['average_score'] !== null): ?>
                                <div class="score-value"><?= number_format($person['average_score'], 1) ?></div>
                                <div class="score-label">Avg Score</div>
                            <?php else: ?>
                                <div class="score-value">-</div>
                                <div class="score-label">No Votes</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Auto-refresh results every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
