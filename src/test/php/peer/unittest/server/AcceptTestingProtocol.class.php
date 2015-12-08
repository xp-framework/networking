<?php namespace peer\unittest\server;

use peer\server\protocol\SocketAcceptHandler;


/**
 * AcceptTestingProtocol handles socket accepts
 *
 */
class AcceptTestingProtocol extends TestingProtocol implements SocketAcceptHandler {

  /**
   * Handle accept
   *
   * @param   peer.Socket socket
   * @return  bool
   */
  public function handleAccept($socket) { 
    \util\cmd\Console::$err->writeLine('ACCEPT ', $this->hashOf($socket));
    return true;
  }
}
