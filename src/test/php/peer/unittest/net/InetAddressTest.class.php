<?php namespace peer\unittest\net;

use lang\FormatException;
use peer\net\{Inet4Address, Inet6Address, InetAddress};
use test\{Assert, Expect, Test};

class InetAddressTest {

  #[Test]
  public function new_v4() {
    Assert::instance(Inet4Address::class, InetAddress::new('127.0.0.1'));
  }

  #[Test]
  public function new_v6() {
    Assert::instance(Inet6Address::class, InetAddress::new('::1'));
  }

  #[Test, Expect(FormatException::class)]
  public function new_from_invalid() {
    InetAddress::new('...');
  }
}