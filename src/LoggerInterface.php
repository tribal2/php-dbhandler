<?php

namespace Tribal2;

interface LoggerInterface {

  const DEBUG = 'debug';
  const INFO = 'info';
  const NOTICE = 'notice';
  const WARNING = 'warning';
  const ERROR = 'error';
  const CRITICAL = 'critical';
  const ALERT = 'alert';
  const EMERGENCY = 'emergency';


  public static function log($data = NULL, $title = '', $level = 'debug'): void;


}
