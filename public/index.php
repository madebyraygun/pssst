<?php

namespace madebyraygun\secureform\public;
require '../vendor/autoload.php';
use Dotenv\Dotenv;

define('BASE_PATH', dirname(__DIR__));
$dotenv = Dotenv::createImmutable(paths: BASE_PATH);
$dotenv->load();
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$router = new \Bramus\Router\Router();
$router->setNamespace('madebyraygun\secureform\controllers');
$router->get('/', 'Create@handleGet');
$router->post('/', 'Create@handlePost');
$router->get('/success', 'Success@handleSuccess');
$router->get('/retrieve/{token}', 'Retrieve@handleGet');
$router->post('/retrieve/{token}', 'Retrieve@handlePost');
$router->run();
