<?php

namespace Tribal2;

class Logger implements LoggerInterface {


  public function log($data = NULL, $title = '', $level = 'debug'): void {
      echo strtoupper($level)
        . ' - '
        . $title
        . ': '
        . json_encode($data)
        . '<br>';
  }


}
