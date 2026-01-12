<?php
require_once __DIR__ . '/../services/PaymentsService.php';

/**
 * @OA\Get(
 *     path="/payments",
 *     summary="Get all payments (user-accessible in this project)",
 *     operationId="getPayments",
 *     tags={"Payments"},
 *     security={{"ApiKey": {}}},
 *     @OA\Response(response=200, description="Payments list"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('GET /payments', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $service = new PaymentsService();
  Flight::json($service->fetch_all_payments());
});

/**
 * @OA\Get(
 *     path="/payments/{id}",
 *     summary="Get payment by ID (user-accessible in this project)",
 *     operationId="getPaymentById",
 *     tags={"Payments"},
 *     security={{"ApiKey": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(response=200, description="Payment details"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('GET /payments/@id', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $service = new PaymentsService();
  Flight::json($service->fetch_payment_by_id($id));
});

/**
 * @OA\Post(
 *     path="/payments",
 *     summary="Create a payment (user-accessible in this project)",
 *     operationId="createPayment",
 *     tags={"Payments"},
 *     security={{"ApiKey": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="order_id", type="integer", example=12),
 *             @OA\Property(property="amount", type="number", format="float", example=150.75),
 *             @OA\Property(property="status", type="string", example="completed")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Payment created"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Forbidden")
 * )
 */
Flight::route('POST /payments', function () {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $data = Flight::request()->data->getData();
  $service = new PaymentsService();

  Flight::json([
    'message' => 'Payment created successfully',
    'data' => $service->create_payment($data)
  ]);
});

// Admin-only routes kept (no Swagger docs)
Flight::route('PUT /payments/@id', function ($id) {
  Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
  $data = Flight::request()->data->getData();
  $service = new PaymentsService();

  Flight::json([
    'message' => 'Payment updated successfully',
    'data' => $service->modify_payment($data, $id)
  ]);
});

Flight::route('DELETE /payments/@id', function ($id) {
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);
  $service = new PaymentsService();

  Flight::json([
    'message' => 'Payment deleted successfully',
    'data' => $service->remove_payment($id)
  ]);
});
