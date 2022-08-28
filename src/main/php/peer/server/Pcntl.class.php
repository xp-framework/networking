<?php namespace peer\server;

use lang\IllegalAccessException;

trait Pcntl {

  /** Verify PCNTL extension is loaded and useable */
  private static function extension() {
    if (!extension_loaded('pcntl')) {
      throw new IllegalAccessException('PCNTL extension not loaded');
    }

    // https://stackoverflow.com/questions/16262854/pcntl-not-working-on-ubuntu-for-security-reasons
    $disabled= ini_get('disable_functions');
    if (strstr($disabled, 'pcntl_fork')) {
      throw new IllegalAccessException('PCNTL functions disabled via PHP configuration (disable_functions='.$disabled.')');
    }
  }
}