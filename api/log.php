<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // PUT
  $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? false;
  if ($authorization !== '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP') {
    http_response_code(401);
    return;
  }
  $x = $data->validni ? ' ' : 'X';
  file_put_contents(__DIR__ . '/logs/' . date("Y-m-d") . ".txt", date("H:i:s") . " [$x]: " . $data->prikaz . "\n", FILE_APPEND);
  http_response_code(204);
  exit();
}