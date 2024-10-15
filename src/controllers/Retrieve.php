<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

use madebyraygun\pssst\services\Challenge;
use madebyraygun\pssst\base\TwigLoader;
use JiriPudil\OTP\Account\SimpleAccountDescriptor;
use JiriPudil\OTP\OTP;
use JiriPudil\OTP\TimeBasedOTP;
use JiriPudil\OTP\Secret;

class Retrieve {
    private static $csrfToken;
    private static $otpType;
    private static $otp;
    private static $secret;
    private static $account;
    private static $authenticated = false;
    private static $twig;
    

    public static function init() {
        self::$csrfToken = $_SESSION['csrf_token'];
        self::$otp = new OTP('madebyraygun/pssst', new TimeBasedOTP());
        self::$secret = Secret::fromBase32($_ENV['TOTP_SECRET']);
        self::$account = new SimpleAccountDescriptor($_ENV['APP_ADMINISTRATOR_EMAIL'], self::$secret);
        self::$twig = TwigLoader::getTwig();
    }

    public static function handleGet($token) {
        self::init();
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($token)) {
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
            echo self::$twig->render('totpRequest.twig', [
                'totp' => [
                    'csrfToken' => self::$csrfToken,
                    'label' => 'Submit',
                    'cfTsSiteKey' => CF_TURNSTILE_SITEKEY,
                    'cfTsActive' => CF_TURNSTILE_ACTIVE,
                ]
            ]);   
        } else {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid token.'
            ]);
            exit;
        }
    } 

    public static function handlePost($token) {
        self::init();
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid CSRF token.'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp']) && isset($token)) {
            $delete = isset($_POST['delete']);
        
            if (CF_TURNSTILE_ACTIVE && !Challenge::verify($_POST['cf-turnstile-response']))
            {
                echo self::$twig->render('message.twig', [
                    'message' => 'Unable to verify the challenge.'
                ]);
                exit;
            }

            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $token;
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'message' => 'File not found.'
                ]);
                exit;
            } 

            $totp = htmlspecialchars(trim($_POST['totp']));
            $authenticated = self::$otp->verify(self::$account, $totp);
            if (!$authenticated) {
                echo self::$twig->render('totpRequest.twig', [
                    'message'   => 'Invalid OTP code',
                    'totp' => [
                        'csrfToken' => self::$csrfToken,
                        'label' => 'Submit',
                        'cfTsSiteKey' => CF_TURNSTILE_SITEKEY,
                        'cfTsActive' => CF_TURNSTILE_ACTIVE,
                     ]
                ]);   
                exit;
            }
            if ( $delete ) {
                unlink($filePath);
                echo self::$twig->render('deleted.twig');
            } else {        
                echo self::$twig->render('displaySecret.twig', [
                    'token' => $token,
                    'fileContents' => file_get_contents($filePath),
                    'totp' => [
                        'csrfToken' => self::$csrfToken,
                        'label' => 'Delete',
                        'cfTsSiteKey' => CF_TURNSTILE_SITEKEY,
                        'cfTsActive' => CF_TURNSTILE_ACTIVE,
                     ]
                ]);   
            }
        } else {
            echo self::$twig->render('message.twig', [
                'message' => 'Could not verify your submission.'
            ]);
        }
    }
}

