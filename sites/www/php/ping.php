<?php

/**
 * ping.php
 * AJAX endpoint for client-side ping requests (timezone/heartbeat)
 * Validates CSRF token in X-CSRF-TOKEN header
 *
 * @copyright (c) 2026 zoidtechnologies.com all rights reserved
 * @package zoid6
 */

require_once("config.php");

// Start session for CSRF token validation
\bbsengine6\session\start();

// Set response headers
header('Content-Type: application/json; charset=UTF-8');

/**
 * Validate CSRF token from request header
 * @return bool true if token is valid or missing, false if invalid
 */
function validateCsrfToken()
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF-TOKEN'] ?? null;
    
    // If no token provided, reject the request
    if ($token === null)
    {
        return false;
    }
    
    // Validate the token
    return \bbsengine6\util\csrfValidateToken($token);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    \bbsengine6\util\logentry("ping.php: Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    exit;
}

// Validate CSRF token
if (!validateCsrfToken())
{
    $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    
    \bbsengine6\util\logentry("CSRF validation failed for ping.php: ip={$remoteAddr}, user_agent={$userAgent}");
    
    http_response_code(403);
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

// Get JSON request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data))
{
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    \bbsengine6\util\logentry("ping.php: Invalid JSON payload");
    exit;
}

// Extract and validate input
$localTimestamp = isset($data['localtimestamp']) ? $data['localtimestamp'] : null;
$localTimezoneOffset = isset($data['localtimezoneoffset']) ? intval($data['localtimezoneoffset']) : null;

if ($localTimestamp === null || $localTimezoneOffset === null)
{
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    \bbsengine6\util\logentry("ping.php: Missing localtimestamp or localtimezoneoffset");
    exit;
}

// Store in session
$_SESSION['localtimestamp'] = $localTimestamp;
$_SESSION['localtimezoneoffset'] = $localTimezoneOffset;

// Log successful ping
\bbsengine6\util\logentry("ping.php: Client ping received - timestamp={$localTimestamp}, offset={$localTimezoneOffset}");

// Return success response
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Ping received',
    'server_timestamp' => date('c')
]);
