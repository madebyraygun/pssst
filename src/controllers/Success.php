<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';
use Mailgun\Mailgun;

class Success {

    private static $loader;
    private static $twig;

    public static function init() {; 
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        self::$twig = new \Twig\Environment($loader);
    }
    public static function handleSuccess() {
        self::init();
        if ($_SESSION['token']) {
            // Get the query parameter and sanitize it
            $token = htmlspecialchars(trim($_SESSION['token']));
            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $token;
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'mesasge' => 'File not found.'
                ]);
            }

            $mailgunApiKey = $_ENV['MAILGUN_API_KEY'];
            $mailgunDomain = $_ENV['MAILGUN_DOMAIN'];
            $mailgunRecipient = $_ENV['MAILGUN_RECIPIENT'];
            $mailgunFromAddress = $_ENV['MAILGUN_FROM_ADDRESS'];

            //Send the email
            if (APP_ENV !== 'dev') {
                $mg = Mailgun::create($mailgunApiKey);
                $mg->messages()->send($mailgunDomain, [
                    'from'    => $mailgunFromAddress,
                    'to'      => $mailgunRecipient,
                    'subject' => 'New message via Raygun secure form',
                    'text'    => 'Click here to retrieve your message: https://secure.rygn.io/retrieve/' . $token
                ]);
            }
        } else {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Could not verify your submission.'
            ]);
        }
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        $twig = new \Twig\Environment($loader);
        echo $twig->render('success.twig');   
    }
}
    