<?php

namespace madebyraygun\pssst\base;

use madebyraygun\pssst\web\Assets;
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
        $twig->addGlobal('logoLight', Assets::logoLightUrl());
        $twig->addGlobal('logoDark', Assets::logoDarkUrl());
        $twig->addGlobal('defaultColorScheme', $_ENV['DEFAULT_COLOR_SCHEME'] ?? 'dark');
        $twig->addGlobal('showGithubLink', $_ENV['SHOW_GITHUB_LINK'] == "true" ? true : false);
        $twig->addGlobal('csrfToken', $_SESSION['csrf_token']);
        $twig->addGlobal('appAdministrator', APP_ADMINISTRATOR_NAME);
        self::$twig = $twig;
    }

    public static function getTwig() {
        self::init();
        return self::$twig;
    }
}
