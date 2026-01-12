<?php
require_once __DIR__ . '/BaseApiTestCase.php';
final class EventsTest extends BaseApiTestCase
{
    public function test_authenticated_user_can_list_events(): void
    {
        $token = $this->login(
            getenv('USER_EMAIL'),
            getenv('USER_PASSWORD')
        );

        [$status, $json] = $this->request(
            'GET',
            getenv('EVENTS_PATH'),
            null,
            $token
        );

        $this->assertSame(200, $status);
        $this->assertIsArray($json);
    }
}

