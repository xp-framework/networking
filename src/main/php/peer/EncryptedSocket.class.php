<?php namespace peer;

use lang\{IllegalArgumentException, IllegalStateException};

/**
 * Socket using SSL or TLS encryption
 *
 * @ext  openssl
 * @see  http://php.net/manual/en/context.ssl.php
 */
class EncryptedSocket extends Socket {
  private $method;

  /**
   * Constructor
   *
   * @param  string $host hostname or IP address
   * @param  int $port
   * @param  resource $socket default NULL
   * @param  int|string $method
   * @param  [:var] $options
   * @throws lang.IllegalArgumentException if method is unknown
   */
  public function __construct($host, $port, $socket= null, $method= STREAM_CRYPTO_METHOD_TLS_CLIENT, $options= []) {
    parent::__construct($host, $port, $socket);

    // Use "localhost" as peer name in these well-known cases.
    if ('localhost' === $host || '127.0.0.1' === $host || '[::1]' === $host) {
      $this->setSocketOption('ssl', 'peer_name', 'localhost');
    }

    foreach ($options as $name => $value) {
      $this->setSocketOption('ssl', $name, $value);
    }

    if (is_int($method)) {
      $this->method= $method;
      return;
    }

    switch ($method) {
      case 'ssl': $this->method= STREAM_CRYPTO_METHOD_ANY_CLIENT; break;
      case 'sslv3': $this->method= STREAM_CRYPTO_METHOD_SSLv3_CLIENT; break;
      case 'sslv23': $this->method= STREAM_CRYPTO_METHOD_SSLv23_CLIENT; break;
      case 'sslv2': $this->method= STREAM_CRYPTO_METHOD_SSLv2_CLIENT; break;
      case 'tls': $this->method= STREAM_CRYPTO_METHOD_TLS_CLIENT; break;
      case 'tlsv10': $this->method= STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT; break;
      case 'tlsv11': $this->method= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT; break;
      case 'tlsv12': $this->method= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT; break;
      case 'tlsv13': $this->method= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT; break;
      default: throw new IllegalArgumentException('Undefined crypto method '.$method);
    }
  }

  /**
   * Connect, then enable crypto
   * 
   * @param   float $timeout
   * @return  bool
   * @throws  peer.SSLUnverifiedPeerException if peer verification fails
   * @throws  peer.SSLHandshakeException if handshake fails for any other reasons
   * @throws  peer.ConnectException for all other reasons
   */
  public function connect($timeout= 2.0) {
    if ($this->isConnected()) return true;

    parent::connect($timeout);
    if (stream_socket_enable_crypto($this->_sock, true, $this->method)) return true;

    // Parse OpenSSL errors:
    if (preg_match('/error:(\d+):(.+)/', key(end(\xp::$errors[__FILE__])), $matches)) {
      switch ($matches[1]) {
        case '14090086': $e= new SSLUnverifiedPeerException($matches[2]); break;
        default: $e= new SSLHandshakeException($matches[2]); break;
      }
    } else {
      $e= new SSLHandshakeException('Unable to enable crypto.');
    }

    $this->close();
    throw $e;
  }

  /**
   * Retrieve captured peer certificate
   *
   * @return string
   * @throws lang.IllegalStateException if capturing is disabled
   */
  public function peerCertificate() {
    if (!$this->getSocketOption('ssl', 'capture_peer_cert')) {
      throw new IllegalStateException('Cannot get peer\'s certificate: capturing is disabled.');
    }

    return $this->getSocketOption('ssl', 'peer_certificate');
  }

  /**
   * Retrieve captured peer certificate chain
   *
   * @return  string[]
   * @throws  lang.IllegalStateException if capturing is disabled
   */
  public function peerCertificateChain() {
    if (!$this->getSocketOption('ssl', 'capture_peer_cert_chain')) {
      throw new IllegalStateException('Cannot get peer\'s certificate chain: capturing is disabled.');
    }

    return $this->getSocketOption('ssl', 'peer_certificate_chain');
  }
}