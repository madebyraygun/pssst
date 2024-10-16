<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

use madebyraygun\pssst\base\TwigLoader;
use madebyraygun\pssst\helpers\Uuid;
use madebyraygun\pssst\services\Challenge;
use JiriPudil\OTP\Account\SimpleAccountDescriptor;
use JiriPudil\OTP\OTP;
use JiriPudil\OTP\TimeBasedOTP;
use JiriPudil\OTP\Secret;

class Retrieve {
    private static $sessionCsrfToken;
    private static $otpType;
    private static $otp;
    private static $secret;
    private static $account;
    private static $authenticated = false;
    private static $twig;
    

    public static function init() {
        self::$sessionCsrfToken = $_SESSION['csrf_token'];
        self::$otp = new OTP('madebyraygun/pssst', new TimeBasedOTP());
        self::$secret = Secret::fromBase32($_ENV['APP_TOTP_SECRET']);
        self::$account = new SimpleAccountDescriptor($_ENV['APP_ADMINISTRATOR_EMAIL'], self::$secret);
        self::$twig = TwigLoader::getTwig();
    }

    public static function handleGet($uuid, $key) {
        self::init();
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($uuid)) {
            $uuid = htmlspecialchars(trim($uuid));
            $key = htmlspecialchars(trim($key));
            $key = preg_match('/^[a-f0-9]{16}$/', $key)  == 1 ? $key : null;
            if (!Uuid::validate($uuid) || !$key) {
                echo self::$twig->render('message.twig', [
                    'message' => 'Invalid ID or key.'
                ]);
                exit;
            } else {
                // Verify the data file exists
                $filePath = BASE_PATH . '/data/' . $uuid . '.json';
                if (!file_exists($filePath)) {
                    echo self::$twig->render('message.twig', [
                        'message' => 'Secret not found.'
                    ]);
                    exit;
                }
            }
            echo self::$twig->render('totpRequest.twig', [
                'totp' => [
                    'label' => 'View Secret',
                ]
            ]);   
        } else {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid ID.'
            ]);
            exit;
        }
    } 

    public static function handlePost($uuid, $key) {
        self::init();
        if (!hash_equals(self::$sessionCsrfToken, $_POST['csrf_token'])) {
            echo self::$twig->render('message.twig', [
                'message' => 'Invalid CSRF token.'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($uuid)) {
            $delete = isset($_POST['delete']);
        
            if (CF_TURNSTILE_ACTIVE && !Challenge::verify($_POST['cf-turnstile-response']))
            {
                echo self::$twig->render('message.twig', [
                    'message' => 'Unable to verify the challenge.'
                ]);
                exit;
            }

            // Verify the data file exists
            $filePath = BASE_PATH . '/data/' . $uuid . '.json';
            if (!file_exists($filePath)) {
                echo self::$twig->render('message.twig', [
                    'message' => 'Secret not found.'
                ]);
                exit;
            } 

            if (TOTP_ACTIVE) {
                if (!isset($_POST['totp'])) {
                    echo self::$twig->render('message.twig', [
                        'message' => 'Invalid TOTP code.'
                    ]);
                    exit;
                }
                $totp = htmlspecialchars(trim($_POST['totp']));
                $authenticated = self::$otp->verify(self::$account, $totp);
                if (!$authenticated) {
                    echo self::$twig->render('totpRequest.twig', [
                        'message'   => 'Invalid TOTP code',
                        'totp' => [
                            'label' => 'Submit',
                        ]
                    ]);   
                    exit;
                }
            }
            if ( $delete ) {
                unlink($filePath);
                echo self::$twig->render('message.twig', [
                    'message' => 'Secret deleted.'
                ]);
            } else {     
                $fileContents = file_get_contents($filePath);
                $data = json_decode($fileContents, true);
                if (!$data['viewed']) {
                    $data['viewed'] = true;
                    file_put_contents($filePath, json_encode($data));
                }
               
                $iv = hex2bin($data['iv']);
                $encryptedMessage = $encryptedMessage = $data['message'];
                $decryptedMessage = openssl_decrypt($encryptedMessage, 'aes-256-cbc', $key, 0, $iv);
                
                echo self::$twig->render('displaySecret.twig', [
                    'uuid' => $uuid,
                    'secretContents' => $decryptedMessage,
                    'totp' => [
                        'label' => 'Delete',
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

