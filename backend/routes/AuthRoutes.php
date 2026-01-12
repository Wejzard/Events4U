<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../services/UsersService.php';

Flight::group('/auth', function() {

  /**
   * @OA\Post(
   *     path="/auth/register",
   *     summary="Register a new user",
   *     operationId="authRegister",
   *     tags={"Auth"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"username","email","password"},
   *             @OA\Property(property="username", type="string", example="user123"),
   *             @OA\Property(property="email", type="string", example="user@example.com"),
   *             @OA\Property(property="password", type="string", example="password123")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User registered successfully",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User registered successfully"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Registration failed",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Registration failed")
   *         )
   *     )
   * )
   */
  Flight::route('POST /register', function () {
    $data = Flight::request()->data->getData();
    $response = Flight::auth_service()->register($data);

    if ($response['success']) {
      Flight::json([
        'message' => 'User registered successfully',
        'data' => $response['data']
      ]);
    } else {
      Flight::halt(401, json_encode(['message' => $response['message']]));
    }
  });

  /**
   * @OA\Post(
   *     path="/auth/login",
   *     summary="Login using email and password",
   *     operationId="authLogin",
   *     tags={"Auth"},
   *     @OA\RequestBody(
   *         required=true,
   *         @OA\JsonContent(
   *             required={"email","password"},
   *             @OA\Property(property="email", type="string", example="demo@gmail.com"),
   *             @OA\Property(property="password", type="string", example="some_password")
   *         )
   *     ),
   *     @OA\Response(
   *         response=200,
   *         description="User logged in successfully (JWT in response data)",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="User logged in successfully"),
   *             @OA\Property(property="data", type="object")
   *         )
   *     ),
   *     @OA\Response(
   *         response=401,
   *         description="Invalid credentials",
   *         @OA\JsonContent(
   *             @OA\Property(property="message", type="string", example="Invalid credentials")
   *         )
   *     )
   * )
   */
  Flight::route('POST /login', function() {
    $data = Flight::request()->data->getData();

    $response = Flight::auth_service()->login($data);

    if ($response['success']) {
      Flight::json([
        'message' => 'User logged in successfully',
        'data' => $response['data']
      ]);
    } else {
      Flight::halt(401, json_encode(["message" => $response['message']]));
    }
  });

});
