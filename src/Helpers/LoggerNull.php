<?php

namespace Tribal2\DbHandler\Helpers;

use Tribal2\DbHandler\Interfaces\LoggerInterface;

class LoggerNull implements LoggerInterface {


  public static function log($data = NULL, $title = '', $level = 'debug'): void {}


}
