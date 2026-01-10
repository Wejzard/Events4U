<?php
require_once 'BaseService.php';
require_once __DIR__ . '/../dao/AuthDao.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class AuthService extends BaseService {
   private $auth_dao;
   public function __construct() {
       $this->auth_dao = new AuthDao();
       parent::__construct(new AuthDao);
   }


   public function get_user_by_email($email){
       return $this->auth_dao->get_user_by_email($email);
   }


   public function register($entity) {
  $firstName = trim($entity['first_name'] ?? '');
  $lastName  = trim($entity['last_name'] ?? '');
  $email     = trim($entity['email'] ?? '');
  $password  = $entity['password'] ?? '';
  $repeat    = $entity['repeat_password'] ?? null;

  if ($firstName === '' || strlen($firstName) < 2) {
    return ['success' => false, 'message' => 'First name too short.'];
  }
  if ($lastName === '' || strlen($lastName) < 2) {
    return ['success' => false, 'message' => 'Last name too short.'];
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return ['success' => false, 'message' => 'Invalid email format.'];
  }
  if (strlen($password) < 6) {
    return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
  }
  if ($repeat !== null && $password !== $repeat) {
    return ['success' => false, 'message' => 'Passwords do not match.'];
  }

  $email_exists = $this->auth_dao->get_user_by_email($email);
  if ($email_exists) {
    return ['success' => false, 'message' => 'Email already registered.'];
  }

  $newUser = [
    'first_name' => $firstName,
    'last_name'  => $lastName,
    'email'      => $email,
    'password'   => password_hash($password, PASSWORD_BCRYPT),
    'role'       => Roles::USER
  ];

  $created = parent::add($newUser);
  unset($created['password']);

  return ['success' => true, 'data' => $created];
}


   public function login($entity) {  
       if (empty($entity['email']) || empty($entity['password'])) {
           return ['success' => false, 'message' => 'Email and password are required.'];
       }


       $user = $this->auth_dao->get_user_by_email($entity['email']);
       if(!$user){
           return ['success' => false, 'message' => 'Invalid username or password.'];
       }


       if(!$user || !password_verify($entity['password'], $user['password']))
           return ['success' => false, 'message' => 'Invalid username or password.'];


       unset($user['password']);
      
       $jwt_payload = [
           'user' => $user,
           'iat' => time(),
           // If this parameter is not set, JWT will be valid for life. This is not a good approach
           'exp' => time() + (60 * 60 * 24) // valid for day
       ];


       $token = JWT::encode(
           $jwt_payload,
           Config::JWT_SECRET(),
           'HS256'
       );


       return ['success' => true, 'data' => array_merge($user, ['token' => $token])];             
   }
}
