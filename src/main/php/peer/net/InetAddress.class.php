<?php namespace peer\net;

use lang\{Value, FormatException, IllegalArgumentException};

/**
 * Common ancestor for IPv4 and IPv6
 *
 * @test  peer.unittest.net.InetAddressTest
 */
abstract class InetAddress implements Value {

  /**
   * Retrieve "human-readable" address
   *
   * @return string
   */
  public abstract function asString();
  
  /**
   * Check whether this address is a loopback address
   *
   * @return bool
   */
  public abstract function isLoopback();
  
  /**
   * Determine whether this address is in the given network.
   *
   * @param  string|peer.net.Network $subnet
   * @return bool
   * @throws lang.FormatException in case net has invalid format
   */
  public abstract function inSubnet($subnet);

  /**
   * Create a subnet of this address, with the specified size.
   *
   * @param  int $size
   * @return peer.net.Network
   * @throws lang.IllegalArgumentException in case the $size is not correct
   */
  public abstract function createSubnet($size);
  
  /**
   * Retrieve size of address in bits
   *
   * @return int
   */
  public abstract function sizeInBits();

  /**
   * Retrieve reversed notation for reverse DNS lookups
   *
   * @return string
   */
  public abstract function reversedNotation();

  /**
   * Returns an IPv4 or IPv6 address based on the given input
   *
   * @throws lang.FormatException
   */
  public static function new(string $arg): self {
    if (preg_match('/^[a-fA-F0-9x\.]+$/', $arg)) {
      return new Inet4Address($arg);
    } else if (preg_match('/^[a-f0-9\:]+$/', $arg)) {
      return new Inet6Address($arg);
    } else {
      throw new FormatException('Given argument does not look like an IP address: '.$arg);
    }
  }
}