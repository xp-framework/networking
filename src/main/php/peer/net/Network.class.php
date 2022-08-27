<?php namespace peer\net;

use lang\{Value, FormatException};
use util\Objects;

/**
 * Represent an IP network
 *
 * @test  peer.unittest.net.NetworkTest
 */
class Network implements Value {
  private $address, $netmask;

  /**
   * Constructor
   *
   * @param  string|peer.net.InetAddress $address
   * @param  ?int $netmask
   * @throws lang.FormatException
   */
  public function __construct($address, $netmask= null) {
    if ($address instanceof InetAddress) {
      $this->address= $address;
    } else {
      sscanf($address, '%[^/]/%d', $base, $netmask);
      $this->address= InetAddress::new($base);
    }

    $size= $this->address->sizeInBits();
    if (!is_int($netmask) || $netmask < 0 || $netmask > $size) {
      throw new FormatException('Netmask must be integer, between 0 and '.$size);
    }
    $this->netmask= $netmask;
  }

  /**
   * Acquire address
   *
   * @return  peer.net.InetAddress
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * Get netmask
   *
   * @return  int
   */
  public function getNetmask() {
    return $this->netmask;
  }

  /**
   * Return address as string
   *
   * @return  string
   */
  public function asString() {
    return $this->address->asString().'/'.$this->netmask;
  }

  /**
   * Get base / network IP
   *
   * @return  peer.net.InetAddress
   */
  public function getNetworkAddress() {
    return $this->address;
  }

  /**
   * Determine whether given address is part of this network
   *
   * @param  string|peer.net.InetAddress $address
   * @return bool
   */
  public function contains($address) {
    return ($address instanceof InetAddress ? $address : InetAddress::new($address))->inSubnet($this);
  }

  /** @return string */
  public function toString() {
    return nameof($this).'('.$this->address->asString().'/'.$this->netmask.')';
  }

  /** @return string */
  public function hashCode() {
    return $this->address->asString().'/'.$this->netmask;
  }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->address, $this->netmask], [$value->address, $value->netmask])
      : 1
    ;
  }
}