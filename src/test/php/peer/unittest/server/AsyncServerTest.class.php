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

  #[Test]
  public function read_synchronously_written_data() {
    $this->connect();
    $this->conn->write("SYNC 3\n");
    $read= [];
    while ('.' !== ($line= $this->conn->readLine())) {
      $read[]= $line;
    }
    $this->conn->close();
    $this->assertEquals(['1', '2', '3'], $read);
    $this->assertHandled(['CONNECT', 'DISCONNECT']);
  }

  #[Test]
  public function read_asynchronously_written_data() {
    $this->connect();
    $this->conn->write("ASNC 3\n");
    $read= [];
    while ('.' !== ($line= $this->conn->readLine())) {
      $read[]= $line;
    }
    $this->conn->close();
    $this->assertEquals(['1', '2', '3'], $read);
    $this->assertHandled(['CONNECT', 'DISCONNECT']);
  }

  #[Test, Ignore('Fragile test, dependant on OS / platform and implementation vagaries')]
  public function interrupt_asynchronously_written_data() {
    $this->connect();
    $this->conn->write("ASNC 3\n");
    $this->conn->readLine();
    $this->conn->close();

    $this->assertHandled(['CONNECT', 'ERROR', 'DISCONNECT']);

    $this->connect();
    $this->conn->write("SYNC 1\n");
    $this->conn->readLine();
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