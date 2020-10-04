<?php namespace peer\unittest\sockets;

use lang\IllegalStateException;
use peer\BSDSocket;
use peer\unittest\StartServer;
use unittest\actions\{Actions, ExtensionAvailable};
use unittest\{Expect, Test};

/**
 * TestCase
 *
 * @ext      sockets
 * @see      xp://peer.BSDSocket
 */
#[Action(eval: '[new ExtensionAvailable("sockets"), new StartServer(TestingServer::class, "connected", "shutdown")]')]
class BSDSocketTest extends AbstractSocketTest {

  /**
   * Creates a new client socket
   *
   * @param   string addr
   * @param   int port
   * @return  peer.Socket
   */
  protected function newSocket($addr, $port) {
    return new BSDSocket($addr, $port);
  }
  
  #[Test]
  public function inetDomain() {
    $this->fixture->setDomain(AF_INET);
    $this->assertEquals(AF_INET, $this->fixture->getDomain());
  }

  #[Test]
  public function unixDomain() {
    $this->fixture->setDomain(AF_UNIX);
    $this->assertEquals(AF_UNIX, $this->fixture->getDomain());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setDomainOnConnected() {
    $this->fixture->connect();
    $this->fixture->setDomain(AF_UNIX);
  }

  #[Test]
  public function streamType() {
    $this->fixture->setType(SOCK_STREAM);
    $this->assertEquals(SOCK_STREAM, $this->fixture->getType());
  }

  #[Test]
  public function dgramType() {
    $this->fixture->setType(SOCK_DGRAM);
    $this->assertEquals(SOCK_DGRAM, $this->fixture->getType());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setTypeOnConnected() {
    $this->fixture->connect();
    $this->fixture->setType(SOCK_STREAM);
  }

  #[Test]
  public function tcpProtocol() {
    $this->fixture->setProtocol(SOL_TCP);
    $this->assertEquals(SOL_TCP, $this->fixture->getProtocol());
  }

  #[Test]
  public function udpProtocol() {
    $this->fixture->setProtocol(SOL_UDP);
    $this->assertEquals(SOL_UDP, $this->fixture->getProtocol());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setProtocolOnConnected() {
    $this->fixture->connect();
    $this->fixture->setProtocol(SOL_TCP);
  }
}