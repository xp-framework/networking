<?php namespace peer\net;

/**
 * Class to perform DNS name server lookups; supports IPv4
 * and IPv6, both.
 *
 * This class is currently still experimental, because it internally
 * uses php://dns_get_record - a function which is not available on
 * all platforms in all supported PHP versions.
 *
 * @test    xp://net.xp_framework.unittest.peer.net.NameserverLookupTest
 * @see     php://dns_get_record
 */
class NameserverLookup extends \lang\Object {

  /**
   * Perform "real" dns lookup
   *
   * @param   string what
   * @param   int type a bitfield of DNS_* constants
   * @return  array
   */
  protected function _nativeLookup($what, $type) {
    return dns_get_record($what, $type);
  }

  /**
   * Lookup all inet4 addresses
   *
   * @param   string host
   * @return  peer.netInet4Address[]
   */
  public function lookupAllInet4($host) {
    $res= [];
    foreach ($this->_nativeLookup($host, DNS_A) as $addr) {
      $res[]= new Inet4Address($addr['ip']);
    }

    return $res;
  }

  /**
   * Lookup inet4 address
   *
   * @param   string host
   * @return  peer.netInet4Address
   */
  public function lookupInet4($host) {
    $addr= $this->_nativeLookup($host, DNS_A);
    if (sizeof($addr) < 1) throw new \lang\ElementNotFoundException('No record found for "'.$host.'"');

    return new Inet4Address($addr[0]['ip']);
  }

  /**
   * Lookup all inet6 addresses
   *
   * @param   string host
   * @return  peer.net.Inet6Address
   */
  public function lookupAllInet6($host) {
    $res= [];
    foreach ($this->_nativeLookup($host, DNS_AAAA) as $addr) {
      $res[]= new Inet6Address($addr['ipv6']);
    }

    return $res;
  }

  /**
   * Lookup inet6 address
   *
   * @param   string host
   * @return  peer.net.Inet4Address
   */
  public function lookupInet6($host) {
    $addr= $this->_nativeLookup($host, DNS_AAAA);
    if (sizeof($addr) < 1) throw new \lang\ElementNotFoundException('No record found for "'.$host.'"');

    return new Inet6Address($addr[0]['ipv6']);
  }

  /**
   * Lookup all addresses
   *
   * @param   string host
   * @return  peer.net.InetAddress[]
   */
  public function lookupAll($host) {
    $res= []; $parser= new InetAddressFactory();
    foreach ($this->_nativeLookup($host, DNS_A|DNS_AAAA) as $addr) {
      if (isset($addr['ip'])) {
        $res[]= $parser->parse($addr['ip']);
      }

      if (isset($addr['ipv6'])) {
        $res[]= $parser->parse($addr['ipv6']);
      }
    }

    return $res;
  }

  /**
   * Lookup inet4 address
   *
   * @param   string host
   * @return  peer.netInet4Address
   */
  public function lookup($host) {
    $addr= $this->_nativeLookup($host, DNS_A|DNS_AAAA);
    if (sizeof($addr) < 1) throw new \lang\ElementNotFoundException('No record found for "'.$host.'"');

    $parser= new InetAddressFactory();
    return $parser->parse($addr[0]['ip']);
  }

  /**
   * Perform reverse lookup for given address
   *
   * @param   peer.InetAddress addr
   * @return  string
   * @throws  lang.ElementNotFoundException in case no reverse lookup exists
   */
  public function reverseLookup(InetAddress $addr) {
    $ptr= $this->tryReverseLookup($addr);
    if (null === $ptr) throw new \lang\ElementNotFoundException('No reverse lookup for '.$addr->toString());

    return $ptr;
  }

  /**
   * Try to perform reverse lookup for given address; if no reverse lookup
   * exists, returns NULL.
   *
   * @param   peer.InetAddress addr
   * @return  string
   */
  public function tryReverseLookup(InetAddress $addr) {
    $ptr= $this->_nativeLookup($addr->reversedNotation(), DNS_PTR);
    if (!isset($ptr[0]['target'])) return null;

    return $ptr[0]['target'];
  }
}
