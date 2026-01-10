<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {

  public function verifyToken($token){
    if(!$token) Flight::halt(401, "Missing authentication header");

    $decoded_token = JWT::decode($token, new Key(Config::JWT_SECRET(), 'HS256'));
    Flight::set('user', $decoded_token->user);
    Flight::set('jwt_token', $token);
    return TRUE;
  }

  public function authorizeRole($requiredRole) {
    $user = Flight::get('user');
    if (!$user || ($user->role ?? null) !== $requiredRole) {
      Flight::halt(403, 'Access denied: insufficient privileges');
    }
  }

  // ✅ Harden: allow string OR array
  public function authorizeRoles($roles) {
    $user = Flight::get('user');
    if (!$user) Flight::halt(401, 'Unauthorized');

    if (!is_array($roles)) $roles = [$roles];

    $role = $user->role ?? null;
    if (!$role || !in_array($role, $roles, true)) {
      Flight::halt(403, 'Forbidden: role not allowed');
    }
  }

  public function authorizePermission($permission) {
    $user = Flight::get('user');
    $perms = $user->permissions ?? [];
    if (!is_array($perms)) $perms = [];

    if (!in_array($permission, $perms, true)) {
      Flight::halt(403, 'Access denied: permission missing');
    }
  }
}
