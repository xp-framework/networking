<?php namespace peer\unittest\sockets;

use peer\Socket;
use peer\unittest\StartServer;
use unittest\actions\VerifyThat;

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

  #[Test]
  public function open_connection_asynchronously() {
    $this->fixture->open();

    $read= $write= $error= [$this->fixture];
    $this->fixture->kind()->select($read, $write, $error);

    $this->assertTrue($this->fixture->isConnected());
  }

  #[Test]
  public function open_connection_to_unbound_port() {

    // Use port 4 which is unassigned and thus VERY unlikely to be bound, see
    // https://en.wikipedia.org/wiki/List_of_TCP_and_UDP_port_numbers
    $fixture= $this->newSocket(self::$bindAddress[0], 4);
    $fixture->open();
    $read= $write= $error= [$fixture];
    $fixture->kind()->select($read, $write, $error);

    $this->assertFalse($fixture->isConnected());
  }
}