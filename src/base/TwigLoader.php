<?php

namespace madebyraygun\pssst\base;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
require '../vendor/autoload.php';

class TwigLoader {
    private static $twig;

    public static function init() {
        $loader = new FilesystemLoader(BASE_PATH . '/src/templates');
        $twig = new Environment($loader);
        $twig->addGlobal('cfTsActive', CF_TURNSTILE_ACTIVE);
        $twig->addGlobal('cfTsSiteKey', CF_TURNSTILE_SITEKEY);
        self::$twig = $twig;
    }

    public static function getTwig() {
        self::init();
        return self::$twig;
    }
}
