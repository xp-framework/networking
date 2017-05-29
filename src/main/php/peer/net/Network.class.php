<?php namespace peer\net;

use lang\Value;
use util\Objects;

/**
 * Represent IP network
 */
class Network implements Value {

  /**
   * Constructor
   *
   * @param   peer.InetAddress addr
   * @param   int netmask
   */
  public function __construct(InetAddress $addr, $netmask) {
    if (!is_int($netmask) || $netmask < 0 || $netmask > $addr->sizeInBits())
      throw new \lang\FormatException('Netmask must be integer, between 0 and '.$addr->sizeInBits());

    $this->address= $addr;
    $this->netmask= $netmask;
  }

  /**
   * Acquire address
   *
   * @return  peer.InetAddress
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
   * @return  peer.InetAddress
   */
  public function getNetworkAddress() {
    return $this->address;
  }

  /**
   * Determine whether given address is part of this network
   *
   * @param   peer.InetAddress addr
   * @return  bool
   */
  public function contains(InetAddress $addr) {
    return $addr->inSubnet($this);
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
