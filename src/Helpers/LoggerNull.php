<?php

namespace Tribal2\DbHandler\Helpers;

use Psr\Log\LoggerInterface;
use Tribal2\DbHandler\Abstracts\LoggerAbstract;

class LoggerNull extends LoggerAbstract implements LoggerInterface {


  public function log(
    $level,
    string|\Stringable $message,
    array $context = array()
  ): void {
    // do nothing
  }


}
