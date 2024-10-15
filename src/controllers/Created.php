<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';
use Mailgun\Mailgun;

class Created {

    private static $administrator;
    private static $loader;
    private static $retrieveUrl;
    private static $twig;

    public static function init() {
        self::$administrator = APP_ADMINISTRATOR_NAME; 
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        self::$twig = new \Twig\Environment($loader);
    }
    public static function handleCreated($token) {
        self::init();
        $token = htmlspecialchars(trim($token));
        if (!$token || !preg_match('/^[a-f0-9]{32}$/', $token)) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid token.'
            ]);
            exit;
        } else {
            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $token;
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'message' => 'File not found.'
                ]);
                exit;
            }
        }

        self::$retrieveUrl = APP_BASE_URL . '/retrieve/' . $token;

        //Send the email
        if (APP_ENV !== 'dev' || MAILGUN_ACTIVE) {
            $mailgunApiKey = $_ENV['MAILGUN_API_KEY'];
            $mailgunDomain = $_ENV['MAILGUN_DOMAIN'];
            $mailgunRecipient = $_ENV['APP_ADMINISTRATOR_EMAIL'];
            $mailgunFromAddress = $_ENV['MAILGUN_FROM_ADDRESS'];

            $mg = Mailgun::create($mailgunApiKey);
            $mg->messages()->send($mailgunDomain, [
                'from'    => $mailgunFromAddress,
                'to'      => $mailgunRecipient,
                'subject' => 'New message via ' . self::$administrator . ' secure form',
                'text'    => 'Click here to retrieve your message: ' . self::$retrieveUrl
            ]);
        }
        echo self::$twig->render('success.twig', [
            'mailgunActive' => MAILGUN_ACTIVE,
            'retrieveUrl' => self::$retrieveUrl
        ]);
        exit;
    }
}
    