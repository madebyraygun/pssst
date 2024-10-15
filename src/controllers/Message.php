<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

use madebyraygun\pssst\base\TwigLoader;

class Message {
    public static function handleMessage() {
        $twig = TwigLoader::getTwig();
        echo $twig->render('message.twig', [
            'message' => $_SESSION['message']
        ]);
        unset($_SESSION['message']);
    }
}
