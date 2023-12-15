<?php

use Tribal2\DbHandler\Helpers\Logger;

describe('Logger', function () {

  test('Logger log method outputs the expected format', function () {
    $logger = new Logger();
    $level = 'info';
    $message = 'Test message';
    $context = ['key' => 'value'];

    ob_start();
    $logger->log($level, $message, $context);
    $output = ob_get_clean();

    $expectedOutput = strtoupper($level) . ' - ' . $message . ': ' . json_encode($context) . '\n';
    expect($output)->toBe($expectedOutput);
  });

});
