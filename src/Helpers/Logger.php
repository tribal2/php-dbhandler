<?php

namespace Tribal2\DbHandler\Helpers;

use Tribal2\DbHandler\Interfaces\LoggerInterface;

class Logger implements LoggerInterface {


  public static function log($data = NULL, $title = '', $level = 'debug'): void {
      echo strtoupper($level)
        . ' - '
        . $title
        . ': '
        . json_encode($data)
        . '\n';
  }


}
