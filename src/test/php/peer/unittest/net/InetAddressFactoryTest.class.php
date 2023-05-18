<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, InetAddressFactory};
use unittest\{Assert, Before, Expect, Test, Values};

class InetAddressFactoryTest {
  private $cut;

  #[Before]
  public function factory() {
    $this->cut= new InetAddressFactory();
  }

  #[Test]
  public function createLocalhostV4() {
    Assert::instance(Inet4Address::class, $this->cut->parse('127.0.0.1'));
  }

  #[Test, Expect(FormatException::class)]
  public function parseInvalidAddressThatLooksLikeV4() {
    $this->cut->parse('3.33.333.333');
  }

  #[Test, Expect(FormatException::class)]
  public function parseInvalidAddressThatAlsoLooksLikeV4() {
    $this->cut->parse('10..3.3');
  }

  #[Test]
  public function tryParse_v4() {
    Assert::equals(new Inet4Address('172.17.29.6'), $this->cut->tryParse('172.17.29.6'));
  }

  #[Test]
  public function tryParse_v6() {
    Assert::equals(new Inet6Address('::1'), $this->cut->tryParse('::1'));
  }

  #[Test, Values(['', '3.33.333.333', '10..3.3', '::ffffff:::::a', 'not an ip address'])]
  public function tryParseReturnsNullOnFailure($input) {
    Assert::equals(null, $this->cut->tryParse($input));
  }

  #[Test]
  public function parseLocalhostV6() {
    Assert::instance(Inet6Address::class, $this->cut->parse('::1'));
  }

  #[Test]
  public function parseV6() {
    Assert::instance(Inet6Address::class, $this->cut->parse('fe80::a6ba:dbff:fefe:7755'));
  }

  #[Test, Expect(FormatException::class)]
  public function parseThatLooksLikeV6() {
    $this->cut->parse('::ffffff:::::a');
  }
}