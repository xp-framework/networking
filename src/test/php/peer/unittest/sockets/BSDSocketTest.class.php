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
    $fixture= $this->newFixture();
    $fixture->setDomain(AF_INET);
    Assert::equals(AF_INET, $fixture->getDomain());
  }

  #[Test]
  public function unixDomain() {
    $fixture= $this->newFixture();
    $fixture->setDomain(AF_UNIX);
    Assert::equals(AF_UNIX, $fixture->getDomain());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setDomainOnConnected() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->setDomain(AF_UNIX);
  }

  #[Test]
  public function streamType() {
    $fixture= $this->newFixture();
    $fixture->setType(SOCK_STREAM);
    Assert::equals(SOCK_STREAM, $fixture->getType());
  }

  #[Test]
  public function dgramType() {
    $fixture= $this->newFixture();
    $fixture->setType(SOCK_DGRAM);
    Assert::equals(SOCK_DGRAM, $fixture->getType());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setTypeOnConnected() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->setType(SOCK_STREAM);
  }

  #[Test]
  public function tcpProtocol() {
    $fixture= $this->newFixture();
    $fixture->setProtocol(SOL_TCP);
    Assert::equals(SOL_TCP, $fixture->getProtocol());
  }

  #[Test]
  public function udpProtocol() {
    $fixture= $this->newFixture();
    $fixture->setProtocol(SOL_UDP);
    Assert::equals(SOL_UDP, $fixture->getProtocol());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function setProtocolOnConnected() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->setProtocol(SOL_TCP);
  }
}