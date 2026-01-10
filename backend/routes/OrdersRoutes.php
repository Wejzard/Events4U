<?php
require_once __DIR__ . '/../services/OrdersService.php';

Flight::route('POST /orders', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $user = Flight::get('user');
  // support both object shapes
  $userId = (int)($user->user_id ?? $user->id ?? 0);
  if (!$userId) {
    Flight::halt(401, json_encode(["message" => "Invalid token: missing user id."]));
  }

  $data = Flight::request()->data->getData();

  $event_id = $data["event_id"] ?? null;
  $quantity = $data["quantity"] ?? 1;
  $action   = $data["action"] ?? null;   // BUY or RESERVE
  $currency = $data["currency"] ?? "KM";

  $res = Flight::order_service()->create_order_flow($userId, $event_id, $quantity, $action, $currency);

  Flight::json(["message" => "OK", "data" => $res], 201);
});

Flight::route('GET /orders/me', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $user = Flight::get('user');
  $userId = (int)($user->user_id ?? $user->id ?? 0);
  if (!$userId) {
    Flight::halt(401, json_encode(["message" => "Invalid token: missing user id."]));
  }

  Flight::json(Flight::order_service()->my_orders($userId));
});

// ------------------- ADMIN CRUD (kept) -------------------

Flight::route('GET /orders', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);
  Flight::json(Flight::order_service()->admin_get_all());
});

Flight::route('GET /orders/@id', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);
  Flight::json(Flight::order_service()->get_by_id($id));
});

Flight::route('PUT /orders/@id', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);
  $data = Flight::request()->data->getData();
  Flight::json(Flight::order_service()->admin_update($data, $id));
});

Flight::route('DELETE /orders/@id', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);
  Flight::order_service()->admin_delete($id);
  Flight::json(["message" => "Order deleted successfully"]);
});

Flight::route('DELETE /orders/@id/cancel', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $user = Flight::get('user');
  $userId = (int)($user->user_id ?? $user->id ?? 0);
  if (!$userId) Flight::halt(401, json_encode(["message" => "Invalid token."]));

  Flight::order_service()->cancel_my_reservation($userId, (int)$id);

  Flight::json(["message" => "Reservation cancelled."]);
});

