<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, Network, NetworkParser};
use unittest\{Expect, Test};

class NetworkParserTest extends \unittest\TestCase {
  private $cut;

  /** @return void */
  public function setUp() {
    $this->cut= new NetworkParser();
  }

  #[Test]
  public function parseV4Network() {
    $this->assertEquals(
      new Network(new Inet4Address('192.168.1.1'), 24),
      $this->cut->parse('192.168.1.1/24')
    );
  }

  #[Test, Expect(FormatException::class)]
  public function parseV4NetworkThrowsExceptionOnIllegalNetworkString() {
    $this->cut->parse('192.168.1.1 b24');
  }

  #[Test]
  public function parseV6Network() {
    $this->assertEquals(
      new Network(new Inet6Address('fc00::'), 7),
      $this->cut->parse('fc00::/7')
    );
  }

  #[Test]
  public function tryParse() {
    $this->assertEquals(
      new Network(new Inet4Address('172.16.0.0'), 12),
      $this->cut->tryParse('172.16.0.0/12')
    );
  }

  #[Test]
  public function parseShortNetwork() {
    this->assertEquals(
      new Network(new Inet4Address('172.16.0.0'), 12),
      this->cut->tryParse('172.16/12')
    );
  }

  #[Test]
  public function tryParseReturnsNullOnFailure() {
    $this->assertEquals(null, $this->cut->tryParse('not a network'));
  }
}