<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  // PUT
  $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? false;
  if ($authorization !== '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP') {
    http_response_code(401);
    return;
  }
  file_put_contents(__DIR__ . '/commands.json', json_encode($data));
  http_response_code(204);
  exit();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $commands = file_get_contents(__DIR__ . '/commands.json');
    if (json_decode($commands)) {
        echo $commands;
    } else {
        echo "{}";
    }
    http_response_code(200);
    exit();
}