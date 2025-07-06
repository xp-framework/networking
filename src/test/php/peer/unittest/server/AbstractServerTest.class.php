<?php namespace peer\unittest\server;

use lang\Process;
use peer\{Socket, SocketEndpoint};
use test\After;

abstract class AbstractServerTest {
  protected $server, $endpoint;
  protected $sockets= [];

  /** Creates a new instance with a running server and connection endpoint */
  public function __construct(Process $server, SocketEndpoint $endpoint) {
    $this->server= $server;
    $this->endpoint= $endpoint;
  }

  /** @return peer.Socket */
  protected function newSocket() {
    $s= new Socket($this->endpoint->getHost(), $this->endpoint->getPort());
    $this->sockets[]= $s;
    return $s;
  }

  /** Connects to a socket and returns the client */
  protected function connectTo(Socket $socket): string {
    $socket->connect();
    $socket->write("CLNT\n");
    return $socket->readLine();
  }

  #[After]
  public function closeSockets() {
    foreach ($this->sockets as $socket) {
      $socket->isConnected() && $socket->close();
    }
  }

  #[After]
  public function shutdownServer() {
    $this->server->terminate(2);
  }
}