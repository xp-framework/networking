<?php namespace peer\net;

use lang\FormatException;

/**
 * IPv4 address
 *
 * @test peer.unittest.Inet4AddressTest
 */
class Inet4Address extends InetAddress {
  private $addr;

  /**
   * Constructor
   *
   * Converts IPv4 address from dotted form into a long. Supports hexadecimal and
   * octal notations. Yes, 0177.0.0.1 and 0x7F.0.0.1 are both equivalent with
   * 127.0.0.1 - localhost!
   *
   * @see    https://www.php.net/ip2long
   * @param  string|int $address
   * @throws lang.FormatException in case address is illegal
   */
  public function __construct($address) {
    if (is_int($address)) {
      if ($address < 0 || $address > 4294967295) {
        throw new FormatException('Integer out of range: '.$address);
      }
      $this->addr= $address;
    } else {
      $blocks= explode('.', $address);
      if (sizeof($blocks) > 4) {
        throw new FormatException('Given IP string has more than 4 blocks: '.$address);
      }

      // PHP's ip2long() doesn't support hexadecimal and octal representations
      $this->addr= 0;
      foreach ($blocks as $i => $block) {
        $l= strlen($block);
        $n= -1;
        if ($l > 1 && '0' === $block[0]) {
          if (('x' === $block[1] || 'X' === $block[1]) && $l === strspn($block, '0123456789aAbBcCdDeEfF', 2) + 2) {
            $n= hexdec($block);
          } else if ($l === strspn($block, '01234567')) {
            $n= octdec($block);
          }
        } else if ($l > 0 && $l === strspn($block, '0123456789')) {
          $n= (int)$block;
        }

        if ($n < 0 || $n > 255) {
          throw new FormatException('Invalid format of IP address: '.$address); 
        }

        $this->addr|= $n << (8 * (3 - $i));
      }
    }
  }

  /** @return int */
  public function sizeInBits() { return 32; }

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
   * @param  string|peer.net.Network $subnet
   * @return bool
   * @throws lang.FormatException in case net has invalid format
   */
  public function inSubnet($subnet) {
    $net= $subnet instanceof Network ? $subnet : new Network($subnet);
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