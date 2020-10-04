<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, Network};
use unittest\{Expect, Test};

class NetworkTest extends \unittest\TestCase {

  #[Test]
  public function createNetwork() {
    $net= new Network(new Inet4Address("127.0.0.1"), 24);
    $this->assertEquals('127.0.0.1/24', $net->asString());
  }

  #[Test, Expect(FormatException::class)]
  public function createNetworkFailsIfTooLargeNetmaskGiven() {
    new Network(new Inet4Address("127.0.0.1"), 33);
  }

  #[Test]
  public function createNetworkV6() {
    $this->assertEquals(
      'fe00::/7',
      (new Network(new Inet6Address('fe00::'), 7))->asString()
    );
  }

  #[Test]
  public function createNetworkV6WorkAlsoWithNetmaskTooBigInV4() {
    $this->assertEquals(
      'fe00::/35',
      (new Network(new Inet6Address('fe00::'), 35))->asString()
    );
  }

  #[Test, Expect(FormatException::class)]
  public function createNetworkV6FailsIfTooLargeNetmaskGiven() {
    new Network(new Inet6Address('fe00::'), 763);
  }

  #[Test, Expect(FormatException::class)]
  public function createNetworkFailsIfTooSmallNetmaskGiven() {
    new Network(new Inet4Address("127.0.0.1"), -1);
  }

  #[Test, Expect(FormatException::class)]
  public function createNetworkFailsIfNonIntegerNetmaskGiven() {
    new Network(new Inet4Address("127.0.0.1"), 0.5);
  }

  #[Test, Expect(FormatException::class)]
  public function createNetworkFailsIfStringGiven() {
    new Network(new Inet4Address("127.0.0.1"), "Hello");
  }

  #[Test]
  public function networkAddress() {
    $net= new Network(new Inet4Address("127.0.0.0"), 24);
    $this->assertEquals(new Inet4Address("127.0.0.0"), $net->getNetworkAddress());
  }

  #[Test]
  public function loopbackNetworkContainsLoopbackAddressV4() {
    $this->assertTrue((new Network(new Inet4Address('127.0.0.5'), 24))->contains(new Inet4Address('127.0.0.1')));
  }

  #[Test]
  public function equalNetworksAreEqual() {
    $this->assertEquals(
      new Network(new Inet4Address('127.0.0.1'), 8),
      new Network(new Inet4Address('127.0.0.1'), 8)
    );
  }
}