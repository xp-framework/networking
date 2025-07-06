<?php namespace peer\unittest\server;

use peer\server\ServerProtocol;
use util\cmd\Console;

/**
 * TestingProtocol implements a simple line-based protocol with the 
 * following commands:
 * 
 * - CLNT: Sends client ID terminated by a "\n" separator.
 * - SEND: Sends 64 kB data terminated by a "\n" separator.
 * - HALT: Sends "+HALT" to the client and then shuts down the 
 *   server immediately.
 * 
 * Status reporting is performed on STDERR
 */
class TestingProtocol implements ServerProtocol {
  public $server;

  /**
   * Initialize the protocol
   *
   * @param  ?peer.server.Server $server
   */
  public function initialize($server= null) {
    $this->server= $server;
  }

  /**
   * Handle disconnect
   *
   * @param   peer.Socket socket
   */
  public function handleDisconnect($socket) { 
    Console::$err->writeLine('DISCONNECT ', $socket->hashCode());
  }

  /**
   * Handle error
   *
   * @param   peer.Socket socket
   * @param   lang.XPException e
   */
  public function handleError($socket, $e) { 
    Console::$err->writeLine('ERROR ', $socket->hashCode());
  }

  /**
   * Handle disconnect
   *
   * @param   peer.Socket socket
   */
  public function handleConnect($socket) { 
    Console::$err->writeLine('CONNECT ', $socket->hashCode());
  }

  /**
   * Handle data
   *
   * @param   peer.Socket socket
   */
  public function handleData($socket) {
    $cmd= $socket->readLine();
    switch (substr($cmd, 0, 4)) {
      case 'CLNT': {
        $socket->write($socket->hashCode()."\n");
        break;
      }

      case 'SEND': {
        $socket->write(str_repeat('*', 0xFFFF)."\n"); 
        break;
      }

      case 'SYNC': {
        for ($i= 0, $s= (int)substr($cmd, 5); $i < $s; $i++) {
          $socket->write(($i + 1)."\n");
        }
        $socket->write(".\n");
        break;
      }

      case 'ASNC': {
        for ($i= 0, $s= (int)substr($cmd, 5); $i < $s; $i++) {
          yield 'write' => $socket;
          $socket->write(($i + 1)."\n");
        }
        $socket->write(".\n");
        break;
      }

      case 'HALT': {
        $socket->write("+HALT\n"); 
        $this->server->shutdown();
        break;
      }
    }
  }    
}