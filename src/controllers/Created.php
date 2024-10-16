<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

use Mailgun\Mailgun;
use madebyraygun\pssst\base\TwigLoader;

class Created {

    private static $administrator;
    private static $loader;
    private static $retrieveUrl;
    private static $twig;

    public static function init() {
        self::$administrator = APP_ADMINISTRATOR_NAME; 
        self::$twig = TwigLoader::getTwig();
    }
    public static function handleCreated($uid) {
        self::init();
        $uid = htmlspecialchars(trim($uid));
        if (!$uid || !preg_match('/^[a-f0-9]{13}$/', $uid)) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid UID.'
            ]);
            exit;
        } else {
            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $uid;
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'message' => 'Secret not found.'
                ]);
                exit;
            }
        }

        self::$retrieveUrl = APP_BASE_URL . '/retrieve/' . $uid;

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
    