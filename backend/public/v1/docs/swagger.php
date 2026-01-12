<?php
// Swagger UI must receive CLEAN JSON only (no warnings/notices/whitespace).

ini_set('display_errors', 0);          // do not print warnings/notices into output
ini_set('log_errors', 1);             // log errors to PHP/Apache error log
error_reporting(E_ALL);

if (ob_get_level() === 0) {
  ob_start();
}

require __DIR__ . '/../../../vendor/autoload.php';

try {
  if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
      define('BASE_URL', 'http://localhost/HajrudinVejzovic/WebProject/backend');
  } else {
      define('BASE_URL', 'https://add-production-server-after-deployment/backend');
  }

  $openapi = \OpenApi\Generator::scan([
      __DIR__ . '/doc_setup.php',
      __DIR__ . '/../../../routes'
  ]);

  // Remove any echoed output before returning JSON
  ob_clean();

  header('Content-Type: application/json; charset=utf-8');
  echo $openapi->toJson();

} catch (\Throwable $e) {
  ob_clean();
  header('Content-Type: application/json; charset=utf-8', true, 500);
  echo json_encode([
    "error" => "Failed to generate OpenAPI spec",
    "message" => $e->getMessage()
  ]);
}
