<?php namespace peer\unittest\server;

use lang\{XPClass, Throwable};
use peer\ServerSocket;
use util\cmd\Console;

/**
 * Socket server used by ServerTest. Process interaction is performed by messages
 * this server prints to standard out:
 *
 * - Server listens on a free port @ 127.0.0.1
 * - On startup success, "+ Service (IP):(PORT)" is written
 * - On shutdown, "+ Done" is written
 * - On errors during any phase, "- " and the exception message are written
 *
 * @see  peer.unittest.server.AbstractServerTest
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
    } catch (Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}