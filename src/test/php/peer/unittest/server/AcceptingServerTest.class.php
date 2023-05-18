<?php namespace peer\unittest\server;

use test\{Assert, Before, Test};

class AcceptingServerTest extends AbstractServerTest {
  
  #[Before]
  public function startServer() {
    $this->startServerWith('peer.unittest.server.AcceptTestingProtocol', 'peer.server.Server');
  }

  #[Test]
  public function connected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);

    Assert::equals('ACCEPT '.$client, self::$serverProcess->err->readLine());
    Assert::equals('CONNECT '.$client, self::$serverProcess->err->readLine());
  }

  #[Test]
  public function disconnected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);
    $socket->close();

    Assert::equals('ACCEPT '.$client, self::$serverProcess->err->readLine());
    Assert::equals('CONNECT '.$client, self::$serverProcess->err->readLine());
    Assert::equals('DISCONNECT '.$client, self::$serverProcess->err->readLine());
  }
}