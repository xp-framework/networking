<?php namespace peer\net;

use lang\Value;

/**
 * Common ancestor for IPv4 and IPv6
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
}