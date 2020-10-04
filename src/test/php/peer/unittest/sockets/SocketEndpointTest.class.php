<?php namespace peer\unittest\sockets;

use lang\FormatException;
use peer\SocketEndpoint;
use peer\net\{Inet4Address, Inet6Address};
use unittest\{Expect, Test, Values};

/**
 * TestCase
 *
 * @see      xp://peer.SocketEndpoint
 */
class SocketEndpointTest extends \unittest\TestCase {

  #[Test]
  public function v4_string_passed_to_constructor() {
    $this->assertEquals('127.0.0.1', (new SocketEndpoint('127.0.0.1', 6100))->getHost());
  }

  #[Test]
  public function v4_addr_passed_to_constructor() {
    $this->assertEquals(
      '127.0.0.1',
      (new SocketEndpoint(new Inet4Address('127.0.0.1'), 6100))->getHost()
    );
  }

  #[Test]
  public function v6_string_passed_to_constructor() {
    $this->assertEquals('fe80::1', (new SocketEndpoint('fe80::1', 6100))->getHost());
  }

  #[Test]
  public function v6_addr_passed_to_constructor() {
    $this->assertEquals(
      '[fe80::1]',
      (new SocketEndpoint(new Inet6Address('fe80::1'), 6100))->getHost()
    );
  }

  #[Test]
  public function port_passed_to_constructor() {
    $this->assertEquals(6100, (new SocketEndpoint('127.0.0.1', 6100))->getPort());
  }

  #[Test]
  public function equal_to_same() {
    $this->assertEquals(
      new SocketEndpoint('127.0.0.1', 6100),
      new SocketEndpoint('127.0.0.1', 6100)
    );
  }

  #[Test]
  public function equal_to_itself() {
    $fixture= new SocketEndpoint('127.0.0.1', 6100);
    $this->assertEquals($fixture, $fixture);
  }

  #[Test]
  public function not_equal_to_this() {
    $this->assertNotEquals($this, new SocketEndpoint('127.0.0.1', 6100));
  }

  #[Test, Values([null, '127.0.0.1:6100', 1270016100])]
  public function not_equal_to_primitive($value) {
    $this->assertNotEquals($value, new SocketEndpoint('127.0.0.1', 6100));
  }

  #[Test]
  public function v4_address() {
    $this->assertEquals('127.0.0.1:6100', (new SocketEndpoint('127.0.0.1', 6100))->getAddress());
  }

  #[Test]
  public function v6_address() {
    $this->assertEquals('[fe80::1]:6100', (new SocketEndpoint('fe80::1', 6100))->getAddress());
  }

  #[Test]
  public function hashcode_returns_address() {
    $this->assertEquals('127.0.0.1:6100', (new SocketEndpoint('127.0.0.1', 6100))->hashCode());
  }

  #[Test]
  public function value_of_parses_v4_address() {
    $this->assertEquals(new SocketEndpoint('127.0.0.1', 6100), SocketEndpoint::valueOf('127.0.0.1:6100'));
  }

  #[Test]
  public function value_of_parses_v6_address() {
    $this->assertEquals(new SocketEndpoint('fe80::1', 6100), SocketEndpoint::valueOf('[fe80::1]:6100'));
  }

  #[Test, Expect(FormatException::class)]
  public function value_of_empty_string() {
    SocketEndpoint::valueOf('');
  }

  #[Test, Expect(FormatException::class)]
  public function value_of_without_colon() {
    SocketEndpoint::valueOf('127.0.0.1');
  }

  #[Test, Expect(FormatException::class)]
  public function value_of_without_port() {
    SocketEndpoint::valueOf('127.0.0.1:');
  }
}