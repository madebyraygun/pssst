<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';

use JiriPudil\OTP\Account\SimpleAccountDescriptor;
use JiriPudil\OTP\OTP;
use JiriPudil\OTP\TimeBasedOTP;
use JiriPudil\OTP\Secret;

class GenerateTotp {
    private static $otp;
    private static $secret;
    private static $account;
    private static $loader;
    private static $twig;
    

    public static function init() {
        self::$otp = new OTP('madebyraygun/secure-form', new TimeBasedOTP());
        self::$secret = Secret::fromBase32($_ENV['TOTP_SECRET']);
        self::$account = new SimpleAccountDescriptor($_ENV['APP_ADMINISTRATOR_EMAIL'], self::$secret);
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        self::$twig = new \Twig\Environment($loader);
    }

    /*
     * Generates an authenticator URL based on TOTP secret and admin email
     * Only available in dev mode.
     */
    public static function generate() {
        self::init();
        if (APP_ENV !== 'dev') {
            echo self::$twig->render('message.twig', [
                'message' => 'This feature is only available in dev mode.'
            ]);
            exit;
        }

        $uri = self::$otp->getProvisioningUri(self::$account, digits: 6);
        echo self::$twig->render('message.twig', [
            'message' => '<a href="'.$uri. '">Click here to add to your authenticator</a>'
        ]);
        exit;
    }
}

