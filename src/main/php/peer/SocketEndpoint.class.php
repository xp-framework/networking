<?php namespace peer;

use lang\FormatException;
use peer\net\InetAddress;

/**
 * Socket endpoint
 *
 * @test  peer.unittest.sockets.SocketEndpointTest
 * @test  peer.unittest.sockets.AbstractSocketTest
 */
class SocketEndpoint {
  protected $host= '';
  protected $port= 0;
  
  /**
   * Constructor
   *
   * @param  string|peer.net.InetAddress $host either hostname or an IP address
   * @param  int $port
   */
  public function __construct($host, $port) {
    $this->host= (string)$host;
    $this->port= $port;
  }

  /**
   * Parses an instance of this object from a given string in hostname:port notation
   *
   * @param   string in
   * @return  self
   * @throws  lang.FormatException
   */
  public static function valueOf($in) {
    if ('' === $in) {
      throw new FormatException('Malformed empty address');
    }

    // Parse string: "[fe80::1]:80" (v6) vs. "127.0.0.1:80" (v4)
    if ('[' === $in[0]) {
      $r= sscanf($in, '[%[0-9a-fA-F:]]:%d', $host, $port);
    } else {
      $r= sscanf($in, '%[^:]:%d', $host, $port);
    }
    if (2 !== $r) {
      throw new FormatException('Malformed address "'.$in.'"');
    }

    return new self($host, $port);
  }

  /**
   * Gets host
   *
   * @return  string
   */
  public function getHost() {
    return $this->host;
  }

  /**
   * Gets port
   *
   * @return  int
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * Gets address (hostname:port notation)
   *
   * @return  string
   */
  public function getAddress() {
    return (strstr($this->host, ':') ? '['.$this->host.']:' : $this->host.':').$this->port;
  }

  /**
   * Returns whether a given value is equal to this instance
   *
   * @param   var cmp
   * @return  bool
   */
  public function equals($cmp) {
    return (
      $cmp instanceof self &&
      $this->host === $cmp->host &&
      $this->port === $cmp->port
    );
  }

  /**
   * Creates a hashcode for this endpoint
   *
   * @return  string
   */
  public function hashCode() {
    return $this->getAddress();
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'('.$this->host.':'.$this->port.')';
  }
}