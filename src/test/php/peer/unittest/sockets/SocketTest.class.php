<?php namespace peer\unittest\sockets;

use peer\Socket;
use peer\unittest\StartServer;
use unittest\actions\VerifyThat;

/**
 * TestCase
 *
 * @see      xp://peer.Socket
 */
#[Action(eval: 'new StartServer(TestingServer::class, "connected", "shutdown")')]
class SocketTest extends AbstractSocketTest {

  /**
   * Creates a new client socket
   *
   * @param   string addr
   * @param   int port
   * @return  peer.Socket
   */
  protected function newSocket($addr, $port) {
    return new Socket($addr, $port);
  }
}