<?php namespace peer\unittest\server;

use peer\server\protocol\SocketAcceptHandler;
use util\cmd\Console;

class AcceptTestingProtocol extends TestingProtocol implements SocketAcceptHandler {

  /**
   * Handle accept
   *
   * @param   peer.Socket socket
   * @return  bool
   */
  public function handleAccept($socket) { 
    Console::$err->writeLine('ACCEPT ', $this->hashOf($socket));
    return true;
  }
}