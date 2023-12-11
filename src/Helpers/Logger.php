<?php

namespace Tribal2\DbHandler\Helpers;

use Psr\Log\LoggerInterface;
use Tribal2\DbHandler\Abstracts\LoggerAbstract;

class Logger extends LoggerAbstract implements LoggerInterface {


  public function log(
    $level,
    string|\Stringable $message,
    array $context = array()
  ): void {
    echo strtoupper($level)
      . ' - '
      . $message
      . ': '
      . json_encode($context)
      . '\n';
  }


}
