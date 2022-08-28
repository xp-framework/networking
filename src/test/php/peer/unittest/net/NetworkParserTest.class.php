<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, Network, NetworkParser};
use unittest\{Assert, Before, Expect, Test, Values};

class NetworkParserTest {
  private $fixture;

  /** @return iterable */
  private function illegal() {
    yield [''];
    yield ['not a network'];
    yield ['192.168.1.1'];
    yield ['192.168.1.1/'];
    yield ['192.168.1.1/a'];
    yield ['192.168.1.1 b24'];
    yield ['192.168.1.1/999'];
    yield ['256.256.256.256/24'];
  }

  #[Before]
  public function fixture() {
    $this->fixture= new NetworkParser();
  }

  #[Test]
  public function parse_v4_network() {
    Assert::equals(
      new Network(new Inet4Address('192.168.1.1'), 24),
      $this->fixture->parse('192.168.1.1/24')
    );
  }

  #[Test]
  public function parse_v6_network() {
    Assert::equals(
      new Network(new Inet6Address('fc00::'), 7),
      $this->fixture->parse('fc00::/7')
    );
  }

  #[Test, Expect(FormatException::class), Values('illegal')]
  public function parse_illegal($input) {
    $this->fixture->parse($input);
  }

  #[Test, Values('illegal')]
  public function tryParse_illegal($input) {
    Assert::null($this->fixture->tryParse($input));
  }

  #[Test]
  public function tryParse() {
    Assert::equals(
      new Network(new Inet4Address('172.16.0.0'), 12),
      $this->fixture->tryParse('172.16.0.0/12')
    );
  }

  #[Test]
  public function tryParse_short_v4_network() {
    Assert::equals(
      new Network(new Inet4Address('172.16.0.0'), 12),
      $this->fixture->tryParse('172.16/12')
    );
  }

  #[Test]
  public function tryParse_hexadecimal_v4_network() {
    Assert::equals(
      new Network(new Inet4Address('172.16.0.0'), 12),
      $this->fixture->tryParse('0xac.0x10/12')
    );
  }
}