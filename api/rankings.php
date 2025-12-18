<?php
/**
 * Rankings API
 * 
 * GET  - Fetch current user's rankings
 * POST - Save/update a ranking
 * DELETE - Remove a ranking
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
        // Get all rankings for current user
        $rankings = getUserRankings($userId);
        jsonResponse(['rankings' => $rankings]);
        break;
        
    case 'POST':
        // Save or update a ranking
        $data = getJsonBody();
        
        if (!$data || !isset($data['person_id']) || !isset($data['tier'])) {
            jsonResponse(['error' => 'Missing person_id or tier'], 400);
        }
        
        $personId = (int)$data['person_id'];
        $tier = strtoupper($data['tier']);
        
        // Validate tier
        if (!in_array($tier, ['S', 'A', 'B', 'C', 'D', 'F'])) {
            jsonResponse(['error' => 'Invalid tier. Must be S, A, B, C, D, or F'], 400);
        }
        
        if (saveRanking($userId, $personId, $tier)) {
            jsonResponse(['success' => true, 'message' => 'Ranking saved']);
        } else {
            jsonResponse(['error' => 'Failed to save ranking'], 500);
        }
        break;
        
    case 'DELETE':
        // Remove a ranking
        $data = getJsonBody();
        
        if (!$data || !isset($data['person_id'])) {
            jsonResponse(['error' => 'Missing person_id'], 400);
        }
        
        $personId = (int)$data['person_id'];
        
        if (removeRanking($userId, $personId)) {
            jsonResponse(['success' => true, 'message' => 'Ranking removed']);
        } else {
            jsonResponse(['error' => 'Failed to remove ranking'], 500);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
