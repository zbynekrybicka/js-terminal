<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/common.php');

$authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? false;
if ($authorization !== '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP') {
  http_response_code(401);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  // GET
  if ($_GET['id']) {
    $filename = __DIR__ . '/soubory/' . $_GET['id'] . ".crypt";
    if (file_exists($filename)) {
      echo file_get_contents($filename);
    } else {
      http_response_code(400);
    }
    exit();  
  } else {
    $result = [];
    $soubory = scandir(__DIR__ . '/soubory');
    foreach ($soubory as $soubor) {
      if (preg_match("/\.crypt$/", $soubor)) {
        $result[] = str_replace('.crypt', '', $soubor);
      }
    }
    echo json_encode($result);
    exit();
  }


} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
  // PUT
  $filename = __DIR__ . '/soubory/' . $data->nazev . ".crypt";
  if (file_exists($filename)) {
    $prev = file_get_contents($filename);
    if ($data->data !== $prev) {
      file_put_contents(__DIR__ . '/../../../backup/' . $data->nazev . "-" . time() . ".crypt", $prev);
    }
  }
  file_put_contents($filename, $data->data);
  file_put_contents(__DIR__ . '/../../../backup/' . $data->nazev . ".crypt", $data->data);
  http_response_code(204);
}