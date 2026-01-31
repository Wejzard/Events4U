<?php
// backend/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/data/Roles.php';
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/services/UsersService.php';
require_once __DIR__ . '/services/EventsService.php';
require_once __DIR__ . '/services/TicketsService.php';
require_once __DIR__ . '/services/PaymentsService.php';
require_once __DIR__ . '/services/OrdersService.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

Flight::register('auth_service', 'AuthService');
Flight::register('user_service', 'UsersService');
Flight::register('events_service', 'EventsService');
Flight::register('ticket_service', 'TicketsService');
Flight::register('payment_service', 'PaymentsService');
Flight::register('order_service', 'OrdersService');
Flight::register('auth_middleware', 'AuthMiddleware');

/**
 * ✅ Public debug route (TEMPORARY)
 * Visit: https://event4u.ba/backend/debug-headers
 * This will show exactly what headers PHP/Flight can see.
 */
Flight::route('GET /debug-headers', function () {
    // getallheaders() is most reliable for this specific issue
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    Flight::json([
        "flight_Authorization" => Flight::request()->getHeader("Authorization"),
        "flight_Authentication" => Flight::request()->getHeader("Authentication"),
        "getallheaders" => $headers,
        "server_HTTP_AUTHORIZATION" => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        "server_AUTHORIZATION" => $_SERVER['AUTHORIZATION'] ?? null,
        "request_uri" => $_SERVER['REQUEST_URI'] ?? null,
    ]);
});

/**
 * ✅ Public health route
 */
Flight::route('GET /health', function () {
    Flight::json(["status" => "ok"]);
});

/**
 * ✅ Global auth middleware with bypass list
 */
Flight::route('/*', function () {
    $url = Flight::request()->url;

    // Public endpoints (no JWT required)
    if (
        strpos($url, '/auth/login') === 0 ||
        strpos($url, '/auth/register') === 0 ||
        strpos($url, '/health') === 0 ||
        strpos($url, '/debug-headers') === 0
    ) {
        return TRUE;
    }

    try {
        // Primary: standard header
        $token = Flight::request()->getHeader("Authorization");

        // Fallbacks: useful while debugging hosting header stripping
        if (!$token && function_exists('getallheaders')) {
            $all = getallheaders();
            $token = $all['Authorization'] ?? $all['authorization'] ?? $token;
        }
        if (!$token) {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['AUTHORIZATION'] ?? null;
        }
        if (!$token) {
            // Temporary compatibility fallback (remove later if you want)
            $token = Flight::request()->getHeader("Authentication");
        }

        return Flight::auth_middleware()->verifyToken($token);
    } catch (\Exception $e) {
        // Return JSON consistently
        Flight::json(["message" => $e->getMessage()], 401);
    }
});

require_once __DIR__ . '/routes/AuthRoutes.php';
require_once __DIR__ . '/routes/UsersRoutes.php';
require_once __DIR__ . '/routes/EventsRoutes.php';
require_once __DIR__ . '/routes/TicketsRoutes.php';
require_once __DIR__ . '/routes/PaymentsRoutes.php';
require_once __DIR__ . '/routes/OrdersRoutes.php';

Flight::start();
