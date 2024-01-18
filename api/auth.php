<?php
require_once 'vendor/autoload.php';
require_once 'GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php';
require_once 'common.php';

use Firebase\JWT\JWT;

$user_id = $data->id;
$code = $data->code;
$secret = "OTDUKSWBYJANHO7O";

$ga = new \PHPGangsta_GoogleAuthenticator();

$valid = $ga->verifyCode($secret, $code);
if ($valid) {
  // Kód je platný, autentizace byla úspěšná
  $token = JWT::encode(['key' => AUTH_KEY], JWT_KEY, 'HS256');
  echo json_encode($token);
} else {
  // Kód není platný
  http_response_code(400);
  echo json_encode(array('error' => 'Autorizační kód není platný.'));
}
