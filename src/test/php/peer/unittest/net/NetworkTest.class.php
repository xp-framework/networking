<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, Network};
use test\{Assert, Expect, Test, Values};

class NetworkTest {

  #[Test]
  public function create_from_v4_address() {
    $net= new Network(new Inet4Address('127.0.0.1'), 24);
    Assert::equals('127.0.0.1/24', $net->asString());
  }

  #[Test]
  public function create_from_v4_string() {
    $net= new Network('127.0.0.1', 24);
    Assert::equals('127.0.0.1/24', $net->asString());
  }

  #[Test]
  public function create_with_v4_string_containing_netmask() {
    $net= new Network('127.0.0.1/24');
    Assert::equals('127.0.0.1/24', $net->asString());
  }

  #[Test]
  public function create_from_v6_address() {
    $net= new Network(new Inet6Address('fe00::'), 7);
    Assert::equals('fe00::/7', $net->asString());
  }

  #[Test]
  public function create_from_v6_string() {
    $net= new Network('fe00::', 7);
    Assert::equals('fe00::/7', $net->asString());
  }

  #[Test]
  public function create_with_v6_string_containing_netmask() {
    $net= new Network('fe00::/24');
    Assert::equals('fe00::/24', $net->asString());
  }

  #[Test]
  public function create_from_v6_address_with_netmask_too_big_for_v4() {
    $net= new Network(new Inet6Address('fe00::'), 35);
    Assert::equals('fe00::/35', $net->asString());
  }

  #[Test, Values([['127.0.0.1', 33], ['fe00::', 763]]), Expect(FormatException::class)]
  public function netmask_too_big($address, $netmask) {
    new Network($address, $netmask);
  }

  #[Test, Values([['127.0.0.1', -1], ['fe00::', -1]]), Expect(FormatException::class)]
  public function netmask_too_small($address, $netmask) {
    new Network($address, $netmask);
  }

  #[Test, Expect(FormatException::class)]
  public function null_netmask_when_not_supplied_via_address_string() {
    new Network('127.0.0.1', null);
  }

  #[Test]
  public function network_address() {
    $net= new Network('127.0.0.0/24');
    Assert::equals(new Inet4Address('127.0.0.0'), $net->getNetworkAddress());
  }

  #[Test]
  public function loopback_network_contains_v4_loopback_address() {
    Assert::true((new Network('127.0.0.5/24'))->contains(new Inet4Address('127.0.0.1')));
  }

  #[Test]
  public function loopback_network_contains_v4_loopback_string() {
    Assert::true((new Network('127.0.0.5/24'))->contains('127.0.0.1'));
  }

  #[Test]
  public function equality() {
    Assert::equals(
      new Network(new Inet4Address('127.0.0.1'), 8),
      new Network(new Inet4Address('127.0.0.1'), 8)
    );
  }
}