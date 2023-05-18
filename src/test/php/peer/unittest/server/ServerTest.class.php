<?php namespace peer\unittest\server;

use peer\unittest\StartServer;
use test\{Assert, Test};

#[StartServer(TestingServer::class, ['peer.unittest.server.TestingProtocol', 'peer.server.Server'])]
class ServerTest extends AbstractServerTest {
  
  #[Test]
  public function connected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);

    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
  }

  #[Test]
  public function disconnected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);
    $socket->close();

    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
    Assert::equals('DISCONNECT '.$client, $this->server->err->readLine());
  }
}