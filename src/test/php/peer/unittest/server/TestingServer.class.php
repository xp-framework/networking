<?php namespace peer\unittest\server;

use lang\XPClass;
use peer\ServerSocket;
use util\cmd\Console;

/**
 * Socket server used by ServerTest. 
 *
 * Process interaction is performed by messages this server prints to
 * standard out:
 * <ul>
 *   <li>Server listens on a free port @ 127.0.0.1</li>
 *   <li>On startup success, "+ Service (IP):(PORT)" is written</li>
 *   <li>On shutdown, "+ Done" is written</li>
 *   <li>On errors during any phase, "- " and the exception message are written</li>
 * </ul>
 *
 * @see   xp://peer.unittest.server.AbstractServerTest
 */
class TestingServer {

  /**
   * Start server
   *
   * @param   string[] args
   */
  public static function main(array $args) {
    $s= XPClass::forName($args[1] ?? 'peer.server.Server')->newInstance();
    $socket= new ServerSocket('127.0.0.1', 0);
    try {
      $s->listen($socket, XPClass::forName($args[0])->newInstance());
      Console::writeLinef('+ Service %s:%d', $socket->host, $socket->port);
      $s->service();
      Console::writeLine('+ Done');
    } catch (\lang\Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}