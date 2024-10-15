<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;
use Bramus\Router\Router;

$dotenv = Dotenv::createImmutable(paths: BASE_PATH);
$dotenv->load();

define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_ADMINISTRATOR_NAME', $_ENV['APP_ADMINISTRATOR_NAME'] ?? 'the administrator');
define('APP_BASE_URL', $_ENV['APP_BASE_URL'] ?? 'http://localhost:3000');
define('MAILGUN_ACTIVE', $_ENV['MAILGUN_ACTIVE'] == "true" ? true : false);
define('CF_TURNSTILE_ACTIVE', $_ENV['CF_TURNSTILE_ACTIVE'] == "true" ? true : false);
define('CF_TURNSTILE_SITEKEY', $_ENV['CF_TURNSTILE_SITEKEY'] ?? '');

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$router = new Router();
$router->setNamespace('madebyraygun\secureform\controllers');
$router->get('/', 'Create@handleGet');
$router->post('/', 'Create@handlePost');
$router->get('/created/{token}', 'Created@handleCreated');
$router->get('/generate-totp', 'GenerateTotp@generate');
$router->get('/retrieve/{token}', 'Retrieve@handleGet');
$router->post('/retrieve/{token}', 'Retrieve@handlePost');
$router->run();
