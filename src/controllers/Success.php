<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';
use Mailgun\Mailgun;

class Success {
    public static function handleSuccess() {
        if ($_SESSION['token']) {
            // Get the query parameter and sanitize it
            $token = htmlspecialchars(trim($_SESSION['token']));
            // Verify the data file exists
            $filePath = BASE_PATH . '/data/.' . $token;
            if (!file_exists($filePath)) {
                $_SESSION['message'] = 'File not found';
                header("Location: /message");
                exit;
            }

            $mailgunApiKey = $_ENV['MAILGUN_API_KEY'];
            $mailgunDomain = $_ENV['MAILGUN_DOMAIN'];
            $mailgunRecipient = $_ENV['MAILGUN_RECIPIENT'];
            $mailgunFromAddress = $_ENV['MAILGUN_FROM_ADDRESS'];

            //Send the email
            $mg = Mailgun::create($mailgunApiKey);
            $mg->messages()->send($mailgunDomain, [
                'from'    => $mailgunFromAddress,
                'to'      => $mailgunRecipient,
                'subject' => 'New message via Raygun secure form',
                'text'    => 'Click here to retrieve your message: https://secure.rygn.io/retrieve/' . $token
            ]);
        } else {
            //redirect to message
            $_SESSION['message'] = 'Could not verify your submission.';
            header("Location: /message");
        }
        require_once '../src/templates/header.php';
        echo '<p>Success, your secret was saved and will be sent to the Raygun team.</p>
        <p><a role="button" href="/">Go back</a></p>';
        require_once '../src/templates/footer.php';
    }
}
    