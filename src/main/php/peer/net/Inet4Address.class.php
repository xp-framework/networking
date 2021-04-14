<?php namespace peer\net;

use lang\FormatException;

/**
 * IPv4 address
 *
 * @test  xp://peer.unittest.Inet4AddressTest
 * @see   php://ip2long
 */
class Inet4Address implements InetAddress {

  /**
   * Convert IPv4 address from dotted form into a long. Supports hexadecimal and
   * octal notations. Yes, 0177.0.0.1 and 0x7F.0.0.1 are both equivalent with
   * 127.0.0.1 - localhost!
   *
   * @param  string $ip
   * @return int
   * @throws lang.FormatException
   */
  protected static function ip2long($ip) {
    $i= 0; $addr= 0; $count= 0;
    foreach (explode('.', $ip) as $byte) {
      if (++$count > 4) {
        throw new FormatException('Given IP string has more than 4 blocks: ['.$ip.']');
      }

      $l= strlen($byte);
      $n= -1;
      if ($l > 1 && '0' === $byte[0]) {
        if ('x' === $byte[1] || 'X' === $byte[1] && $l === strspn($byte, '0123456789aAbBcCdDeEfF', 2) + 2) {
          $n= hexdec($byte);
        } else if ($l === strspn($byte, '0123456789')) {
          $n= octdec($byte);
        }
      } else if ($l === strspn($byte, '0123456789')) {
        $n= (int)$byte;
      }

      if ($n < 0 || $n > 255) {
        throw new FormatException('Invalid format of IP address: ['.$ip.']'); 
      }

      $addr|= ($n << (8 * (3 - $i++)));
    }
    return $addr;
  }
  
  /**
   * Constructor
   *
   * @param  string $address
   * @throws lang.FormatException in case address is illegal
   */
  public function __construct($address) {
    $this->addr= self::ip2long($address);
  }

  /**
   * Retrieve size of ips of this kind in bits.
   *
   * @return  int
   */
  public function  sizeInBits() {
    return 32;
  }

  /**
   * Retrieve IP address notation for DNS reverse query
   *
   * @return  string
   */
  public function reversedNotation() {
    return implode('.', array_reverse(explode('.', $this->asString()))).'.in-addr.arpa';
  }

  /**
   * Retrieve human-readable form
   *
   * @return  string
   */
  public function asString() {
    return long2ip($this->addr);
  }
  
  /**
   * Determine whether address is a loopback address
   *
   * @return  bool
   */
  public function isLoopback() {
    return $this->addr >> 8 === 0x7F0000;
  }
  
  /**
   * Determine whether address is in the given subnet
   *
   * @param   string net
   * @return  bool
   * @throws  lang.FormatException in case net has invalid format
   */
  public function inSubnet(Network $net) {
    if (!$net->getAddress() instanceof self) return false;
    
    $addrn= $net->getAddress()->addr;
    $mask= $net->getNetmask();
    return $this->addr >> (32 - $mask) === $addrn >> (32 - $mask);
  }
  
  /**
   * Create a subnet of this address, with the specified size.
   *
   * @param   int subnetSize
   * @return  peer.net.Network
   * @throws  lang.IllegalArgumentException in case the subnetSize is not correct
   */
  public function createSubnet($subnetSize) {
    $addr= $this->addr & (0xFFFFFFFF << (32 - $subnetSize));
    return new Network(new Inet4Address(long2ip($addr)), $subnetSize);
  }

  /** @return string */
  public function toString() {
    return nameof($this).'('.$this->asString().')';
  }

  /** @return string */
  public function hashCode() {
    return $this->asString();
  }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? strcmp($this->asString(), $value->asString()) : 1;
  }

  /**
   * Magic string case callback
   *
   * @return  string
   */
  public function  __toString() {
    return $this->asString();
  }
}