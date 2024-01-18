<?php
require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/common.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $soubor = __DIR__ . '/export/' . $_GET['id'] . ".json";
    if ($data) {
        file_put_contents($soubor, json_encode($data));
    } else {
        try {
            unlink($soubor);
        } catch (\Exception $e) {}
    }
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $soubor = __DIR__ . '/export/' . $_GET['id'] . ".json";
    if (file_exists($soubor)) {
        echo file_get_contents($soubor);
        exit;
    } else {
        http_response_code(400);
        exit;
    }
}