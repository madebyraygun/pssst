<?php 

namespace madebyraygun\pssst\web;

require '../vendor/autoload.php';

class Assets {
    public static function css() {
      header('Content-Type: text/css');
      $theme = $_ENV['APP_THEME'] ?? 'violet';
      $appStyles = file_get_contents(BASE_PATH . '/src/web/assets/app.css');
      $themeStyles = file_get_contents(VENDOR_PATH . '/picocss/pico/css/pico.classless.'.$theme.'.css');
      echo $themeStyles . $appStyles;
    }

    public static function js() {
      header('Content-Type: script/js');
      echo file_get_contents(BASE_PATH . '/src/web/assets/app.js');
    }

    public static function logoDark() {
      header('Content-Type: image/png');
      echo file_get_contents(BASE_PATH . '/src/web/assets/logo-dark.png');
    }

    public static function logoLight() {
      header('Content-Type: image/png');
      $theme = $_ENV['APP_THEME'] ?? 'violet';
      echo file_get_contents(BASE_PATH . '/src/web/assets/logo-light.png');
    }

    public static function logoLightUrl() {
      $lightLogoFileExists = file_exists(BASE_PATH . '/public/assets/logo-light.png');
      if ($lightLogoFileExists) {
        $assetUrl = APP_BASE_URL . '/assets/logo-light.png';
      } elseif (file_exists(BASE_PATH . '/public/assets/logo.png')) {
        $assetUrl = APP_BASE_URL . '/assets/logo.png';
      } else {
        $assetUrl = '/system/logo-light';
      }
      return $assetUrl;
    }

    public static function logoDarkUrl() {
      $darkLogoFileExists = file_exists(BASE_PATH . '/public/assets/logo-dark.png');
      if (!$darkLogoFileExists) {
        return '/system/logo-dark';
      }
      return APP_BASE_URL . '/assets/logo-dark.png';
    }
}
