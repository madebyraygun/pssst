<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

use madebyraygun\pssst\base\TwigLoader;
use madebyraygun\pssst\helpers\Uuid;
use madebyraygun\pssst\services\Challenge;


class Create {
    private static $sessionCsrfToken;
    private static $basePath;
    private static $maxLength;
    private static $twig;

    public static function init() {
        self::$basePath = BASE_PATH;
        self::$maxLength = 10000;
        self::$sessionCsrfToken = $_SESSION['csrf_token'];
        self::$twig = TwigLoader::getTwig();
    }

    public static function handlePost() {
        self::init();
        if (!hash_equals(self::$sessionCsrfToken, $_POST['csrf_token'])) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid CSRF token.'
            ]);
            exit;
        }
       
        if (CF_TURNSTILE_ACTIVE && !Challenge::verify($_POST['cf-turnstile-response']))
        {
            echo self::$twig->render('message.twig', [
                'message' => 'Unable to verify the challenge.'
            ]);
            exit;
        }

        // Validate the uuid
        $uuid = $_POST['uuid'];
        if (!Uuid::validate($uuid)) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid ID.'
            ]);
            exit;
        }

        // Sanitize the input
        $message = trim($_POST['message']);
        $message = substr($message, 0, self::$maxLength); 
        
        // Encrypt the message
        $key = bin2hex(random_bytes(8));
        $iv = random_bytes(16); //
        $encryptedMessage = openssl_encrypt($message, 'aes-256-cbc', $key, 0, $iv);
        
        // Define the file path
        $filePath = self::$basePath . '/data/' . $uuid . '.json';
        $fileContents = json_encode([
            'created' => time(),
            'expires' => false, //@todo
            'viewed' => false,
            'deleteAfterView' => false,// @todo
            'isPasswordProtected' => false,// @todo
            'iv' => bin2hex($iv),
            'message' => $encryptedMessage
        ]);
        if (file_put_contents($filePath, $fileContents ) !== false) {
            header("Location: created/" . $uuid . "/" . $key);
            exit;
        } else {
            echo self::$twig->render('message.twig', [
                'message' => 'Failed to write to file.'
            ]);
        }
    }

    public static function handleGet() {
        self::init();
        
        $uuid = Uuid::uuid4();
        
        echo self::$twig->render('index.twig', [
            'uuid' => $uuid,
            'maxLength' => self::$maxLength,
        ]);   
    }
}
