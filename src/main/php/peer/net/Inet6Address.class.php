<?php namespace peer\net;

use lang\FormatException;
use util\Objects;

/**
 * IPv6 address
 *
 * @test  xp://peer.unittest.net.Inet6AddressTest
 */
class Inet6Address extends InetAddress {
  private $addr;

  /**
   * Constructor
   *
   * @param   string addr
   * @param   bool   binary
   */
  public function __construct($addr, $binary= false) {
    if ($binary) {
      $this->addr= $addr;
    } else {
      $this->addr= self::parse($addr);
    }
  }

  /** @return int */
  public function sizeInBits() { return 128; }

  /**
   * Parse a given input string, either raising exceptions or silently returning NULL.
   *
   * @param  string $input
   * @param  bool $throw
   * @return ?string
   * @throws lang.FormatException
   */
  public static function parse($input, $throw= true) {
    $out= '';
    $quads= explode(':', $input);

    // Shortest address is ::1, this results in 3 parts...
    if (sizeof($quads) < 3) {
      if ($throw) throw new FormatException('Address contains less than 1 hexquad part: '.$input);
      return null;
    }

    if ('' === $quads[0]) array_shift($quads);
    foreach ($quads as $hq) {
      if ('' === $hq) {
        $out.= str_repeat('0000', 8 - (sizeof($quads) - 1));
        continue;
      }

      // Catch cases like ::ffaadd00::
      if (strlen($hq) > 4) {
        if ($throw) throw new FormatException('Detected hexquad w/ more than 4 digits in '.$input);
        return null;
      }
      
      // Not hex
      if (strspn($hq, '0123456789abcdefABCDEF') < strlen($hq)) {
        if ($throw) throw new FormatException('Illegal digits in '.$input);
        return null;
      }

      $out.= str_repeat('0', 4 - strlen($hq)).$hq;
    }

    return pack('H*', $out);
  }
      
  /**
   * Retrieve human-readable form;
   *
   * this method will shorten upon the first possible occasion, not on the
   * occasion where shortening will save the most space.
   *
   * @return  string
   */
  public function asString() {
    $skipZero= false; $hasSkipped= false; $hexquads= [];
    for ($i= 0; $i < 16; $i+= 2) {
      if (!$hasSkipped && "\x00\x00" == $this->addr[$i].$this->addr[$i + 1]) {
        $skipZero= true;
        continue;
      }
      if ($skipZero) {
        if (0 === sizeof($hexquads)) { $hexquads[]= ''; }
        $hexquads[]= '';
        $hasSkipped= true;
        $skipZero= false;
      }
      if ("\x00\x00" == $this->addr[$i].$this->addr[$i + 1]) {
        $hexquads[]= '0';
      } else {
        $hexquads[]= ltrim(unpack('H*', $this->addr[$i].$this->addr[$i + 1])[1], '0');
      }
    }
    
    if ($skipZero) { $hexquads[]= ''; $hexquads[]= ''; }
    return implode(':', $hexquads);
  }    
  
  /**
   * Determine whether address is a loopback address
   *
   * @return  bool
   */
  public function isLoopback() {
    return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01" == $this->addr;
  }

  /**
   * Retrieve reversed notation
   *
   * @return  string
   */
  public function reversedNotation() {
    $nibbles= unpack('H*', $this->addr)[1];
    $ret= '';
    for ($i= 31; $i >= 0; $i--) {
      $ret.= $nibbles[$i].'.';
    }

    return $ret.'ip6.arpa';
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

    $addr= $net->getAddress();
    $mask= $net->getNetmask();
    $position= 0;
    while ($mask > 8) {
      if ($addr->addr[$position] !== $this->addr[$position]) return false;
      $position++;
      $mask-= 8;
    }

    return $mask > 0
      ? ord($addr->addr[$position]) >> (8 - $mask) === ord($this->addr[$position]) >> (8 - $mask)
      : true
    ;
  }
  
  /**
   * Create a subnet of this address, with the specified size.
   *
   * @param   int subnetSize
   * @return  Network
   * @throws  lang.IllegalArgumentException in case the subnetSize is not correct
   */
  public function createSubnet($subnetSize) {
    $addr= $this->addr;
    
    for ($i= 15; $i >= $subnetSize / 8; --$i) {
      $addr[$i]= "\0";
    }
    
    if($subnetSize % 8 > 0) {
      $lastNibblePos= (int)($subnetSize / 8);
      $addr[$lastNibblePos]= chr(ord($addr[$lastNibblePos]) & (0xFF << (8 - $subnetSize % 8)));
    }
    return new Network(new Inet6Address($addr, true), $subnetSize);
  }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->asString().')'; }

  /** @return string */
  public function hashCode() { return $this->asString(); }

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
   * Magic string cast callback
   *
   * @return  string
   */
  public function __toString() {
    return '['.$this->asString().']';
  }
}