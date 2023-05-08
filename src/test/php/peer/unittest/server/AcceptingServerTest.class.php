<?php namespace peer\unittest\server;

use unittest\{BeforeClass, Ignore, Test};

class AcceptingServerTest extends AbstractServerTest {
  
  #[BeforeClass]
  public static function startServer() {
    parent::startServerWith('peer.unittest.server.AcceptTestingProtocol', 'peer.server.Server');
  }

  #[Test]
  public function connected() {
    $this->connect();
    $this->assertHandled(['ACCEPT', 'CONNECT']);
  }

  #[Test]
  public function disconnected() {
    $this->connect();
    $this->conn->close();
    $this->assertHandled(['ACCEPT', 'CONNECT', 'DISCONNECT']);
  }

  #[Test, Ignore('Fragile test, dependant on OS / platform and implementation vagaries')]
  public function error() {
    $this->connect();
    $this->conn->write("SEND\n");
    $this->conn->close();
    $this->assertHandled(['ACCEPT', 'CONNECT', 'ERROR']);
  }
}