<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;
use Bramus\Router\Router;

$dotenv = Dotenv::createImmutable(paths: BASE_PATH);
$dotenv->load();

define('VENDOR_PATH', BASE_PATH . '/vendor');
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
$router->get('/system/css', '\madebyraygun\pssst\web\Assets@css');
$router->get('/system/js', '\madebyraygun\pssst\web\Assets@js');
$router->get('/system/logo-dark', '\madebyraygun\pssst\web\Assets@logoDark');
$router->get('/system/logo-light', '\madebyraygun\pssst\web\Assets@logoLight');
$router->get('/', '\madebyraygun\pssst\controllers\Create@handleGet');
$router->post('/', '\madebyraygun\pssst\controllers\Create@handlePost');
$router->get('/created/{uid}', '\madebyraygun\pssst\controllers\Created@handleCreated');
$router->get('/generate-totp', '\madebyraygun\pssst\controllers\GenerateTotp@generate');
$router->get('/retrieve/{uid}', '\madebyraygun\pssst\controllers\Retrieve@handleGet');
$router->post('/retrieve/{uid}', '\madebyraygun\pssst\controllers\Retrieve@handlePost');
$router->run();
