<?php
require_once __DIR__ . '/../services/UsersService.php';




/**
 * @OA\Post(
 *     path="/change-password",
 *     summary="Change my password",
 *     security={{"ApiKey": {}}},
 *     operationId="changeMyPassword",
 *     tags={"Users"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"old_password","new_password","confirm_password"},
 *             @OA\Property(
 *                 property="old_password",
 *                 type="string",
 *                 example="old_password123",
 *                 description="Your current password"
 *             ),
 *             @OA\Property(
 *                 property="new_password",
 *                 type="string",
 *                 example="new_password123",
 *                 description="Your new password (min 6 chars)"
 *             ),
 *             @OA\Property(
 *                 property="confirm_password",
 *                 type="string",
 *                 example="new_password123",
 *                 description="Must match new_password"
 *             ),
 *             @OA\Property(
 *                 property="email",
 *                 type="string",
 *                 example="user@example.com",
 *                 description="Optional; ignored. Identity is taken from JWT."
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Password updated successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Password updated successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Old password is incorrect.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid token.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Forbidden: role not allowed")
 *         )
 *     )
 * )
 */
Flight::route('POST /change-password', function () {
  // ✅ Must be logged in
  Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::USER]);

  $data = Flight::request()->data->getData();

  // ✅ Authenticated user from token
  $user = Flight::get('user');
  if (!$user) {
    Flight::halt(401, json_encode(["message" => "Invalid token."]));
  }

  // ✅ Force identity from token (do NOT trust client-sent email/user_id)
  $userId = (int)($user->user_id ?? $user->id ?? 0);
  if (!$userId) {
    Flight::halt(401, json_encode(["message" => "Invalid token: missing user id."]));
  }

  $data['user_id'] = $userId;

  // ✅ Require fields (email is NOT required anymore)
  if (!isset($data['old_password']) || trim((string)$data['old_password']) === '') {
    Flight::halt(400, json_encode(["message" => "old_password is required."]));
  }
  if (!isset($data['new_password']) || trim((string)$data['new_password']) === '') {
    Flight::halt(400, json_encode(["message" => "new_password is required."]));
  }
  if (!isset($data['confirm_password']) || trim((string)$data['confirm_password']) === '') {
    Flight::halt(400, json_encode(["message" => "confirm_password is required."]));
  }

  $service = new UsersService();
  $service->change_password($data);

  Flight::json([
    'message' => 'Password updated successfully'
  ]);
});


/*
 * Admin-only routes kept in code, but intentionally hidden from Swagger documentation
 * for security reasons (not shown in Swagger UI).
 */

/*
 * @OA\Get(
 *     path="/users",
 *     summary="Get all users (Admin only)",
 *     security={{"JWT":{}}},
 *     operationId="getAllUsers",
 *     tags={"User"},
 *     @OA\Response(
 *         response=200,
 *         description="List of all users"
 *     )
 * )
 */
Flight::route('GET /users', function () {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $service = new UsersService();
    Flight::json($service->get_all());
});

/*
 * @OA\Get(
 *     path="/users/{id}",
 *     summary="Get user by ID (Admin only)",
 *     security={{"JWT":{}}},
 *     operationId="getUserById",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the user to retrieve",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User found"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found"
 *     )
 * )
 */
Flight::route('GET /users/@id', function ($id) {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $service = new UsersService();
    Flight::json($service->get_by_id($id));
});

/*
 * @OA\Put(
 *     path="/users/{id}",
 *     summary="Update an existing user by ID (Admin only)",
 *     security={{"JWT":{}}},
 *     operationId="updateUser",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the user to update",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"username", "email"},
 *             @OA\Property(property="username", type="string", example="user123"),
 *             @OA\Property(property="email", type="string", example="user@example.com")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User updated successfully"
 *     )
 * )
 */
Flight::route('PUT /users/@id', function ($id) {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $data = Flight::request()->data->getData();
    $service = new UsersService();

    Flight::json([
        'message' => 'User updated successfully',
        'data' => $service->update($data, $id, 'user_id')
    ]);
});

/*
 * @OA\Delete(
 *     path="/users/{id}",
 *     summary="Delete a user by ID (Admin only)",
 *     security={{"JWT":{}}},
 *     operationId="deleteUser",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the user to delete",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User deleted successfully"
 *     )
 * )
 */
Flight::route('DELETE /users/@id', function ($id) {
    Flight::auth_middleware()->authorizeRoles(Roles::ADMIN);
    $service = new UsersService();
    $service->delete($id);
    Flight::json(['message' => "User deleted successfully."]);
});
