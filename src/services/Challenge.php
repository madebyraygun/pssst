<?php 

namespace madebyraygun\secureform\services;

require '../vendor/autoload.php';

class Challenge {
  public static function verify($cfResponse) : bool {
    if ('APP_ENV' === 'dev') {
      return true;
    }
    
    if (!$cfResponse) {
      return false;
    }

    $tsSecretKey = $_ENV['TURNSTILE_SECRET'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // Prepare data for the API call
    $formData = [
        'secret' => $tsSecretKey,
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
    if (!$result) {
        return false;
    }
    $decoded = json_decode($result, true);
    if (!isset($decoded['success'])) return false;

    return true;
  }
}
