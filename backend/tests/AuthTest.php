<?php
require_once __DIR__ . '/BaseApiTestCase.php';
final class AuthTest extends BaseApiTestCase
{
    public function test_admin_login_success(): void
    {
        $token = $this->login(
            getenv('ADMIN_EMAIL'),
            getenv('ADMIN_PASSWORD')
        );

        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    public function test_login_wrong_password_fails(): void
    {
        [$status] = $this->request(
            'POST',
            getenv('LOGIN_PATH'),
            [
                'email' => getenv('ADMIN_EMAIL'),
                'password' => 'wrong-password'
            ]
        );

        $this->assertSame(401, $status);
    }
}
