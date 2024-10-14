<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';

use JiriPudil\OTP\Account\SimpleAccountDescriptor;
use JiriPudil\OTP\OTP;
use JiriPudil\OTP\TimeBasedOTP;
use JiriPudil\OTP\Secret;

class Retrieve {

    private static $tsSiteKey;
    private static $tsSecretKey;
    private static $otpType;
    private static $otp;
    private static $secret;
    private static $account;
    private static $authenticated;

    public static function init() {
        self::$tsSiteKey = $_ENV['TURNSTILE_SITEKEY'];
        self::$tsSecretKey = $_ENV['TURNSTILE_SECRET'];
        self::$otpType = new TimeBasedOTP();
        self::$otp = new OTP('rgsecure', self::$otpType);
        self::$secret = Secret::fromBase32($_ENV['OTP_SECRET']);
        self::$account = new SimpleAccountDescriptor('dev@madebyraygun.com', self::$secret);
        self::$authenticated = false;
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
                $_SESSION['message'] = 'Invalid token';
                header("Location: /message");
                exit;
            } else {
                // Verify the data file exists
                $filePath = BASE_PATH . '/data/.' . $token;
                if (!file_exists($filePath)) {
                    $_SESSION['message'] = 'File not found';
                    header("Location: /message");
                    exit;
                }
            }
            self::otpRequestTemplate();
        } else {
             $_SESSION['message'] = 'Invalid token';
                header("Location: /message");
                exit;
        }
    } 

    public static function handlePost($token) {
        self::init();
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['message'] = 'Invalid CSRF token';
                header("Location: /message");
                exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code']) && isset($token)) {
            $cfResponse = $_POST['cf-turnstile-response'];
            $ip = $_SERVER['REMOTE_ADDR'];

            $delete = isset($_POST['delete']);

            // Prepare data for the API call
            $formData = [
                'secret' => self::$tsSecretKey,
                'response' => $cfResponse,
                'remoteip' => $ip
            ];

            // Make a POST request to the Cloudflare Turnstile API
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($formData),
                ],
            ];
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            // Decode the JSON response
            $outcome = json_decode($result, true);
            if (!$outcome['success']) {
            $_SESSION['message'] = 'Unable to verify reCAPTCHA';
                header("Location: /message");
                exit;
            }

            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $token;
            if (!file_exists($filePath)) {
                $_SESSION['message'] = 'File not found';
                header("Location: /message");
                exit;
            } 

            $totp = htmlspecialchars(trim($_POST['totp']));
            $authenticated = self::$otp->verify(self::$account, $totp);
            if (!$authenticated) {
                self::otpRequestTemplate($message='Invalid code');
                exit;
            }
            if ( $delete ) {
                unlink($filePath);
                self::deletedSecret();
            } else {        
                self::displaySecret($filePath, $token);
            }
        } else {
            //redirect to message
            $_SESSION['message'] = 'Could not verify your submission.';
            header("Location: /message");
        }
    }

    private static function otpRequestTemplate($message = null) {
        require_once '../src/templates/header.php';
        if (isset($message)) {
            echo '<p>' . $message . '</p>';
        }
        echo '<form action="" method="post">
            <label for="totp">Enter your OTP code to view:</label>
            <input type="text" required id="totp" name="totp" autocomplete="one-time-code" maxlength="6">
            <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
            <div class="cf-turnstile" data-sitekey="' . self::$tsSiteKey . '" data-theme="light"></div>
            <input type="submit" value="Submit">
        </form>';
        require_once '../src/templates/footer.php';

    }
    private static function displaySecret($filePath, $token) {
        require_once '../src/templates/header.php';
        echo '<p>Secret contents:</p>
        <pre>' . file_get_contents($filePath) . '</pre>
        
        <form action="" method="post">
            <label for="totp">Enter your OTP code to delete:</label>
            <input type="text" required id="totp" name="totp" autocomplete="one-time-code" maxlength="6">
            <input type="hidden" name="token" value="' . $token . '">
            <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
            <input type="hidden" name="delete" value="true">
            <div class="cf-turnstile" data-sitekey="' . self::$tsSiteKey . '" data-theme="light"></div>
            <input type="submit" value="Delete">
        </form>';
        require_once '../src/templates/footer.php';
    }
    private static function deletedSecret() {
        require_once '../src/templates/header.php';
        echo '<p>Secret Deleted</p>';
        echo '<p><a role="button" href="/">Go back</a></p>';
        require_once '../src/templates/footer.php';
    }
}

