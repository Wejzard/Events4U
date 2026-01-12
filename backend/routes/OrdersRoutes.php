<?php
require_once __DIR__ . '/../services/OrdersService.php';

/**
 * @OA\Post(
 *     path="/orders",
 *     summary="Create an order (BUY or RESERVE)",
 *     operationId="createOrder",
 *     tags={"Orders"},
 *     security={{"ApiKey": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"event_id","quantity","action"},
 *             @OA\Property(property="event_id", type="integer", example=1),
 *             @OA\Property(property="quantity", type="integer", example=2),
 *             @OA\Property(property="action", type="string", example="BUY"),
 *             @OA\Property(property="currency", type="string", example="KM")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Order created"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
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

/**
 * @OA\Get(
 *     path="/orders/me",
 *     summary="Get my orders",
 *     operationId="getMyOrders",
 *     tags={"Orders"},
 *     security={{"ApiKey": {}}},
 *     @OA\Response(response=200, description="My orders"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
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

/**
 * @OA\Get(
 *     path="/orders/{id}",
 *     summary="Get order by ID",
 *     operationId="getOrderById",
 *     tags={"Orders"},
 *     security={{"ApiKey": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(response=200, description="Order details"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
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

/**
 * @OA\Delete(
 *     path="/orders/{id}/cancel",
 *     summary="Cancel my reservation",
 *     operationId="cancelMyReservation",
 *     tags={"Orders"},
 *     security={{"ApiKey": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=10)
 *     ),
 *     @OA\Response(response=200, description="Reservation cancelled"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('DELETE /orders/@id/cancel', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $user = Flight::get('user');
  $userId = (int)($user->user_id ?? $user->id ?? 0);
  if (!$userId) Flight::halt(401, json_encode(["message" => "Invalid token."]));

  Flight::order_service()->cancel_my_reservation($userId, (int)$id);

  Flight::json(["message" => "Reservation cancelled."]);
});
