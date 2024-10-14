<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';

class Create {
    private static $basePath;
    private static $maxLength;
    private static $tsSiteKey;
    private static $tsSecretKey;

    public static function init() {
        self::$basePath = BASE_PATH;
        self::$maxLength = 10000;
        self::$tsSiteKey = $_ENV['TURNSTILE_SITEKEY'];
        self::$tsSecretKey = $_ENV['TURNSTILE_SECRET'];        
    }

    public static function handlePost() {
        self::init();
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['message'] = 'Invalid CSRF token';
                header("Location: /message");
                exit;
        }
        if (isset($_POST['query']) && isset($_POST['token'])) {
            $cfResponse = $_POST['cf-turnstile-response'];
            $ip = $_SERVER['REMOTE_ADDR'];

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

            // Validate the token
            $token = $_POST['token'];
            if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
                $_SESSION['message'] = 'Invalid token';
                header("Location: /message");
                exit;
            }

            // Sanitize the input
            $query = trim($_POST['query']); // Trim whitespace
            $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); // Convert special characters to HTML entities
            $query = substr($query, 0, self::$maxLength); 

            // Define the file path
            $filePath = self::$basePath . '/data/.' . $token;

            // Write the contents to the file
            if (file_put_contents($filePath, $query) !== false) {
                $_SESSION['token'] = $token;
                header("Location: success");
                exit;
            } else {
                // Store error message in session and redirect to error page
                $_SESSION['message'] = 'Failed to write to file';
                header("Location: /message");
                exit;
            }
        }
    }

    public static function handleGet() {
        self::init();
        $token = bin2hex(random_bytes(16));
        $_SESSION['token'] = $token;
        require_once '../src/templates/header.php';
        echo '<p>Use this secure form to send passwords and other sensitive information to the Raygun team. Any information shared via this form will be stored securely and deleted within 24 hours.</p>
        <form action="/" method="post">
            <label for="query">Message</label>
            <textarea id="query" name="query" rows="4" cols="50" maxlength="' . self::$maxLength . '"></textarea>
            <small id="counter">0/' . self::$maxLength . '</small>
            <script>
                const query = document.getElementById("query");
                const counter = document.getElementById("counter");
                query.addEventListener("input", () => {
                    counter.textContent = `${query.value.length}/' . self::$maxLength . '`;
                });
            </script>
            <input type="hidden" name="token" value="' . $token . '">
            <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
            <div class="cf-turnstile" data-sitekey="' . self::$tsSiteKey . '" data-theme="light"></div>
            <br>
            <input type="submit" value="Submit">
        </form>';
        require_once '../src/templates/footer.php';
    }
}
