<?php

namespace Tribal2\DbHandler\Abstracts;

use Psr\Log\LoggerInterface;

abstract class LoggerAbstract implements LoggerInterface {


  abstract public function log(
    $level,
    string|\Stringable $message,
    array $context = array()
  ): void;


  public function emergency(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('emergency', $message, $context);
  }


  public function alert(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('alert', $message, $context);
  }


  public function critical(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('critical', $message, $context);
  }


  public function error(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('error', $message, $context);
  }


  public function warning(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('warning', $message, $context);
  }


  public function notice(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('notice', $message, $context);
  }


  public function info(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('notice', $message, $context);
  }


  public function debug(
    string|\Stringable $message,
    array $context = array()
  ): void {
    $this->log('debug', $message, $context);
  }


}
