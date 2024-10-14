<?php

namespace madebyraygun\secureform\controllers;

require '../vendor/autoload.php';

class Message {
    public static function handleMessage() {
        require_once '../src/templates/header.php';
        if (isset($_SESSION['message'])) {
            echo '<p>' . htmlspecialchars($_SESSION['message']) . '</p>';
            unset($_SESSION['message']); // Clear the error after displaying
        } else {
            echo '<p>An unknown error occurred. Please try again.</p>';
        }
        echo '<p><a role="button" href="/">Go back</a></p>';
        require_once '../src/templates/footer.php';
    }
}
