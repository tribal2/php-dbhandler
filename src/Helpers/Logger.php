<?php

namespace Tribal2\DbHandler\Helpers;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class Logger extends AbstractLogger implements LoggerInterface {


  public function log(
    mixed $level,
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
