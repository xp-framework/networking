<?php namespace peer\unittest\server;

use peer\server\ForkedServer;
use peer\unittest\StartServer;
use test\verify\Runtime;
use test\{Assert, Test};

#[Runtime(extensions: ['pcntl']), StartServer(protocol: TestingProtocol::class, implementation: ForkedServer::class)]
class ForkedServerTest extends AbstractServerTest {

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