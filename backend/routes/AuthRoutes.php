<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../services/UsersService.php';
Flight::group('/auth', function() {
  /**
 * @OA\Post(
 *     path="/register",
 *     summary="Register a new user",
 *     operationId="registerUser",
 *     tags={"User"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"username", "email", "password"},
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
 *             @OA\Property(property="data", type="object", example={"id": 1, "username": "user123"})
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
    *      path="/auth/login",
    *      tags={"auth"},
    *      summary="Login to system using email and password",
    *      @OA\Response(
    *           response=200,
    *           description="Student data and JWT"
    *      ),
    *      @OA\RequestBody(
    *          description="Credentials",
    *          @OA\JsonContent(
    *              required={"email","password"},
    *              @OA\Property(property="email", type="string", example="demo@gmail.com", description="Student email address"),
    *              @OA\Property(property="password", type="string", example="some_password", description="Student password")
    *          )
    *      )
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




/**
 * @OA\Post(
 *     path="/login",
 *     summary="Login an existing user",
 *     operationId="loginUser",
 *     tags={"User"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", example="user@example.com"),
 *             @OA\Property(property="password", type="string", example="password123")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="token", type="string", example="jwt_token_example")
 *         )
 *     )
 * )
 */
/*

RUTA IZ USERS SERVICE-A

Flight::route('POST /login', function () {
    $data = Flight::request()->data->getData();
    $service = new UsersService();

    Flight::json($service->login($data));
});
*/







