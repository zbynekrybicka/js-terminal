<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/common.php');


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $originPath = __DIR__ . '/images/';
  echo json_encode(array_map(function($item) use($originPath) {
    return "https://{$_SERVER['HTTP_HOST']}/api/images/" . str_replace($originPath, "", $item);
  }, glob($originPath . '*')));
}


$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? false;
if ($authorization !== '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP') {
  http_response_code(401);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $target_file = __DIR__ . "/images/" . $_POST['path'];
  move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
  echo json_encode("https://{$_SERVER['HTTP_HOST']}/api/images/". $_POST['path']);
  http_response_code(201);
  exit;
}