<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Network};
use unittest\{Assert, Expect, Test, Values, TestCase};

class Inet4AddressTest {

  /** @return iterable */
  private function localhost() {
    return [['127.0.0.1'], ['0177.0000.000.01'], ['0177.0.0.1'], ['0x7F.0.0.1'], ['0x7f.0.0.1'], ['0X7F.0.0.1']];
  }

  #[Test, Values('localhost')]
  public function createAddress($addr) {
    Assert::equals('127.0.0.1', (new Inet4Address($addr))->asString());
  }

  #[Test, Expect(FormatException::class)]
  public function createInvalidAddressRaisesException() {
    new Inet4Address('Who am I');
  }

  #[Test, Expect(FormatException::class)]
  public function emptySegmentRaisesException() {
    new Inet4Address('127.0..1');
  }

  #[Test, Expect(FormatException::class)]
  public function createInvalidAddressThatLooksLikeAddressRaisesException() {
    new Inet4Address('10.0.0.355');
  }
  
  #[Test, Expect(FormatException::class)]
  public function createInvalidAddressWithTooManyBlocksRaisesException() {
    new Inet4Address('10.0.0.255.5');
  }

  #[Test, Expect(FormatException::class)]
  public function invalidHexRaisesException() {
    new Inet4Address('0xZZ.0.0.1');
  }

  #[Test, Expect(FormatException::class)]
  public function invalidOctalRaisesException() {
    new Inet4Address('0ABC.0.0.1');
  }

  #[Test, Values('localhost')]
  public function loopbackAddress($addr) {
    Assert::true((new Inet4Address($addr))->isLoopback());
  }
  
  #[Test]
  public function alternativeLoopbackAddress() {
    Assert::true((new Inet4Address('127.0.0.200'))->isLoopback());
  }
  
  #[Test]
  public function inSubnet() {
    Assert::true((new Inet4Address('192.168.2.1'))->inSubnet(new Network(new Inet4Address('192.168.2'), 24)));
  }
  
  #[Test]
  public function notInSubnet() {
    Assert::false((new Inet4Address('192.168.2.1'))->inSubnet(new Network(new Inet4Address('172.17.0.0'), 12)));
  }
  
  #[Test]
  public function hostInOwnHostSubnet() {
    Assert::true((new Inet4Address('172.17.29.6'))->inSubnet(new Network(new Inet4Address('172.17.29.6'), 32)));
  }
  
  #[Test, Expect(FormatException::class)]
  public function illegalSubnet() {
    (new Inet4Address('172.17.29.6'))->inSubnet(new Network(new Inet4Address('172.17.29.6'), 33));
  }

  #[Test]
  public function sameIPsShouldBeEqual() {
    Assert::equals(new Inet4Address('127.0.0.1'), new Inet4Address('127.0.0.1'));
  }

  #[Test]
  public function differentIPsShouldBeDifferent() {
    Assert::notequals(new Inet4Address('127.0.0.5'), new Inet4Address('192.168.1.1'));
  }

  #[Test]
  public function castToString() {
    Assert::equals('192.168.1.1', (string)new Inet4Address('192.168.1.1'));
  }

  #[Test]
  public function reverseNotationLocalhost() {
    Assert::equals('1.0.0.127.in-addr.arpa', (new Inet4Address('127.0.0.1'))->reversedNotation());
  }
  
  #[Test]
  public function createSubnet_creates_subnet_with_trailing_zeros() {
    $addr= new Inet4Address('192.168.1.1');
    $subNetSize= 24;
    $expAddr= new Inet4Address('192.168.1.0');
    Assert::equals($expAddr, $addr->createSubnet($subNetSize)->getAddress());
    
    $addr= new Inet4Address('192.168.1.1');
    $subNetSize= 12;
    $expAddr= new Inet4Address('192.160.0.0');
    Assert::equals($expAddr, $addr->createSubnet($subNetSize)->getAddress());
  }
}