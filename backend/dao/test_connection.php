<?php
require_once __DIR__ . '/UsersDao.php';
require_once __DIR__ . '/BaseDao.php';
require_once __DIR__ . '/../config.php';

//These are my tests for the UsersDao
$users_dao = new UsersDao();
$new_user = [
    'full_name' => 'Test User',
    'email' => 'testuser@example.com',
    'password_hash' => 'testpasswordhash',
    'role' => 'user',
    'image' => '',
    'created_at' => date('Y-m-d H:i:s')
];

$added_user = $users_dao->add($new_user); 

print_r( "Added User: \n");
print_r($added_user);

//Test for retrieving all
$users = $users_dao->get_all();  
print_r ( "Users List: \n");
print_r($users);


// Update test
$updated_user = [
    'full_name' => 'Test User',
    'email' => 'testuser@example.com',
    'password_hash' => 'newpasswordhash',
    'role' => 'admin',
    'image' => '',
    'created_at' => date('Y-m-d H:i:s')
];
$user_id_to_update = 11;
$users_dao->update($updated_user, $user_id_to_update); 

$user_id_to_update = 5;
// Delete Testing
$users_dao->delete($user_id_to_update); 
//By id test
print_r($users_dao->get_by_id(2));

//specific update test
$user_id = 1; 
$nickname = "Test Updated Nickname";
$password = "TestPassword123"; 
$password_hash = password_hash($password, PASSWORD_DEFAULT); 


$rowsAffected = $usersDao->update_user_info($user_id, $nickname, $password_hash);


echo "Rows Affected: " . $rowsAffected . "\n";


$user = $usersDao->get_by_id($user_id);
echo "Updated User Information:\n";
print_r($user);

