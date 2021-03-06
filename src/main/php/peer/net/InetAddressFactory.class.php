<?php namespace peer\net;

/**
 * InetAddress Factory
 *
 * @test  xp://peer.unittest.net.InetAddressFactoryTest
 */
class InetAddressFactory {

  /**
   * Parse address from string
   *
   * @param   string string
   * @return  peer.InetAddress
   * @throws  lang.FormatException if address could not be matched
   */
  public function parse($string) {
    if (preg_match('#^[a-fA-F0-9x\.]+$#', $string)) {
      return new Inet4Address($string);
    }

    if (preg_match('#^[a-f0-9\:]+$#', $string)) {
      return new Inet6Address($string);
    }

    throw new \lang\FormatException('Given argument does not look like an IP address: '.$string);
  }

  /**
   * Parse address from string, return NULL on failure
   *
   * @param   string string
   * @return  peer.InetAddress
   */
  public function tryParse($string) {
    try {
      return $this->parse($string);
    } catch (\lang\FormatException $e) {
      return null;
    }
  }
}