<?php

namespace madebyraygun\pssst\helpers;

require '../vendor/autoload.php';

class Uuid {
  
  public static function uuid4()
  {
      $data = random_bytes(16);
      $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  public static function validate($uuid) {
    return preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $uuid) === 1;
  }
}
