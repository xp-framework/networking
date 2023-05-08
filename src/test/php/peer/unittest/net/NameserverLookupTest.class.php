<?php namespace peer\unittest\net;

use lang\ElementNotFoundException;
use peer\net\{Inet4Address, Inet6Address, NameserverLookup};
use unittest\{Assert, Expect, Test};

/**
 * Test nameserver lookup API
 *
 * @see   xp://peer.net.NameserverLookup'
 */
class NameserverLookupTest {

  /** Creates a testable nameserver lookup implementation */
  private function lookup() {
    return new class() extends NameserverLookup {
      private $results= [];

      public function returning($ip, $type= 'ip') {
        $this->results[]= [$type => $ip];
        return $this;
      }

      protected function _nativeLookup($what, $type) {
        return $this->results;
      }
    };
  }

  #[Test]
  public function lookupLocalhostAllInet4() {
    $fixture= $this->lookup()->returning('127.0.0.1');
    Assert::equals([new Inet4Address('127.0.0.1')], $fixture->lookupAllInet4('localhost'));
  }

  #[Test]
  public function lookupLocalhostInet4() {
    $fixture= $this->lookup()->returning('127.0.0.1');
    Assert::equals(new Inet4Address('127.0.0.1'), $fixture->lookupInet4('localhost'));
  }

  #[Test]
  public function lookupLocalhostAllInet6() {
    $fixture= $this->lookup()->returning('::1', 'ipv6');
    Assert::equals([new Inet6Address('::1')], $fixture->lookupAllInet6('localhost'));
  }

  #[Test]
  public function lookupLocalhostInet6() {
    $fixture= $this->lookup()->returning('::1', 'ipv6');
    Assert::equals(new Inet6Address('::1'), $fixture->lookupInet6('localhost'));
  }

  #[Test]
  public function lookupLocalhostAll() {
    $fixture= $this->lookup()->returning('127.0.0.1')->returning('::1', 'ipv6');
    
    Assert::equals(
      [new Inet4Address('127.0.0.1'), new Inet6Address('::1')],
      $fixture->lookupAll('localhost')
    );
  }

  #[Test]
  public function lookupLocalhost() {
    $fixture= $this->lookup()->returning('127.0.0.1')->returning('::1', 'ipv6');

    Assert::equals(new Inet4Address('127.0.0.1'), $fixture->lookup('localhost'));
  }

  #[Test]
  public function lookupAllNonexistantGivesEmptyArray() {
    $fixture= $this->lookup();
    Assert::equals([], $fixture->lookupAll('localhost'));
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function lookupNonexistantThrowsException() {
    $this->lookup()->lookup('localhost');
  }

  #[Test]
  public function reverseLookup() {
    $fixture= $this->lookup()->returning('localhost', 'target');
    Assert::equals('localhost', $fixture->reverseLookup(new Inet4Address('127.0.0.1')));
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function nonexistingReverseLookupCausesException() {
    $fixture= $this->lookup();
    $fixture->reverseLookup(new Inet4Address('192.168.1.1'));
  }

  #[Test]
  public function tryReverseLookupReturnsNullWhenNoneFound() {
    $fixture= $this->lookup();
    Assert::null($fixture->tryReverseLookup(new Inet4Address('192.178.1.1')));
  }
}