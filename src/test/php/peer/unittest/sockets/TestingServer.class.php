<?php namespace peer\unittest\sockets;

use lang\XPClass;
use peer\ServerSocket;
use peer\server\{Server, ServerProtocol};
use util\cmd\Console;

/**
 * Socket server used by SocketTest. Implements a simple line-based
 * protocol with the following commands:
 * <ul>
 *   <li>
 *     ECHO [DATA]: Echoes data following the command, terminating
 *     it by a "\n" separator.
 *   </li>
 *   <li>
 *     LINE [N] [S]: Prints N lines with separator(s) S (urlencoded)
 *     followed by a "LINE ." with "\n" separator. For example, the 
 *     command "LINE 5 %0A" prints five lines with "\n" (and the last
 *     line).
 *   </li>
 *   <li>
 *     CLOS: Closes communications socket without sending any prior
 *     notice. Can be used to simulate behaviour when connection is
 *     closed by foreign host.
 *   </li>
 *   <li>
 *     HALT: Sends "+HALT" to the client and then shuts down the 
 *     server immediately.
 *   </li>
 * </ul>
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
 * @see   peer.unittest.sockets.SocketTest
 */
class TestingServer {

  /**
   * Start server
   *
   * @param   string[] args
   */
  public static function main(array $args) {
    $protocol= newinstance(ServerProtocol::class, [], '{
      public function initialize() { }
      public function handleDisconnect($socket) { }
      public function handleError($socket, $e) { }
      public function handleConnect($socket) { }
      
      public function handleData($socket) {
        $cmd= $socket->readLine();
        switch (substr($cmd, 0, 4)) {
          case "ECHO": {
            $socket->write("+ECHO ".substr($cmd, 5)."\n"); 
            break;
          }
          case "LINE": {
            sscanf(substr($cmd, 5), "%d %s", $l, $sep);
            for ($i= 0, $sbytes= urldecode($sep); $i < $l; $i++) {
              $socket->write("+LINE ".$i.$sbytes); 
            }
            $socket->write("+LINE .\n");
            break;
          }
          case "CLOS": {
            $socket->close(); 
            break;
          }
          case "HALT": {
            $socket->write("+HALT\n"); 
            $this->server->terminate= TRUE; 
            break;
          }
        }
      }
    }');
    
    $s= new Server();
    try {
      $s->listen(new ServerSocket('127.0.0.1', 0), $protocol);
      $s->init();
      Console::writeLinef('+ Service %s:%d', $s->socket->host, $s->socket->port);
      $s->service();
      Console::writeLine('+ Done');
    } catch (\lang\Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}