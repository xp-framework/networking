<?php namespace peer\unittest\net;

use lang\ElementNotFoundException;
use peer\net\{Inet4Address, Inet6Address, NameserverLookup};
use unittest\{Expect, Test};

/**
 * Test nameserver lookup API
 *
 * @see   xp://peer.net.NameserverLookup'
 */
class NameserverLookupTest extends \unittest\TestCase {
  private $cut;

  /**
   * Sets up test case and defines dummy nameserver lookup fixture
   *
   * @return void
   */
  public function setUp() {
    $this->cut= newinstance(NameserverLookup::class, [], [
      'results' => [],
      'addLookup' => function($ip, $type= 'ip') { $this->results[]= [$type => $ip]; },
      '_nativeLookup' => function($what, $type) { return $this->results; }
    ]);
  }

  #[Test]
  public function lookupLocalhostAllInet4() {
    $this->cut->addLookup('127.0.0.1');
    $this->assertEquals([new Inet4Address('127.0.0.1')], $this->cut->lookupAllInet4('localhost'));
  }

  #[Test]
  public function lookupLocalhostInet4() {
    $this->cut->addLookup('127.0.0.1');
    $this->assertEquals(new Inet4Address('127.0.0.1'), $this->cut->lookupInet4('localhost'));
  }

  #[Test]
  public function lookupLocalhostAllInet6() {
    $this->cut->addLookup('::1', 'ipv6');
    $this->assertEquals([new Inet6Address('::1')], $this->cut->lookupAllInet6('localhost'));
  }

  #[Test]
  public function lookupLocalhostInet6() {
    $this->cut->addLookup('::1', 'ipv6');
    $this->assertEquals(new Inet6Address('::1'), $this->cut->lookupInet6('localhost'));
  }

  #[Test]
  public function lookupLocalhostAll() {
    $this->cut->addLookup('127.0.0.1');
    $this->cut->addLookup('::1', 'ipv6');
    
    $this->assertEquals(
      [new Inet4Address('127.0.0.1'), new Inet6Address('::1')],
      $this->cut->lookupAll('localhost')
    );
  }

  #[Test]
  public function lookupLocalhost() {
    $this->cut->addLookup('127.0.0.1');
    $this->cut->addLookup('::1', 'ipv6');

    $this->assertEquals(
      new Inet4Address('127.0.0.1'),
      $this->cut->lookup('localhost')
    );
  }

  #[Test]
  public function lookupAllNonexistantGivesEmptyArray() {
    $this->assertEquals([], $this->cut->lookupAll('localhost'));
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function lookupNonexistantThrowsException() {
    $this->cut->lookup('localhost');
  }

  #[Test]
  public function reverseLookup() {
    $this->cut->addLookup('localhost', 'target');
    $this->assertEquals('localhost', $this->cut->reverseLookup(new Inet4Address('127.0.0.1')));
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function nonexistingReverseLookupCausesException() {
    $this->cut->reverseLookup(new Inet4Address('192.168.1.1'));
  }

  #[Test]
  public function tryReverseLookupReturnsNullWhenNoneFound() {
    $this->assertNull($this->cut->tryReverseLookup(new Inet4Address('192.178.1.1')));
  }
}