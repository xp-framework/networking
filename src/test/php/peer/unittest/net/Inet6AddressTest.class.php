<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet6Address, Network};
use test\{Assert, Expect, Test};

/**
 * IPv6 addresses test 
 *
 * @see   http://en.wikipedia.org/wiki/Reverse_DNS_lookup#IPv6_reverse_resolution
 */
class Inet6AddressTest {

  #[Test]
  public function createAddress() {
    Assert::equals(
      'febc:a574:382b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('febc:a574:382b:23c1:aa49:4592:4efe:9982'))->asString()
    );
  }

  #[Test]
  public function createAddressFromUpperCase() {
    Assert::equals(
      'febc:a574:382b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('FEBC:A574:382B:23C1:AA49:4592:4EFE:9982'))->asString()
    );
  }

  #[Test]
  public function createAddressFromPackedForm() {
    Assert::equals(
      '::1',
      (new Inet6Address("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\1", true))->asString()
    );
  }

  #[Test]
  public function createAddressFromPackedFormWithColonSpecialCase() {
    Assert::equals(
      '::3a',
      (new Inet6Address("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0:", true))->asString() // ord(':')==0x32
    );
  }

  #[Test]
  public function addressIsShortened() {
    Assert::equals(
      'febc:a574:382b::4592:4efe:9982',
      (new Inet6Address('febc:a574:382b:0000:0000:4592:4efe:9982'))->asString()
    );
  }
  
  #[Test]
  public function addressShorteningOnlyTakesPlaceOnce() {
    Assert::equals(
      'febc::23c1:aa49:0:0:9982',
      (new Inet6Address('febc:0000:0000:23c1:aa49:0000:0000:9982'))->asString()
    );
  }
  
  #[Test]
  public function hexquadsAreShortenedWhenStartingWithZero() {
    Assert::equals(
      'febc:a574:2b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('febc:a574:002b:23c1:aa49:4592:4efe:9982'))->asString()
    );
  }
  
  #[Test]
  public function addressPrefixIsShortened() {
    Assert::equals(
      '::382b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('0000:0000:382b:23c1:aa49:4592:4efe:9982'))->asString()
    );
  }
  
  #[Test]
  public function addressPostfixIsShortened() {
    Assert::equals(
      'febc:a574:382b:23c1:aa49::',
      (new Inet6Address('febc:a574:382b:23c1:aa49:0000:0000:0000'))->asString()
    );
  }
  
  #[Test]
  public function loopbackAddress() {
    Assert::equals('::1', (new Inet6Address('::1'))->asString());
  }
  
  #[Test]
  public function isLoopbackAddress() {
    Assert::true((new Inet6Address('::1'))->isLoopback());
  }
  
  #[Test]
  public function isNotLoopbackAddress() {
    Assert::false((new Inet6Address('::2'))->isLoopback());
  }
  
  #[Test]
  public function inSubnet() {
    Assert::true((new Inet6Address('::1'))->inSubnet(new Network(new Inet6Address('::1'), 120)));
  }
  
  #[Test]
  public function inSmallestPossibleSubnet() {
    Assert::true((new Inet6Address('::1'))->inSubnet(new Network(new Inet6Address('::0'), 127)));
  }
  
  #[Test]
  public function notInSubnet() {
    Assert::false((new Inet6Address('::1'))->inSubnet(new Network(new Inet6Address('::0101'), 120)));
  }

  #[Test, Expect(FormatException::class)]
  public function illegalAddress() {
    new Inet6Address('::ffffff:::::a');
  }

  #[Test, Expect(FormatException::class)]
  public function anotherIllegalAddress() {
    new Inet6Address('');
  }

  #[Test, Expect(FormatException::class)]
  public function invalidInputOfNumbers() {
    new Inet6Address('12345678901234567');
  }

  #[Test, Expect(FormatException::class)]
  public function invalidHexQuadBeginning() {
    new Inet6Address('XXXX::a574:382b:23c1:aa49:4592:4efe:9982');
  }

  #[Test, Expect(FormatException::class)]
  public function invalidHexQuadEnd() {
    new Inet6Address('9982::a574:382b:23c1:aa49:4592:4efe:XXXX');
  }

  #[Test, Expect(FormatException::class)]
  public function invalidHexQuad() {
    new Inet6Address('a574::XXXX:382b:23c1:aa49:4592:4efe:9982');
  }
  
  #[Test, Expect(FormatException::class)]
  public function invalidHexDigit() {
    new Inet6Address('a574::382X:382b:23c1:aa49:4592:4efe:9982');
  }

  #[Test]
  public function sameIPsShouldBeEqual() {
    Assert::equals(new Inet6Address('::1'), new Inet6Address('::1'));
  }

  #[Test]
  public function differentIPsShouldBeDifferent() {
    Assert::notequals(new Inet6Address('::1'), new Inet6Address('::fe08'));
  }

  #[Test]
  public function castToString() {
    Assert::equals('[::1]', (string)new Inet6Address('::1'));
  }

  #[Test]
  public function reversedNotation() {
    Assert::equals(
      'b.a.9.8.7.6.5.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.8.b.d.0.1.0.0.2.ip6.arpa',
      (new Inet6Address('2001:db8::567:89ab'))->reversedNotation()
    );
  }
  
  #[Test]
  public function createSubnet_creates_subnet_with_trailing_zeros() {
    $addr= new Inet6Address('febc:a574:382b:23c1:aa49:4592:4efe:9982');
    $subNetSize= 64;
    $expAddr= new Inet6Address('febc:a574:382b:23c1::');
    Assert::equals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());
    
    $subNetSize= 48;
    $expAddr= new Inet6Address('febc:a574:382b::');
    Assert::equals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());
    
    $subNetSize= 35;
    $expAddr= new Inet6Address('febc:a574:2000::');
    Assert::equals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());
    
    $subNetSize= 128;
    $expAddr= $addr;
    Assert::equals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());      
  }
}