<?php
require_once __DIR__ . '/../services/PaymentsService.php';
/**
 * @OA\Get(
 *     path="/payments",
 *     summary="Get all payments",
 *     security={{"JWT":{}}},
 *     operationId="getAllPayments",
 *     tags={"Payments"},
 *     @OA\Response(
 *         response=200,
 *         description="List of all payments",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="amount", type="number", format="float", example=150.75),
 *                 @OA\Property(property="status", type="string", example="completed")
 *             )
 *         )
 *     )
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
 *     summary="Get payment by ID",
 *     security={{"JWT":{}}},
 *     operationId="getPaymentById",
 *     tags={"Payments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the payment to retrieve",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment details",
 *         @OA\JsonContent(
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="amount", type="number", format="float", example=150.75),
 *             @OA\Property(property="status", type="string", example="completed")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Payment not found"
 *     )
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
 *     summary="Create a new payment",
 *     security={{"JWT":{}}},
 *     operationId="createPayment",
 *     tags={"Payments"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"amount", "status"},
 *             @OA\Property(property="amount", type="number", format="float", example=150.75),
 *             @OA\Property(property="status", type="string", example="completed")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Payment created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Payment created successfully"),
 *             @OA\Property(property="data", type="object", example={"id": 1, "amount": 150.75, "status": "completed"})
 *         )
 *     )
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
/**
 * @OA\Put(
 *     path="/payments/{id}",
 *     summary="Update an existing payment by ID (Admin only)",
 *     security={{"JWT":{}}},
 *     operationId="updatePayment",
 *     tags={"Payments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Payment ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"amount", "status"},
 *             @OA\Property(property="amount", type="number", format="float", example=175.00),
 *             @OA\Property(property="status", type="string", example="pending")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Payment updated successfully"),
 *             @OA\Property(property="data", type="object", example={"id": 1, "amount": 175.00, "status": "pending"})
 *         )
 *     )
 * )
 */
Flight::route('PUT /payments/@id', function ($id) {
     Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    $service = new PaymentsService();

    Flight::json([
        'message' => 'Payment updated successfully',
        'data' => $service->modify_payment($data, $id)
    ]);
});
/**
 * @OA\Delete(
 *     path="/payments/{id}",
 *     summary="Delete a payment by ID (Admin only)",
 *     security={{"JWT":{}}},
 *     operationId="deletePayment",
 *     tags={"Payments"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="Payment ID",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Payment deleted successfully."),
 *             @OA\Property(property="data", type="object", example={"id": 1})
 *         )
 *     )
 * )
 */
Flight::route('DELETE /payments/@id', function ($id) {
    Flight::auth_middleware()->authorizeRoles([Roles::ADMIN]);
    $service = new PaymentsService();

    Flight::json([
        'message' => 'Payment deleted successfully',
        'data' => $service->remove_payment($id)
    ]);
});