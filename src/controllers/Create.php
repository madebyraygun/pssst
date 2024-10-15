<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';

use madebyraygun\secureform\services\Challenge;

class Create {
    private static $basePath;
    private static $maxLength;
    private static $csrfToken;
    private static $administrator;
    private static $twig;

    public static function init() {
        self::$basePath = BASE_PATH;
        self::$maxLength = 10000;
        self::$csrfToken = $_SESSION['csrf_token'];
        self::$administrator = APP_ADMINISTRATOR_NAME;
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        self::$twig = new \Twig\Environment($loader);
    }

    public static function handlePost() {
        self::init();
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Invalid CSRF token.'
            ]);
            exit;
        }
       
        if (CF_TURNSTILE_ACTIVE && !Challenge::verify($_POST['cf-turnstile-response']))
        {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Unable to verify the challenge.'
            ]);
            exit;
        }

        // Validate the token
        $token = $_POST['token'];
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Invalid token'
            ]);
            exit;
        }

        // Sanitize the input
        $query = trim($_POST['query']); // Trim whitespace
        $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
        $query = substr($query, 0, self::$maxLength); 

        // Define the file path
        $filePath = self::$basePath . '/data/.' . $token;

        if (file_put_contents($filePath, $query) !== false) {
            header("Location: created/" . $token);
            exit;
        } else {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Failed to write to file.'
            ]);
        }
    }

    public static function handleGet() {
        self::init();
        $token = bin2hex(random_bytes(16));
        $_SESSION['token'] = $token;
        
        echo self::$twig->render('index.twig', [
            'token' => $token,
            'cfTsSiteKey' => CF_TURNSTILE_SITEKEY,
            'cfTsActive' => CF_TURNSTILE_ACTIVE,
            'csrfToken' => self::$csrfToken,
            'maxLength' => self::$maxLength,
            'administrator' => self::$administrator
        ]);   
    }
}
