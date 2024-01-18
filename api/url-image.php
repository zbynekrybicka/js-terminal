<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/common.php');

$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? false;
if ($authorization !== '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP') {
  http_response_code(401);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_file = __DIR__ . "/images/" . $data->path;
    $image = file_get_contents($data->url);
    file_put_contents($target_file, $image);
    echo json_encode("https://{$_SERVER['HTTP_HOST']}/api/images/". $data->path);
    http_response_code(201);
    exit;
}