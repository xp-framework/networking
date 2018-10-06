<?php namespace peer\server;

use lang\IllegalAccessException;

trait Pcntl {

  static function __static() {
    if (!extension_loaded('pcntl')) {
      throw new IllegalAccessException('PCNTL extension not available');
    }

    // https://stackoverflow.com/questions/16262854/pcntl-not-working-on-ubuntu-for-security-reasons
    $disabled= ini_get('disable_functions');
    if (strstr($disabled, 'pcntl_fork')) {
      throw new IllegalAccessException('PCNTL functions disabled via PHP configuration (disable_functions='.$disabled.')');
    }
  }
}