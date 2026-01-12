<?php
/**
* @OA\Info(
*     title="Event4u API",
*     description="Event4u backend API (FlightPHP + JWT). Public routes: /auth/login, /auth/register. All other routes require Authentication header.",
*     version="1.0",
*     @OA\Contact(
*         email="web2001programming@gmail.com",
*         name="Web Programming"
*     )
* )
*/
/**
* @OA\Server(
*     url="http://localhost/HajrudinVejzovic/WebProject/backend",
*     description="Local development server"
* )
*/
/**
* @OA\SecurityScheme(
*     securityScheme="ApiKey",
*     type="apiKey",
*     in="header",
*     name="Authentication",
*     description="Paste the JWT token here (no 'Bearer ' prefix unless your backend expects it)."
* )
*/
