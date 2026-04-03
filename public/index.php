<?php


ini_set('session.name', 'PHPSESSID');

require_once __DIR__ . '/../vendor/autoload.php';

use App\Routes\Api;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

$api = new Api();
$api->handleRequest();
