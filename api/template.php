<?php
require __DIR__ . '/vendor/autoload.php';

use \Latte\Engine;


$template = __DIR__ . '/templates/' . $_GET['id'] . '.latte';
$dataFile = __DIR__ . '/export/' . $_GET['id'] . '.json';
if (file_exists($template) && file_exists($dataFile)) {
    $data = file_get_contents($dataFile);
    $latte = new Engine();
    $latte->setTempDirectory(__DIR__ . '/temp');
    $latte->render($template, [ 'data' => json_decode($data)]);
} else {
    http_response_code(400);
}