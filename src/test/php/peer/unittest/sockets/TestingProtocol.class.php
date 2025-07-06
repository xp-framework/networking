<?php namespace peer\unittest\sockets;

use peer\server\ServerProtocol;

/**
 * Implements a simple line-based protocol with the following commands:
 *
 * - ECHO [DATA]: Echoes data following the command, terminating
 *   it by a "\n" separator.
 * - LINE [N] [S]: Prints N lines with separator(s) S (urlencoded)
 *   followed by a "LINE ." with "\n" separator. For example, the 
 *   command "LINE 5 %0A" prints five lines with "\n" (and the last
 *   line).
 * - CLOS: Closes communications socket without sending any prior
 *   notice. Can be used to simulate behaviour when connection is
 *   closed by foreign host.
 * - HALT: Sends "+HALT" to the client and then shuts down the 
 *   server immediately.
 *
 * Process interaction is performed by messages this server prints to STDOUT:
 *
 * - Server listens on a free port @ 127.0.0.1
 * - On startup success, "+ Service (IP):(PORT)" is written
 * - On shutdown, "+ Done" is written
 * - On errors during any phase, "- " and the exception message are written
 *
 * @see   peer.unittest.sockets.SocketTest
 */
class TestingProtocol implements ServerProtocol {

  public function initialize($server= null) { }

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

  public function handleDisconnect($socket) { }

  public function handleError($socket, $e) { }
}