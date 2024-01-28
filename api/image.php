<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/common.php');


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $originPath = __DIR__ . '/images/';
  echo json_encode(array_map(function($item) use($originPath) {
    return str_replace($originPath, "", $item);
  }, glob($originPath . '*')));
}


$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? false;
if ($authorization !== '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP') {
  http_response_code(401);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $path = md5(time());
  $target_file = __DIR__ . "/images/" . $path;
  move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
  echo json_encode($path);
  http_response_code(201);
  exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
  $target_file = __DIR__ . "/images/" . $_GET['id'];
  unlink($target_file);
}