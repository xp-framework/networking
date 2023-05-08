<?php namespace peer\unittest\sockets;

use lang\IllegalStateException;
use peer\BSDSocket;
use peer\unittest\StartServer;
use unittest\actions\{Actions, ExtensionAvailable};
use unittest\{Assert, Expect, Test};

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
    Assert::equals(AF_INET, $this->fixture->getDomain());
  }

  #[Test]
  public function unixDomain() {
    $this->fixture->setDomain(AF_UNIX);
    Assert::equals(AF_UNIX, $this->fixture->getDomain());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setDomainOnConnected() {
    $this->fixture->connect();
    $this->fixture->setDomain(AF_UNIX);
  }

  #[Test]
  public function streamType() {
    $this->fixture->setType(SOCK_STREAM);
    Assert::equals(SOCK_STREAM, $this->fixture->getType());
  }

  #[Test]
  public function dgramType() {
    $this->fixture->setType(SOCK_DGRAM);
    Assert::equals(SOCK_DGRAM, $this->fixture->getType());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setTypeOnConnected() {
    $this->fixture->connect();
    $this->fixture->setType(SOCK_STREAM);
  }

  #[Test]
  public function tcpProtocol() {
    $this->fixture->setProtocol(SOL_TCP);
    Assert::equals(SOL_TCP, $this->fixture->getProtocol());
  }

  #[Test]
  public function udpProtocol() {
    $this->fixture->setProtocol(SOL_UDP);
    Assert::equals(SOL_UDP, $this->fixture->getProtocol());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setProtocolOnConnected() {
    $this->fixture->connect();
    $this->fixture->setProtocol(SOL_TCP);
  }
}