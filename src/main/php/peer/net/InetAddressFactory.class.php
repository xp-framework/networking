<?php namespace peer\net;

use lang\FormatException;

/**
 * Parses string notations into `peer.net.InetAddress` instances.
 *
 * @test  peer.unittest.net.InetAddressFactoryTest
 */
class InetAddressFactory {

  /**
   * Parse address from string
   *
   * @param  string $input
   * @return peer.InetAddress
   * @throws lang.FormatException if address could not be matched
   */
  public static function parse(string $input) {
    if (preg_match('#^[a-fA-F0-9x\.]+$#', $input)) {
      return new Inet4Address($input);
    } else if (preg_match('#^[a-f0-9\:]+$#', $input)) {
      return new Inet6Address($input);
    } else {
      throw new FormatException('Given argument does not look like an IP address: '.$input);
    }
  }

  /**
   * Parse address from string, return NULL on failure
   *
   * @param  string $input
   * @return ?peer.InetAddress
   */
  public static function tryParse(string $input) {
    if (preg_match('#^[a-fA-F0-9x\.]+$#', $input)) {
      $addr= Inet4Address::parse($input, false);
      return null === $addr ? null : new Inet4Address($addr);
    } else if (preg_match('#^[a-f0-9\:]+$#', $input)) {
      $addr= Inet6Address::parse($input, false);
      return null === $addr ? null : new Inet6Address($addr, true);
    } else {
      return null;
    }
  }
}