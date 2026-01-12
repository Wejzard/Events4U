<?php
require_once __DIR__ . '/BaseApiTestCase.php';
final class AuthorizationTest extends BaseApiTestCase
{
    public function test_admin_can_access_admin_route(): void
    {
        $token = $this->login(
            getenv('ADMIN_EMAIL'),
            getenv('ADMIN_PASSWORD')
        );

        [$status] = $this->request(
            'GET',
            getenv('ADMIN_ONLY_PATH'),
            null,
            $token
        );

        $this->assertSame(200, $status);
    }

    public function test_user_cannot_access_admin_route(): void
    {
        $token = $this->login(
            getenv('USER_EMAIL'),
            getenv('USER_PASSWORD')
        );

        [$status] = $this->request(
            'GET',
            getenv('ADMIN_ONLY_PATH'),
            null,
            $token
        );

        $this->assertSame(403, $status);
    }
}
