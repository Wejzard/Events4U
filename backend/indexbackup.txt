<?php
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

//error_log('Requested URL: ' . Flight::request()->url);


Flight::route('/*', function () {
    if (
        strpos(Flight::request()->url, '/auth/login') === 0 ||
        strpos(Flight::request()->url, '/auth/register') === 0
    ) {
        return TRUE;
    } else {
        try {
            $token = Flight::request()->getHeader("Authentication");
            return Flight::auth_middleware()->verifyToken($token);
        } catch (\Exception $e) {
            Flight::halt(401, json_encode(["message" => $e->getMessage()]));

        }
    }
});



require_once __DIR__ . '/routes/AuthRoutes.php';
require_once __DIR__ . '/routes/UsersRoutes.php';
require_once __DIR__ . '/routes/EventsRoutes.php';
require_once __DIR__ . '/routes/TicketsRoutes.php';
require_once __DIR__ . '/routes/PaymentsRoutes.php';
require_once __DIR__ . '/routes/OrdersRoutes.php';

Flight::start();