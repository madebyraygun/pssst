<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

use madebyraygun\pssst\base\TwigLoader;
use madebyraygun\pssst\helpers\Uuid;
use Mailgun\Mailgun;

class Created {

    private static $administrator;
    private static $loader;
    private static $retrieveUrl;
    private static $twig;

    public static function init() {
        self::$administrator = APP_ADMINISTRATOR_NAME; 
        self::$twig = TwigLoader::getTwig();
    }
    public static function handleCreated($uuid) {
        self::init();
        $uuid = htmlspecialchars(trim($uuid));
        if (!Uuid::validate($uuid)) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid ID.'
            ]);
            exit;
        } else {
            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $uuid;
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'message' => 'Secret not found.'
                ]);
                exit;
            }
        }

        self::$retrieveUrl = APP_BASE_URL . '/retrieve/' . $uuid;

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
    