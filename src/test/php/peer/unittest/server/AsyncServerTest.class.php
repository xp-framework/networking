<?php namespace peer\unittest\server;

use unittest\{BeforeClass, Ignore, Test};

class AsyncServerTest extends AbstractServerTest {
  
  /**
   * Starts server in background
   *
   * @return void
   */
  #[BeforeClass]
  public static function startServer() {
    parent::startServerWith('peer.unittest.server.TestingProtocol', 'peer.server.AsyncServer');
  }

  #[Test]
  public function connected() {
    $this->connect();
    $this->assertHandled(['CONNECT']);
  }

  #[Test]
  public function disconnected() {
    $this->connect();
    $this->conn->close();
    $this->assertHandled(['CONNECT', 'DISCONNECT']);
  }

  #[Test, Ignore('Fragile test, dependant on OS / platform and implementation vagaries')]
  public function error() {
    $this->connect();
    $this->conn->write("SEND\n");
    $this->conn->close();
    $this->assertHandled(['CONNECT', 'ERROR']);
  }
}