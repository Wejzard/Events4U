<?php

use PHPUnit\Framework\TestCase;

abstract class BaseApiTestCase extends TestCase
{
    protected string $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = rtrim(getenv('API_BASE_URL'), '/');
    }

    protected function url(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    protected function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?string $jwt = null
    ): array {
        $ch = curl_init($this->url($path));

        $headers = ['Content-Type: application/json'];
        if ($jwt) {
            // IMPORTANT: matches index.php
            $headers[] = 'Authentication: ' . $jwt;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [$status, json_decode($response, true), $response];
    }

    protected function login(string $email, string $password): string
{
    [$status, $json, $raw] = $this->request(
        'POST',
        getenv('LOGIN_PATH'),
        ['email' => $email, 'password' => $password]
    );

    $this->assertSame(200, $status, "Login failed: $raw");
    $this->assertArrayHasKey('data', $json);

    // Your API returns JWT inside data (object)
    $data = $json['data'];

    // Common patterns: token / jwt
    $token = $data['token'] ?? $data['jwt'] ?? null;

    $this->assertNotEmpty($token, "JWT token not found in login response: $raw");

    return $token;
}
}
