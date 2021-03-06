<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet6Address, Network};
use unittest\{Expect, Test};

/**
 * IPv6 addresses test 
 *
 * @see   http://en.wikipedia.org/wiki/Reverse_DNS_lookup#IPv6_reverse_resolution
 */
class Inet6AddressTest extends \unittest\TestCase {

  #[Test]
  public function createAddress() {
    $this->assertEquals(
      'febc:a574:382b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('febc:a574:382b:23c1:aa49:4592:4efe:9982'))->asString()
    );
  }

  #[Test]
  public function createAddressFromUpperCase() {
    $this->assertEquals(
      'febc:a574:382b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('FEBC:A574:382B:23C1:AA49:4592:4EFE:9982'))->asString()
    );
  }

  #[Test]
  public function createAddressFromPackedForm() {
    $this->assertEquals(
      '::1',
      (new Inet6Address("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\1", true))->asString()
    );
  }

  #[Test]
  public function createAddressFromPackedFormWithColonSpecialCase() {
    $this->assertEquals(
      '::3a',
      (new Inet6Address("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0:", true))->asString() // ord(':')==0x32
    );
  }

  #[Test]
  public function addressIsShortened() {
    $this->assertEquals(
      'febc:a574:382b::4592:4efe:9982',
      (new Inet6Address('febc:a574:382b:0000:0000:4592:4efe:9982'))->asString()
    );
  }
  
  #[Test]
  public function addressShorteningOnlyTakesPlaceOnce() {
    $this->assertEquals(
      'febc::23c1:aa49:0:0:9982',
      (new Inet6Address('febc:0000:0000:23c1:aa49:0000:0000:9982'))->asString()
    );
  }
  
  #[Test]
  public function hexquadsAreShortenedWhenStartingWithZero() {
    $this->assertEquals(
      'febc:a574:2b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('febc:a574:002b:23c1:aa49:4592:4efe:9982'))->asString()
    );
  }
  
  #[Test]
  public function addressPrefixIsShortened() {
    $this->assertEquals(
      '::382b:23c1:aa49:4592:4efe:9982',
      (new Inet6Address('0000:0000:382b:23c1:aa49:4592:4efe:9982'))->asString()
    );
  }
  
  #[Test]
  public function addressPostfixIsShortened() {
    $this->assertEquals(
      'febc:a574:382b:23c1:aa49::',
      (new Inet6Address('febc:a574:382b:23c1:aa49:0000:0000:0000'))->asString()
    );
  }
  
  #[Test]
  public function loopbackAddress() {
    $this->assertEquals('::1', (new Inet6Address('::1'))->asString());
  }
  
  #[Test]
  public function isLoopbackAddress() {
    $this->assertTrue((new Inet6Address('::1'))->isLoopback());
  }
  
  #[Test]
  public function isNotLoopbackAddress() {
    $this->assertFalse((new Inet6Address('::2'))->isLoopback());
  }
  
  #[Test]
  public function inSubnet() {
    $this->assertTrue((new Inet6Address('::1'))->inSubnet(new Network(new Inet6Address('::1'), 120)));
  }
  
  #[Test]
  public function inSmallestPossibleSubnet() {
    $this->assertTrue((new Inet6Address('::1'))->inSubnet(new Network(new Inet6Address('::0'), 127)));
  }
  
  #[Test]
  public function notInSubnet() {
    $this->assertFalse((new Inet6Address('::1'))->inSubnet(new Network(new Inet6Address('::0101'), 120)));
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
    $this->assertEquals(new Inet6Address('::1'), new Inet6Address('::1'));
  }

  #[Test]
  public function differentIPsShouldBeDifferent() {
    $this->assertNotEquals(new Inet6Address('::1'), new Inet6Address('::fe08'));
  }

  #[Test]
  public function castToString() {
    $this->assertEquals('[::1]', (string)new Inet6Address('::1'));
  }

  #[Test]
  public function reversedNotation() {
    $this->assertEquals(
      'b.a.9.8.7.6.5.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.8.b.d.0.1.0.0.2.ip6.arpa',
      (new Inet6Address('2001:db8::567:89ab'))->reversedNotation()
    );
  }
  
  #[Test]
  public function createSubnet_creates_subnet_with_trailing_zeros() {
    $addr= new Inet6Address('febc:a574:382b:23c1:aa49:4592:4efe:9982');
    $subNetSize= 64;
    $expAddr= new Inet6Address('febc:a574:382b:23c1::');
    $this->assertEquals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());
    
    $subNetSize= 48;
    $expAddr= new Inet6Address('febc:a574:382b::');
    $this->assertEquals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());
    
    $subNetSize= 35;
    $expAddr= new Inet6Address('febc:a574:2000::');
    $this->assertEquals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());
    
    $subNetSize= 128;
    $expAddr= $addr;
    $this->assertEquals($expAddr->asString(), $addr->createSubnet($subNetSize)->getAddress()->asString());      
  }
}