<?php
/**
 * Database Connection and Helper Functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Get PDO database connection
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    return $pdo;
}

/**
 * Create or update a user from Discord data
 */
function upsertUser(array $discordUser, string $accessToken, string $refreshToken, int $expiresIn): array {
    $db = getDB();
    
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
    
    $sql = "INSERT INTO users (id, username, discriminator, avatar, access_token, refresh_token, token_expires_at)
            VALUES (:id, :username, :discriminator, :avatar, :access_token, :refresh_token, :token_expires_at)
            ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                discriminator = VALUES(discriminator),
                avatar = VALUES(avatar),
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                token_expires_at = VALUES(token_expires_at),
                updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':id' => $discordUser['id'],
        ':username' => $discordUser['username'],
        ':discriminator' => $discordUser['discriminator'] ?? '0',
        ':avatar' => $discordUser['avatar'] ?? null,
        ':access_token' => $accessToken,
        ':refresh_token' => $refreshToken,
        ':token_expires_at' => $expiresAt,
    ]);
    
    return getUserById($discordUser['id']);
}

/**
 * Get user by Discord ID
 */
function getUserById(string $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Get all active people to rank
 */
function getAllPeople(): array {
    $db = getDB();
    $stmt = $db->query("SELECT id, name, discord_id, avatar_url FROM people WHERE is_active = 1 ORDER BY name");
    $people = $stmt->fetchAll();
    
    // Ensure discord_id is returned as string to prevent JS number precision issues
    foreach ($people as &$person) {
        if ($person['discord_id'] !== null) {
            $person['discord_id'] = (string)$person['discord_id'];
        }
    }
    
    return $people;
}

/**
 * Get rankings for a specific user
 */
function getUserRankings(string $userId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT person_id, tier FROM rankings WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    $rankings = [];
    while ($row = $stmt->fetch()) {
        $rankings[$row['person_id']] = $row['tier'];
    }
    return $rankings;
}

/**
 * Save or update a ranking
 */
function saveRanking(string $userId, int $personId, string $tier): bool {
    $validTiers = ['S', 'A', 'B', 'C', 'D', 'F'];
    if (!in_array($tier, $validTiers)) {
        return false;
    }
    
    $db = getDB();
    $sql = "INSERT INTO rankings (user_id, person_id, tier)
            VALUES (:user_id, :person_id, :tier)
            ON DUPLICATE KEY UPDATE tier = VALUES(tier), updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        ':user_id' => $userId,
        ':person_id' => $personId,
        ':tier' => $tier,
    ]);
}

/**
 * Remove a ranking (move person back to unranked)
 */
function removeRanking(string $userId, int $personId): bool {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM rankings WHERE user_id = ? AND person_id = ?");
    return $stmt->execute([$userId, $personId]);
}

/**
 * Get aggregate ranking results for all people
 */
function getAggregateResults(): array {
    $db = getDB();
    
    // Get tier counts for each person
    $sql = "SELECT 
                p.id,
                p.name,
                p.avatar_url,
                r.tier,
                COUNT(r.id) as count
            FROM people p
            LEFT JOIN rankings r ON p.id = r.person_id
            WHERE p.is_active = 1
            GROUP BY p.id, p.name, p.avatar_url, r.tier
            ORDER BY p.name, r.tier";
    
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
    
    // Organize results by person
    $results = [];
    foreach ($rows as $row) {
        $id = $row['id'];
        if (!isset($results[$id])) {
            $results[$id] = [
                'id' => $id,
                'name' => $row['name'],
                'avatar_url' => $row['avatar_url'],
                'tiers' => ['S' => 0, 'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0],
                'total' => 0,
                'average_score' => null,
            ];
        }
        if ($row['tier']) {
            $results[$id]['tiers'][$row['tier']] = (int)$row['count'];
            $results[$id]['total'] += (int)$row['count'];
        }
    }
    
    // Calculate average scores (S=5, A=4, B=3, C=2, D=1, F=0)
    $tierScores = ['S' => 5, 'A' => 4, 'B' => 3, 'C' => 2, 'D' => 1, 'F' => 0];
    foreach ($results as &$person) {
        if ($person['total'] > 0) {
            $totalScore = 0;
            foreach ($person['tiers'] as $tier => $count) {
                $totalScore += $tierScores[$tier] * $count;
            }
            $person['average_score'] = round($totalScore / $person['total'], 2);
        }
    }
    
    // Sort by average score descending
    usort($results, function($a, $b) {
        if ($a['average_score'] === null && $b['average_score'] === null) return 0;
        if ($a['average_score'] === null) return 1;
        if ($b['average_score'] === null) return -1;
        return $b['average_score'] <=> $a['average_score'];
    });
    
    return array_values($results);
}

/**
 * Add a new person to rank
 */
function addPerson(string $name, ?string $discordId = null, ?string $avatarUrl = null, ?string $addedBy = null): int {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO people (name, discord_id, avatar_url, added_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $discordId, $avatarUrl, $addedBy]);
    return (int)$db->lastInsertId();
}

/**
 * Delete a person (soft delete)
 */
function deletePerson(int $personId): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE people SET is_active = 0 WHERE id = ?");
    return $stmt->execute([$personId]);
}

/**
 * Check if user is admin
 */
function isAdmin(string $userId): bool {
    $user = getUserById($userId);
    return $user && $user['is_admin'] == 1;
}
