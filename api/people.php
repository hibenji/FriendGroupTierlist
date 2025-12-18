<?php
/**
 * People API
 * 
 * GET    - Fetch all people available to rank
 * POST   - Add new person (admin only)
 * DELETE - Remove person (admin only)
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all active people
        $people = getAllPeople();
        jsonResponse(['people' => $people]);
        break;
        
    case 'POST':
        // Add new person - admin only
        if (!isAdmin($userId)) {
            jsonResponse(['error' => 'Only admins can add new people'], 403);
        }
        
        // Accepts discord_id to auto-fetch name/avatar
        $data = getJsonBody();
        
        $discordId = isset($data['discord_id']) ? trim($data['discord_id']) : null;
        $name = isset($data['name']) ? trim($data['name']) : null;
        $avatarUrl = isset($data['avatar_url']) ? trim($data['avatar_url']) : null;
        
        // If discord_id is provided, try to fetch user info from Discord
        if ($discordId && (!$name || !$avatarUrl)) {
            $discordUser = fetchDiscordUserByBot($discordId);
            if ($discordUser) {
                // Use fetched data if not provided
                if (!$name) {
                    $name = $discordUser['global_name'] ?? $discordUser['username'];
                }
                if (!$avatarUrl && isset($discordUser['avatar'])) {
                    $avatarUrl = getDiscordAvatarUrl($discordId, $discordUser['avatar']);
                } elseif (!$avatarUrl) {
                    $avatarUrl = getDiscordAvatarUrl($discordId, null);
                }
            }
        }
        
        // Name is required
        if (!$name) {
            jsonResponse(['error' => 'Name is required (or provide a valid discord_id)'], 400);
        }
        
        // Default avatar if still not set
        if (!$avatarUrl && $discordId) {
            $avatarUrl = "https://cdn.discordapp.com/embed/avatars/" . ((int)$discordId % 5) . ".png";
        }
        
        try {
            $personId = addPerson($name, $discordId, $avatarUrl, $userId);
            jsonResponse([
                'success' => true,
                'message' => 'Person added',
                'person' => [
                    'id' => $personId,
                    'name' => $name,
                    'discord_id' => $discordId,
                    'avatar_url' => $avatarUrl,
                ]
            ], 201);
        } catch (Exception $e) {
            jsonResponse(['error' => 'Failed to add person'], 500);
        }
        break;
        
    case 'DELETE':
        // Remove person (soft delete) - admin only
        if (!isAdmin($userId)) {
            jsonResponse(['error' => 'Admin access required'], 403);
        }
        
        $data = getJsonBody();
        
        if (!$data || !isset($data['id'])) {
            jsonResponse(['error' => 'Person ID is required'], 400);
        }
        
        $personId = (int)$data['id'];
        
        if (deletePerson($personId)) {
            jsonResponse(['success' => true, 'message' => 'Person removed']);
        } else {
            jsonResponse(['error' => 'Failed to remove person'], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
