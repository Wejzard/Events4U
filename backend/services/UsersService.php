<?php
require_once 'BaseService.php';
require_once __DIR__ . "/../dao/UsersDao.php";


class UsersService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new UsersDao());
    }
   /* 
    public function register($data)
{
    
    
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $repeatPassword = $data['repeat_password'] ?? '';
    $user_data['role'] = Roles::USER;
    
    if (strlen($firstName) < 2) {
        Flight::halt(400, json_encode(["error" => "First name too short."]));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Flight::halt(400, json_encode(["error" => "Invalid email format."]));
    }

    if ($password !== $repeatPassword) {
        Flight::halt(400, json_encode(["error" => "Passwords do not match."]));
    }

    // Remove repeat_password before inserting
    unset($data['repeat_password']);

    if (strlen($password) < 6) {
        Flight::halt(400, json_encode(["error" => "Password must be at least 6 characters."]));
    }

    
    if ($this->dao->get_user_by_email($email)) {
        Flight::halt(400, json_encode(["error" => "Email already exists."]));
    }

    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    
    $user = [
        "first_name" => $firstName,
        "last_name" => $lastName,
        "email" => $email,
        "password" => $hashedPassword,
          "role" => Roles::USER
    ];

    $this->dao->add($user);

    return ["message" => "User registered successfully."];
}

public function login($data) {
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Flight::halt(400, json_encode(["error" => "Invalid email format."]));
    }

    $user = $this->dao->get_user_by_email($email);

    if (!$user || !password_verify($password, $user['password'])) {
        Flight::halt(401, json_encode(["error" => "Invalid email or password."]));
    }

    unset($user['password']); // never send hashed password back

    return [
        "message" => "Login successful.",
        "user" => $user
    ];
}
*/
public function change_password($data) {
    $email = trim($data['email'] ?? '');
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Flight::halt(400, json_encode(["message" => "Invalid email format."]));
    }

    if (strlen($newPassword) < 6) {
        Flight::halt(400, json_encode(["message" => "Password must be at least 6 characters."]));
    }

    if ($newPassword !== $confirmPassword) {
        Flight::halt(400, json_encode(["message" => "Passwords do not match."]));
    }

    $user = $this->dao->get_user_by_email($email);

    if (!$user) {
        Flight::halt(404, json_encode(["message" => "User not found."]));
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $this->dao->update(['password' => $hashedPassword], $user['user_id'], 'user_id');

    return ["message" => "Password updated."];
}




}