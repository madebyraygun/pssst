<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';

use madebyraygun\secureform\services\Challenge;
use JiriPudil\OTP\Account\SimpleAccountDescriptor;
use JiriPudil\OTP\OTP;
use JiriPudil\OTP\TimeBasedOTP;
use JiriPudil\OTP\Secret;

class Retrieve {

    private static $tsSiteKey;
    private static $csrfToken;
    private static $otpType;
    private static $otp;
    private static $secret;
    private static $account;
    private static $authenticated;
    private static $loader;
    private static $twig;
    

    public static function init() {
        self::$tsSiteKey = $_ENV['TURNSTILE_SITEKEY'];
        self::$csrfToken = $_SESSION['csrf_token'];
        self::$otpType = new TimeBasedOTP();
        self::$otp = new OTP('rgsecure', self::$otpType);
        self::$secret = Secret::fromBase32($_ENV['OTP_SECRET']);
        self::$account = new SimpleAccountDescriptor('dev@madebyraygun.com', self::$secret);
        self::$authenticated = false;
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        self::$twig = new \Twig\Environment($loader);
    }

    /* Add this function to the output template to generate an authenticator URL. */
    // public static function generateOTP() {
    //     self::init();
    //     $uri = self::$otp->getProvisioningUri(self::$account, digits: 6);
    //     echo '<a href="'.$uri. '">Click here to add to your authenticator</a>';
    // }

    public static function handleGet($token) {
        self::init();
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($token)) {
            $token = htmlspecialchars(trim($token));
            if (!$token || !preg_match('/^[a-f0-9]{32}$/', $token)) {
                echo self::$twig->render('message.twig', [
                    'mesasge' => 'Invalid token.'
                ]);
                exit;
            } else {
                // Verify the data file exists
                $filePath = BASE_PATH . '/data/.' . $token;
                if (!file_exists($filePath)) {
                    echo self::$twig->render('message.twig', [
                        'mesasge' => 'File not found.'
                    ]);
                    exit;
                }
            }
            echo self::$twig->render('totpRequest.twig', [
                'totp' => [
                    'csrfToken' => self::$csrfToken,
                    'label' => 'Submit',
                    'siteKey' => self::$tsSiteKey
                ]
            ]);   
        } else {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Invalid token.'
            ]);
            exit;
        }
    } 

    public static function handlePost($token) {
        self::init();
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Invalid CSRF token.'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp']) && isset($token)) {
            $delete = isset($_POST['delete']);
            $cfTurnstileResponse = $_POST['cf-turnstile-response'];
            if (!Challenge::verify($cfTurnstileResponse) )
            {
                echo self::$twig->render('message.twig', [
                    'mesasge' => 'Unable to verify the challenge.'
                ]);
                exit;
            }

            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $token;
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'mesasge' => 'File not found.'
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
                        'siteKey' => self::$tsSiteKey
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
                        'siteKey' => self::$tsSiteKey
                     ]
                ]);   
            }
        } else {
            echo self::$twig->render('message.twig', [
                'mesasge' => 'Could not verify your submission.'
            ]);
        }
    }
}

