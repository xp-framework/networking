<?php namespace peer\unittest\server;

use peer\unittest\StartServer;
use test\{Assert, Test};

#[StartServer(TestingServer::class, ['peer.unittest.server.AcceptTestingProtocol', 'peer.server.Server'])]
class AcceptingServerTest extends AbstractServerTest {
  
  #[Test]
  public function connected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);

    Assert::equals('ACCEPT '.$client, $this->server->err->readLine());
    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
  }

  #[Test]
  public function disconnected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);
    $socket->close();

    Assert::equals('ACCEPT '.$client, $this->server->err->readLine());
    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
    Assert::equals('DISCONNECT '.$client, $this->server->err->readLine());
  }
}