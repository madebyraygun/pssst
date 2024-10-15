<?php 

namespace madebyraygun\pssst\web;

require '../vendor/autoload.php';

class Assets {
    public static function css() {
      header('Content-Type: text/css');
      $theme = $_ENV['APP_THEME'] ?? 'violet';
      echo file_get_contents(VENDOR_PATH . '/picocss/pico/css/pico.classless.'.$theme.'.css');
    }
}
