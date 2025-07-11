<?php namespace peer\server\protocol;

/**
 * Server Protocol: Accept sockets handler
 *
 * @test  peer.unittest.server.AcceptingServerTest
 */
interface SocketAcceptHandler {

  /**
   * Handle accepted socket. Return FALSE to make server drop connection
   * immediately, TRUE to continue on to handleConnect().
   *
   * @param   peer.Socket socket
   * @return  bool
   */
  public function handleAccept($socket);
}