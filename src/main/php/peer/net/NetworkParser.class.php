<?php namespace peer\net;

use lang\FormatException;

/**
 * Parses string notations into `peer.net.Network` instances.
 *
 * @test  peer.unittest.net.NetworkParserTest
 */
class NetworkParser {
  private $addresses;

  /** Constructor */
  public function __construct() {
    $this->addresses= new InetAddressFactory();
  }

  /**
   * Parse given string into network object
   *
   * @param  string $input
   * @return peer.Network
   * @throws lang.FormatException if string could not be parsed
   */
  public function parse(string $input) {
    if (2 !== sscanf($input, '%[^/]/%d', $base, $netmask)) {
      throw new FormatException('Given string cannot be parsed to network: '.$input);
    }

    return new Network($this->addresses->parse($base), $netmask);
  }

  /**
   * Parse given string into network object, return NULL if it fails.
   *
   * @param  string $input
   * @return ?peer.Network
   */
  public function tryParse(string $input) {
    $valid= (
      (2 === sscanf($input, '%[^/]/%d', $base, $netmask)) &&
      ($address= $this->addresses->tryParse($base)) &&
      ($netmask >= 0) && 
      ($netmask <= $address->sizeInBits())
    );

    return $valid ? new Network($address, $netmask) : null;
  }
}