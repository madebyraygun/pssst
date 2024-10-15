<?php

namespace madebyraygun\pssst\controllers;

require '../vendor/autoload.php';

class Message {
    public static function handleMessage() {
        $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/src/templates');
        $twig = new \Twig\Environment($loader);
        echo $twig->render('message.twig', [
            'message' => $_SESSION['message']
        ]);
        unset($_SESSION['message']);
    }
}
