<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, InetAddressFactory};
use unittest\{Expect, Test, TestCase};

class InetAddressFactoryTest extends TestCase {
  private $cut;

  /** @return void */
  public function setUp() {
    $this->cut= new InetAddressFactory();
  }

  #[Test]
  public function createLocalhostV4() {
    $this->assertInstanceOf(Inet4Address::class, $this->cut->parse('127.0.0.1'));
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
  public function tryParse() {
    $this->assertEquals(new Inet4Address('172.17.29.6'), $this->cut->tryParse('172.17.29.6'));
  }

  #[Test]
  public function tryParseReturnsNullOnFailure() {
    $this->assertEquals(null, $this->cut->tryParse('not an ip address'));
  }

  #[Test]
  public function parseLocalhostV6() {
    $this->assertInstanceOf(Inet6Address::class, $this->cut->parse('::1'));
  }

  #[Test]
  public function parseV6() {
    $this->assertInstanceOf(Inet6Address::class, $this->cut->parse('fe80::a6ba:dbff:fefe:7755'));
  }

  #[Test, Expect(FormatException::class)]
  public function parseThatLooksLikeV6() {
    $this->cut->parse('::ffffff:::::a');
  }
}