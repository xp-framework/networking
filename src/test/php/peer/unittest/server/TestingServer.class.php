<?php namespace peer\unittest\server;

use lang\Throwable;
use lang\XPClass;
use peer\BSDSocket;
use peer\server\Server;
use peer\server\ServerProtocol;
use util\cmd\Console;

/**
 * Socket server used by ServerTest. Process interaction is performed by messages
 * this server prints to standard output.
 *
 * - Server listens on a free port @ 127.0.0.1
 * - On startup success, "+ Service (IP):(PORT)" is written
 * - On shutdown, "+ Done" is written
 * - On errors during any phase, "- " and the exception message are written
 *
 * @see   xp://peer.unittest.server.AbstractServerTest
 */
class TestingServer {

  /**
   * Start server
   *
   * @param  string[] args
   * @return int
   */
  public static function main(array $args) {
    $s= new Server('127.0.0.1', 0);
    try {
      $s->setProtocol(XPClass::forName($args[0])->newInstance());
      $s->init();

      // Integrate with `xp -supervise`
      if ($port= getenv('XP_SIGNAL')) {
        $signal= new BSDSocket('127.0.0.1', $port);
        $signal->connect();
        $s->listen($signal, function($socket, &$connections) use($s) {
          $s->terminate= true;
        });
      }

      Console::writeLinef('+ Service %s:%d', $s->socket->host, $s->socket->port);
      $s->service();
      Console::writeLine('+ Done');
      return 0;
    } catch (Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
      return 1;
    } finally {
      $s->shutdown();
    }
  }
}