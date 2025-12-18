<?php
/**
 * Results API
 * 
 * GET - Fetch aggregate ranking results for all people
 */

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Require authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get aggregate results
$results = getAggregateResults();
jsonResponse(['results' => $results]);
